<?php
require 'db.php';
foreach($pdo->query('SELECT payment_date, client_id, amount FROM payment_records') as $row) {
    print_r($row);
}
