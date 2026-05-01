<?php
require 'db.php';
foreach(['subscriptions'] as $t) {
    echo "Table $t:\n";
    foreach($pdo->query("SHOW FULL COLUMNS FROM $t") as $c) {
        if(strpos($c['Field'], 'client') !== false) {
            echo $c['Field'] . ": " . $c['Collation'] . "\n";
        }
    }
}
