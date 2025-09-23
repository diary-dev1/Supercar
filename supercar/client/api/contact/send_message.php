<?php
require_once '../config/database.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    $raw = file_get_contents('php://input');
    error_log("Contact raw input: " . $raw); // Debug
    
    $input = json_decode($raw, true);
    
    if (!$input) {
        error_log("Contact JSON decode error: " . json_last_error_msg()); // Debug
        throw new Exception('Données JSON invalides');
    }
    
    $nom = sanitizeInput($input['nom'] ?? '');
    $email = sanitizeInput($input['email'] ?? '');
    $sujet = sanitizeInput($input['sujet'] ?? '');
    $message = sanitizeInput($input['message'] ?? '');
    
    error_log("Contact parsed data - Nom: $nom, Email: $email, Sujet: $sujet"); // Debug
    
    // Validation des données
    if (empty($nom)) {
        throw new Exception('Le nom est requis');
    }
    
    if (empty($email)) {
        throw new Exception('L\'email est requis');
    }
    
    if (!validateEmail($email)) {
        throw new Exception('Format d\'email invalide');
    }
    
    if (empty($sujet)) {
        throw new Exception('Le sujet est requis');
    }
    
    if (empty($message)) {
        throw new Exception('Le message est requis');
    }
    
    if (strlen($nom) > 100) {
        throw new Exception('Le nom ne peut pas dépasser 100 caractères');
    }
    
    if (strlen($sujet) > 200) {
        throw new Exception('Le sujet ne peut pas dépasser 200 caractères');
    }
    
    if (strlen($message) > 2000) {
        throw new Exception('Le message ne peut pas dépasser 2000 caractères');
    }
    
    // Insérer le message de contact (CORRECTION: utiliser $conn au lieu de $pdo)
    $stmt = $conn->prepare("INSERT INTO contact_messages (nom, email, sujet, message) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Contact prepare error: " . $conn->error); // Debug
        throw new Exception('Erreur SQL (prepare) : ' . $conn->error);
    }
    
    $stmt->bind_param('ssss', $nom, $email, $sujet, $message);
    $result = $stmt->execute();
    
    if (!$result) {
        error_log("Contact insert error: " . $stmt->error); // Debug
        throw new Exception('Erreur lors de l\'insertion : ' . $stmt->error);
    }
    
    $messageId = $conn->insert_id;
    error_log("Contact message created with ID: " . $messageId); // Debug
    
    echo json_encode([
        'success' => true,
        'message' => 'Message envoyé avec succès',
        'data' => [
            'id' => intval($messageId),
            'nom' => $nom,
            'email' => $email,
            'sujet' => $sujet
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Contact error: " . $e->getMessage()); // Debug
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>