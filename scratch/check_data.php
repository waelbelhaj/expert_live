<?php
require 'db.php';
$stmt = $pdo->query('SELECT id, id_client, nom, caisse_id FROM clients LIMIT 5');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
