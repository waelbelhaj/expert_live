<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

// Superadmin check
if (!isset($_SESSION["user"]) || $_SESSION["user"][1] !== '*') {
    echo json_encode(["error" => "Unauthorized access"]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    switch ($action) {
        case 'listClients':
            $month = $_GET['month'] ?? date('Y-m');
            $sql = "SELECT c.*, 
                           s.is_paid, 
                           s.connections_count,
                           (SELECT attempt_count FROM daily_access WHERE client_id = c.id_client AND access_date = CURDATE()) as daily_attempts
                    FROM clients c
                    LEFT JOIN subscriptions s ON c.id_client = s.client_id AND s.month = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$month]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'toggleLock':
            $idClient = $_POST['idClient'];
            $status = (int)$_POST['status'];
            $stmt = $pdo->prepare("UPDATE clients SET is_locked = ? WHERE id_client = ?");
            $stmt->execute([$status, $idClient]);
            echo json_encode(["success" => true]);
            break;

        case 'markPaid':
            $idClient = $_POST['idClient'];
            $month = $_POST['month'] ?? date('Y-m');
            $status = (int)$_POST['status'];
            
            $stmt = $pdo->prepare("INSERT INTO subscriptions (client_id, month, is_paid) VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE is_paid = ?");
            $stmt->execute([$idClient, $month, $status, $status]);
            echo json_encode(["success" => true]);
            break;

        case 'saveClient':
            $id = $_POST['id_client'];
            $pin = $_POST['code_pin'];
            $nom = $_POST['nom'];
            $role = $_POST['role'];
            $notes = $_POST['notes'] ?? '';
            $caisse = (int)($_POST['caisse_id'] ?? 1);
            
            $stmt = $pdo->prepare("INSERT INTO clients (id_client, code_pin, nom, role, caisse_id, notes) VALUES (?, ?, ?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE code_pin=?, nom=?, role=?, caisse_id=?, notes=?");
            $stmt->execute([$id, $pin, $nom, $role, $caisse, $notes, $pin, $nom, $role, $caisse, $notes]);
            echo json_encode(["success" => true]);
            break;

        case 'deleteClient':
            $id = $_POST['id_client'];
            $stmt = $pdo->prepare("DELETE FROM clients WHERE id_client = ?");
            $stmt->execute([$id]);
            echo json_encode(["success" => true]);
            break;

        case 'getPaymentHistory':
            $idClient = $_GET['idClient'];
            $stmt = $pdo->prepare("SELECT * FROM payment_records WHERE client_id = ? ORDER BY payment_date DESC");
            $stmt->execute([$idClient]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'addPayment':
            $idClient = $_POST['idClient'];
            $amount = (float)$_POST['amount'];
            $periodType = $_POST['periodType']; // 'month' or 'year'
            $periodValue = $_POST['periodValue']; // '2026-04' or '2026'
            $method = $_POST['method'] ?? 'Espèces';
            $notes = $_POST['admin_notes'] ?? '';

            $pdo->beginTransaction();
            
            // 1. Add to payment history
            $stmt = $pdo->prepare("INSERT INTO payment_records (client_id, amount, period_type, period_value, payment_method, admin_notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$idClient, $amount, $periodType, $periodValue, $method, $notes]);

            // 2. Update subscriptions table
            if ($periodType === 'year') {
                for ($m = 1; $m <= 12; $m++) {
                    $month = $periodValue . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
                    $stmtSub = $pdo->prepare("INSERT INTO subscriptions (client_id, month, is_paid) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE is_paid = 1");
                    $stmtSub->execute([$idClient, $month]);
                }
            } else {
                $stmtSub = $pdo->prepare("INSERT INTO subscriptions (client_id, month, is_paid) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE is_paid = 1");
                $stmtSub->execute([$idClient, $periodValue]);
            }

            $pdo->commit();
            echo json_encode(["success" => true]);
            break;

        default:
            echo json_encode(["error" => "Invalid action"]);
            break;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["error" => $e->getMessage()]);
}
