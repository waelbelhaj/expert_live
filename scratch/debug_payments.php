<?php
require 'db.php';
echo "--- PAYMENT RECORDS ---\n";
foreach($pdo->query('SELECT * FROM payment_records') as $row) {
    print_r($row);
}
echo "--- CLIENTS ---\n";
foreach($pdo->query('SELECT id_client, nom FROM clients') as $row) {
    print_r($row);
}
