<?php
require_once __DIR__ . '/db.php';
echo "Tickets for 'test': " . $pdo->query("SELECT count(*) FROM tickets WHERE client_id = 'test'")->fetchColumn() . "\n";
echo "Tickets for '1': " . $pdo->query("SELECT count(*) FROM tickets WHERE client_id = '1'")->fetchColumn() . "\n";
echo "All tickets (first 5): \n";
print_r($pdo->query("SELECT id, client_id, num_ticket FROM tickets LIMIT 5")->fetchAll());
