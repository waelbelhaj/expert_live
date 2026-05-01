<?php
require_once __DIR__ . '/db.php';
try {
    echo "Dropping FKs...\n";
    $pdo->exec("ALTER TABLE subscriptions DROP FOREIGN KEY subscriptions_ibfk_1");
    echo "OK subscriptions\n";
} catch (Exception $e) { echo "FAIL subscriptions: " . $e->getMessage() . "\n"; }

try {
    $pdo->exec("ALTER TABLE daily_access DROP FOREIGN KEY daily_access_ibfk_1");
    echo "OK daily_access\n";
} catch (Exception $e) { echo "FAIL daily_access: " . $e->getMessage() . "\n"; }

try {
    $pdo->exec("ALTER TABLE payment_records DROP FOREIGN KEY payment_records_ibfk_1");
    echo "OK payment_records\n";
} catch (Exception $e) { echo "FAIL payment_records: " . $e->getMessage() . "\n"; }
