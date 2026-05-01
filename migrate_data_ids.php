<?php
require_once __DIR__ . '/db.php';

$clients = $pdo->query("SELECT id_client, caisse_id FROM clients")->fetchAll();

echo "Starting Data Migration from numeric IDs to string IDs...\n";
echo "--------------------------------------------------------\n";

$tables = ['tickets', 'clotures', 'depenses', 'logs', 'subscriptions', 'daily_access', 'payment_records'];

foreach ($clients as $c) {
    $strId = $c['id_client'];
    $numId = $c['caisse_id'];
    
    if (empty($numId)) continue;
    if ($strId === (string)$numId) continue;
    
    echo "Processing Client: $strId (Caisse ID: $numId)\n";
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->prepare("UPDATE $table SET client_id = ? WHERE client_id = ?");
            $stmt->execute([$strId, $numId]);
            $count = $stmt->rowCount();
            if ($count > 0) {
                echo "  - Table $table: $count rows updated.\n";
            }
        } catch (Exception $e) {
            echo "  - Table $table: ERROR - " . $e->getMessage() . "\n";
        }
    }
}

echo "--------------------------------------------------------\n";
echo "Migration finished.\n";
