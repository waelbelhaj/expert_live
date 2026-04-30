<?php
require 'db.php';
$idClient = '440304901'; // Example from user
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id_client = ? OR caisse_id = ?");
$stmt->execute([$idClient, $idClient]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);
echo "CLIENT INFO:\n";
print_r($client);

if ($client) {
    $realId = $client['id_client'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE client_id = ?");
    $stmt->execute([$realId]);
    echo "\nTICKETS COUNT for '$realId': " . $stmt->fetchColumn() . "\n";
}
