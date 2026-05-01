<?php
require 'db.php';
foreach(['clients', 'payment_records'] as $t) {
    echo "Table $t:\n";
    foreach($pdo->query("SHOW FULL COLUMNS FROM $t") as $c) {
        if(strpos($c['Field'], 'client') !== false) {
            echo $c['Field'] . ": " . $c['Collation'] . "\n";
        }
    }
}
