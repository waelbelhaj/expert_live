<?php
/**
 * clients.php
 * Ce fichier charge les utilisateurs depuis la base de données 
 * et reconstruit le tableau $users pour la rétrocompatibilité 
 * avec l'ancien système d'authentification et de groupes.
 */

// Inclure la configuration de la base de données
require_once __DIR__ . '/db.php';

$users = [];

try {
    // Lecture des clients
    $stmt = $pdo->query("SELECT id_client, code_pin, nom, role, caisse_id FROM clients");
    while ($row = $stmt->fetch()) {
        if (!empty($row['caisse_id']) && $row['caisse_id'] > 1) {
            $users[$row['id_client']] = [
                $row['code_pin'],
                $row['id_client'], // dans l'ancien système c'était souvent un identifiant dossier (numérique/tel), mais l'ID client est utilisé
                $row['nom'],
                $row['role'],
                (int)$row['caisse_id']
            ];
        } else {
            $users[$row['id_client']] = [
                $row['code_pin'],
                $row['id_client'], 
                $row['nom'],
                $row['role']
            ];
        }
    }
} catch (PDOException $e) {
    // Si la table n'est pas encore créée ou s'il y a une erreur
    // On peut logger ou gérer l'erreur silencieusement en mode API
    error_log("Erreur lors de la lecture des clients: " . $e->getMessage());
}
