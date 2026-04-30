<?php
require 'db.php';
$stmt = $pdo->query('DESCRIBE clients');
print_r($stmt->fetchAll());
$stmt = $pdo->query('SHOW CREATE TABLE clients');
echo "\n\nCREATE TABLE SQL:\n" . $stmt->fetch()[1] . "\n";
