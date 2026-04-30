<?php
// missingTicket.php - Retourne le premier numéro de ticket manquant dans la séquence
$idClient = (isset($_GET['idSte'])) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['idSte']) : die("OAuth3.0 ERROR");

require_once __DIR__ . '/db.php';

try {
    $stmt = $pdo->prepare("SELECT num_ticket FROM tickets WHERE client_id = ? ORDER BY num_ticket ASC");
    $stmt->execute([$idClient]);
    $rows = $stmt->fetchAll();

    $prev = 0;
    foreach ($rows as $row) {
        $current = (int)$row['num_ticket'];
        if ($prev > 0 && ($prev + 1) !== $current) {
            // Trou détecté : le ticket ($prev + 1) est manquant
            echo ($prev + 1);
            die();
        }
        $prev = $current;
    }
} catch (\PDOException $e) {
    // En cas d'erreur, ne rien retourner pour éviter un blocage
}
// Aucun ticket manquant : retourner une chaîne vide
