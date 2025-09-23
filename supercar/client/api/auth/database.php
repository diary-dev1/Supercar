<?php
// config/database.php
// Ajuste ces valeurs si besoin
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'supercar_elite';

// Afficher les erreurs pendant le dev
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Activer les rapports d'erreurs MySQLi pour throw des exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    // En prod tu peux logger plutôt que d'afficher
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données: ' . $e->getMessage()
    ]);
    exit;
}

// Fonctions utilitaires
function setCorsHeaders() {
    // Ajuste Access-Control-Allow-Origin si nécessaire
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json; charset=utf-8");

    // Répondre aux préflight OPTIONS
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit();
    }
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    // Accepte chiffres, espaces, +, -, ()
    return preg_match('/^[0-9\-\+\s\(\)]+$/', $phone);
}
