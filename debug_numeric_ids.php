<?php
require_once __DIR__ . '/db.php';
$res = $pdo->query("SELECT id_client FROM clients WHERE id_client REGEXP '^[0-9]+$'")->fetchAll();
print_r($res);
