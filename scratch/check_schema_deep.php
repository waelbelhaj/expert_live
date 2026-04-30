<?php
require 'db.php';
echo "--- COLUMNS ---\n";
$stmt = $pdo->query('DESCRIBE clients');
print_r($stmt->fetchAll());
echo "\n--- KEYS ---\n";
$stmt = $pdo->query('SHOW KEYS FROM clients');
print_r($stmt->fetchAll());
echo "\n--- CREATE TABLE ---\n";
$stmt = $pdo->query('SHOW CREATE TABLE clients');
$res = $stmt->fetch(PDO::FETCH_ASSOC);
echo $res['Create Table'] . "\n";
