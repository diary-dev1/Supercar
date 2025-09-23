<?php
require_once '../config/database.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    // Lire les données JSON
    $raw = file_get_contents('php://input');
    error_log("Raw input: " . $raw); // Debug
    
    $input = json_decode($raw, true);

    if (!$input) {
        error_log("JSON decode error: " . json_last_error_msg()); // Debug
        throw new Exception('Données JSON invalides : ' . json_last_error_msg());
    }

    // Récupération et sanitation
    $name = sanitizeInput($input['name'] ?? '');
    $email = sanitizeInput($input['email'] ?? '');
    $phone = sanitizeInput($input['phone'] ?? '');
    $password = $input['password'] ?? '';

    error_log("Parsed data - Name: $name, Email: $email, Phone: $phone"); // Debug

    // Vérifications
    if (empty($name) || empty($email) || empty($password)) {
        throw new Exception('Nom, email et mot de passe sont obligatoires');
    }

    if (!validateEmail($email)) {
        throw new Exception('Format d\'email invalide');
    }

    if (strlen($password) < 6) {
        throw new Exception('Le mot de passe doit contenir au moins 6 caractères');
    }

    if (!empty($phone) && !validatePhone($phone)) {
        throw new Exception('Format de téléphone invalide');
    }

    // Vérifier si l'email existe déjà 
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        error_log("Prepare error: " . $conn->error); // Debug
        throw new Exception('Erreur SQL (prepare) : ' . $conn->error);
    }
    
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->fetch_assoc()) {
        throw new Exception('Cet email est déjà utilisé');
    }

    // Hasher le mot de passe
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    error_log("Password hashed successfully"); // Debug

    // Insertion de l'utilisateur
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Insert prepare error: " . $conn->error); // Debug
        throw new Exception('Erreur SQL (prepare insert) : ' . $conn->error);
    }
    
    $stmt->bind_param('ssss', $name, $email, $phone, $hashedPassword);
    $result = $stmt->execute();
    
    if (!$result) {
        error_log("Insert execute error: " . $stmt->error); // Debug
        throw new Exception('Erreur lors de l\'insertion : ' . $stmt->error);
    }

    $userId = $conn->insert_id;
    error_log("User created with ID: " . $userId); // Debug

    // Démarrer la session
    session_start();
    $_SESSION['user_id']    = $userId;
    $_SESSION['user_name']  = $name;
    $_SESSION['user_email'] = $email;

    echo json_encode([
        'success' => true,
        'message' => 'Inscription réussie',
        'user' => [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone
        ]
    ]);

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage()); // Debug
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>