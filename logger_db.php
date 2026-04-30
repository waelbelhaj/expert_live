<?php
/**
 * logger.php
 * Intercepte les accès et les enregistre dans la base de données 
 * plutôt que dans des fichiers physiques.
 */

// S'assurer que db.php n'est pas déjà inclus si logger.php est appelé en premier
require_once __DIR__ . '/db.php';

function writeAccessLog() {
    global $pdo;

    // ─── SUPERADMIN BYPASS ───────────────────────────────────────────────────
    // If the logged-in user is the Superadmin (*), we do NOT log the access.
    if (isset($_SESSION["user"]) && $_SESSION["user"][1] === '*') {
        return;
    }

    $idClient = isset($_GET['idClient']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['idClient']) : (isset($_REQUEST['idSte']) ? $_REQUEST['idSte'] : 'unknown');

    // Collecte des infos serveur
    $now        = date('Y-m-d H:i:s');
    $ip         = $_SERVER['REMOTE_ADDR'] ?? '-';
    
    // Gérer les proxies, load balancers
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    $ip         = trim($ip);
    
    $ua         = $_SERVER['HTTP_USER_AGENT']    ?? '-';
    // $referer    = $_SERVER['HTTP_REFERER']        ?? '-'; // Peut être ajouté au log de la DB plus tard
    $method     = $_SERVER['REQUEST_METHOD']      ?? 'GET';
    $uri        = $_SERVER['REQUEST_URI']         ?? '-';
    // $session    = session_id()                    ?: '-';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO logs (client_id, created_at, ip, uri, method, user_agent) 
            VALUES (:client_id, :created_at, :ip, :uri, :method, :ua)
        ");
        
        $stmt->execute([
            ':client_id'  => substr($idClient, 0, 20),
            ':created_at' => $now,
            ':ip'         => substr($ip, 0, 45),
            ':uri'        => substr($uri, 0, 255),
            ':method'     => substr($method, 0, 10),
            ':ua'         => substr($ua, 0, 255)
        ]);
        
    } catch (\PDOException $e) {
        // En cas d'erreur de journalisation, on l'ignore de façon silencieuse 
        // pour ne pas stopper l'exécution du reste de l'application
        error_log("DB Logger Error: " . $e->getMessage());
    }
}

// Appeler le script d'enregistrement s'il n'est pas désactivé 
// pour la session courante.
writeAccessLog();
