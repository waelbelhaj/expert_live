<?php
require 'db.php';
echo "--- ALL CLIENTS IN DB ---\n";
$stmt = $pdo->query("SELECT id_client, nom, caisse_id FROM clients");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
