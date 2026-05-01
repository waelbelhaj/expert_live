<?php
require_once __DIR__ . '/db.php';
echo "Tickets for '1': " . $pdo->query("SELECT count(*) FROM tickets WHERE client_id = '1'")->fetchColumn() . "\n";
echo "Tickets for '123': " . $pdo->query("SELECT count(*) FROM tickets WHERE client_id = '123'")->fetchColumn() . "\n";
echo "Tickets for 'SANSHO': " . $pdo->query("SELECT count(*) FROM tickets WHERE client_id = 'SANSHO'")->fetchColumn() . "\n";
