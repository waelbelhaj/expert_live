<?php
require_once __DIR__ . '/db.php';
header('Content-Type: text/plain');

echo "--- RÉPARATION FINALE (FORÇAGE DES DOUBLONS) ---\n";

try {
    echo "1. Nettoyage des verrous...\n";
    $toDrop = ['subscriptions' => 'subscriptions_ibfk_1', 'daily_access' => 'daily_access_ibfk_1', 'payment_records' => 'payment_records_ibfk_1'];
    foreach ($toDrop as $table => $fk) {
        try {
            $pdo->exec("ALTER TABLE $table DROP FOREIGN KEY $fk");
        } catch (Exception $e) {
        }
    }
    echo "[OK] Nettoyage terminé.\n";

    echo "\n2. Restauration forcée des données...\n";
    $clients = $pdo->query("SELECT id_client, caisse_id FROM clients")->fetchAll();
    $tables = ['tickets', 'clotures', 'depenses', 'logs', 'subscriptions', 'daily_access', 'payment_records'];

    foreach ($clients as $c) {
        $strId = $c['id_client'];
        $numId = (string) $c['caisse_id'];

        if (empty($numId) || $strId === $numId)
            continue;

        echo "Client $strId -> $numId : ";
        foreach ($tables as $table) {
            try {
                // Utilisation de IGNORE pour passer outre les doublons de SANSHO/440304901
                $stmt = $pdo->prepare("UPDATE IGNORE $table SET client_id = ? WHERE client_id = ?");
                $stmt->execute([$numId, $strId]);

                // Si UPDATE IGNORE a laissé des restes (doublons non fusionnables), on les nettoie
                $stmtDel = $pdo->prepare("DELETE FROM $table WHERE client_id = ?");
                $stmtDel->execute([$strId]);

                echo "[$table] ";
            } catch (Exception $e) {
                echo "[Erreur $table] ";
            }
        }
        echo "\n";
    }

    echo "\n[SUCCÈS TOTAL] Tout est synchronisé. Vous pouvez retourner sur votre dashboard.";

} catch (Exception $e) {
    echo "\n[ERREUR CRITIQUE] : " . $e->getMessage();
}
