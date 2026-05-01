<?php
require 'db.php';
echo "--- CLIENTS ---\n";
foreach($pdo->query('SELECT id_client, nom, caisse_id FROM clients LIMIT 10') as $row) {
    print_r($row);
}
echo "--- SUBSCRIPTIONS MAY 2026 ---\n";
foreach($pdo->query("SELECT * FROM subscriptions WHERE month='2026-05'") as $row) {
    print_r($row);
}
