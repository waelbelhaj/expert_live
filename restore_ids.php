<?php
require_once __DIR__ . '/db.php';

$clients = $pdo->query("SELECT id_client, caisse_id FROM clients")->fetchAll();
$tables = ['tickets', 'clotures', 'depenses', 'logs', 'subscriptions', 'daily_access', 'payment_records'];

echo "Restauration des données vers la méthode 'caisse_id'...\n";

foreach ($clients as $c) {
    $strId = $c['id_client'];
    $numId = $c['caisse_id'];
    
    if (empty($numId) || $strId === (string)$numId) continue;
    
    echo "Client $strId -> Retour vers $numId\n";
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("UPDATE $table SET client_id = ? WHERE client_id = ?");
        $stmt->execute([(string)$numId, $strId]);
        if ($stmt->rowCount() > 0) echo "  - $table: " . $stmt->rowCount() . " lignes restaurées.\n";
    }
}
echo "Terminé.";
