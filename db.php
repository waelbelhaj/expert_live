<?php
// Fichier : db.php
// Connexion PDO globale et optimisée

$host = '127.0.0.1'; // ou localhost
$db = 'expert_live';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
//  $host = '102.219.176.39'; // ou localhost
//  $db = 'dveuvwkq_exp_liv';
//  $user = 'dveuvwkq_exp_liv';
//  $pass = 'bftKtQVN222OlTIW';
//  $charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false, // Important pour utiliser les types de données natifs
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Si la config database échoue, on renvoie une réponse json ou un code d'erreur HTTP 500
    http_response_code(500);
    die(json_encode(["error" => "Erreur de connexion a la base de donnees", "details" => $e->getMessage()]));
}
