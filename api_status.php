<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$idClient = $_GET['idClient'] ?? null;
if (!$idClient) {
    echo json_encode(["status" => "ERROR", "message" => "Client ID missing"]);
    exit;
}

$currentMonth = date('Y-m');
$currentDate = date('Y-m-d');

try {
    // 1. Get client info
    $stmt = $pdo->prepare("SELECT id_client, is_locked FROM clients WHERE id_client = ?");
    $stmt->execute([$idClient]);
    $client = $stmt->fetch();

    if (!$client) {
        echo json_encode(["status" => "ERROR", "message" => "Client not found"]);
        exit;
    }

    if ($client['is_locked']) {
        echo json_encode(["status" => "LOCKED", "message" => "Application verrouillée par l'administrateur"]);
        exit;
    }

    // 2. Manage Subscription (Monthly)
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE client_id = ? AND month = ?");
    $stmt->execute([$idClient, $currentMonth]);
    $subscription = $stmt->fetch();

    if (!$subscription) {
        // Create initial subscription record for the month
        $pdo->prepare("INSERT IGNORE INTO subscriptions (client_id, month, is_paid, connections_count) VALUES (?, ?, 0, 0)")
            ->execute([$idClient, $currentMonth]);
        
        $stmt->execute([$idClient, $currentMonth]);
        $subscription = $stmt->fetch();
    }

    // Increment connection count
    $pdo->prepare("UPDATE subscriptions SET connections_count = connections_count + 1 WHERE id = ?")
        ->execute([$subscription['id']]);
    
    $connectionsCount = $subscription['connections_count'] + 1;

    // 3. Manage Daily Access (for unpaid logic)
    $stmt = $pdo->prepare("SELECT * FROM daily_access WHERE client_id = ? AND access_date = ?");
    $stmt->execute([$idClient, $currentDate]);
    $daily = $stmt->fetch();

    if (!$daily) {
        $pdo->prepare("INSERT IGNORE INTO daily_access (client_id, access_date, attempt_count) VALUES (?, ?, 0)")
            ->execute([$idClient, $currentDate]);
        $stmt->execute([$idClient, $currentDate]);
        $daily = $stmt->fetch();
    }

    // 4. Subscription Enforcement Logic
    $isPaid = (bool)$subscription['is_paid'];
    
    // Check monthly connection limit (60)
    if ($connectionsCount > 60) {
        echo json_encode([
            "status" => "LIMIT_EXCEEDED",
            "message" => "Limite de 60 connexions mensuelles atteinte",
            "connections" => $connectionsCount
        ]);
        exit;
    }

    if (!$isPaid) {
        $attempts = (int)$daily['attempt_count'];
        
        if ($attempts < 3) {
            // Increment attempt count
            $pdo->prepare("UPDATE daily_access SET attempt_count = attempt_count + 1 WHERE id = ?")
                ->execute([$daily['id']]);
            
            echo json_encode([
                "status" => "UNPAID",
                "message" => "Abonnement non payé. Déconnexion dans 5 secondes.",
                "disconnect_after" => 5,
                "attempts_remaining" => 3 - ($attempts + 1)
            ]);
            exit;
        } else {
            echo json_encode([
                "status" => "UNPAID_LIMIT",
                "message" => "Limite de 3 tentatives quotidiennes atteinte pour un compte non payé.",
                "attempts_remaining" => 0
            ]);
            exit;
        }
    }

    // Everything is fine
    echo json_encode([
        "status" => "OK",
        "message" => "Accès autorisé",
        "is_paid" => true,
        "connections" => $connectionsCount
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "ERROR", "message" => "Database error: " . $e->getMessage()]);
}
