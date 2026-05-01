<?php
require 'db.php';
$month = '2026-05';
$year = '2026';
$sql = "SELECT c.*, (SELECT payment_method FROM payment_records WHERE (client_id = c.id_client OR client_id = CAST(c.caisse_id AS CHAR)) AND (period_value = ? OR period_value = ?) LIMIT 1) as payment_method FROM clients c LIMIT 1";
try {
    $s = $pdo->prepare($sql);
    $s->execute([$month, $year]);
    print_r($s->fetch());
} catch (Exception $e) {
    echo "SQL ERROR: " . $e->getMessage() . "\n";
}
