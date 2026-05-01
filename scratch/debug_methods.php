<?php
require 'db.php';
foreach($pdo->query('SELECT DISTINCT payment_method FROM payment_records') as $row) {
    echo "Method: [" . $row['payment_method'] . "]\n";
}
