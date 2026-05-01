<?php
require_once __DIR__ . '/db.php';
echo "Clients:\n";
print_r($pdo->query("SELECT id_client, nom, caisse_id FROM clients")->fetchAll());
