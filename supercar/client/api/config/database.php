<?php
// config/database.php (MySQLi - orienté objet)

// Paramètres de connexion
$host = 'localhost';
$dbname = 'supercar_elite';
$username = 'root';
$password = '';

// Afficher les erreurs pendant le développement
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Activer les rapports d'erreurs MySQLi pour lever des exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    http_response_code(500);
    // En dev on affiche l'erreur ; en production, loguer plutôt.
    die(json_encode([
        'success' => false,
        'message' => 'Erreur de connexion : ' . $e->getMessage()
    ]));
}

/**
 * Headers CORS pour permettre les requêtes depuis le frontend
 */
function setCorsHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json; charset=utf-8');

    // Gérer les requêtes OPTIONS (preflight)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

/**
 * Valide un email
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valide un numéro de téléphone (au moins 8 caractères, chiffres, espaces, +, -, parentheses)
 * @param string $phone
 * @return bool|int
 */
function validatePhone($phone) {
    return preg_match('/^[+]?[0-9\s\-\(\)]{8,}$/', $phone);
}

/**
 * Nettoie une entrée utilisateur
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
?>
