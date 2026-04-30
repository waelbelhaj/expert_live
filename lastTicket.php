<?php
// lastTicket.php - Retourne le dernier num_ticket envoyé pour un client donné
$idClient = (isset($_GET['idSte'])) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['idSte']) : die("OAuth3.0 ERROR");

require_once __DIR__ . '/db.php';

$lastTicket = 0;
try {
    $stmt = $pdo->prepare("SELECT MAX(num_ticket) as last FROM tickets WHERE client_id = ?");
    $stmt->execute([$idClient]);
    $row = $stmt->fetch();
    if ($row && $row['last'] !== null) {
        $lastTicket = (int)$row['last'];
    }
} catch (\PDOException $e) {
    // En cas d'erreur, retourner 0 pour éviter un blocage côté logiciel
    $lastTicket = 0;
}

echo $lastTicket;
