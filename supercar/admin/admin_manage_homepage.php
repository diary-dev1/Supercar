<?php
require_once '../client/api/config/database.php';
setCorsHeaders();

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            getHomepageConfig();
            break;
        case 'PUT':
            updateHomepageConfig();
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}

function getHomepageConfig() {
    global $conn;
    
    // Créer la table si elle n'existe pas
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS homepage_config (
            id INT PRIMARY KEY AUTO_INCREMENT,
            hero_title VARCHAR(255) NOT NULL DEFAULT 'L\'Excellence Automobile',
            hero_subtitle TEXT DEFAULT 'Découvrez notre collection exclusive de supercars d\'exception',
            hero_image_url TEXT DEFAULT 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80',
            hero_button_text VARCHAR(100) DEFAULT 'Découvrir nos Voitures',
            about_title VARCHAR(255) DEFAULT 'Excellence & Passion',
            about_description TEXT DEFAULT 'Depuis plus de 20 ans, nous nous spécialisons dans la vente et l\'entretien de véhicules d\'exception.',
            company_name VARCHAR(100) DEFAULT 'Supercar Elite',
            company_phone VARCHAR(20) DEFAULT '',
            company_email VARCHAR(100) DEFAULT '',
            company_address TEXT DEFAULT '',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    
    $conn->query($createTableQuery);
    
    // Vérifier si des données existent
    $stmt = $conn->prepare("SELECT * FROM homepage_config ORDER BY id LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $config = $result->fetch_assoc();
    
    // Si aucune configuration n'existe, créer une configuration par défaut
    if (!$config) {
        $stmt = $conn->prepare("
            INSERT INTO homepage_config (hero_title, hero_subtitle, hero_image_url, hero_button_text, about_title, about_description, company_name) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $defaultValues = [
            "L'Excellence Automobile",
            "Découvrez notre collection exclusive de supercars d'exception",
            "https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80",
            "Découvrir nos Voitures",
            "Excellence & Passion",
            "Depuis plus de 20 ans, nous nous spécialisons dans la vente et l'entretien de véhicules d'exception.",
            "Supercar Elite"
        ];
        $stmt->bind_param('sssssss', ...$defaultValues);
        $stmt->execute();
        
        // Récupérer la configuration créée
        $stmt = $conn->prepare("SELECT * FROM homepage_config ORDER BY id LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        $config = $result->fetch_assoc();
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => intval($config['id']),
            'hero_title' => $config['hero_title'],
            'hero_subtitle' => $config['hero_subtitle'],
            'hero_image_url' => $config['hero_image_url'],
            'hero_button_text' => $config['hero_button_text'],
            'about_title' => $config['about_title'],
            'about_description' => $config['about_description'],
            'company_name' => $config['company_name'],
            'company_phone' => $config['company_phone'],
            'company_email' => $config['company_email'],
            'company_address' => $config['company_address'],
            'updated_at' => $config['updated_at']
        ]
    ]);
}

function updateHomepageConfig() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Données JSON invalides');
    }
    
    $configId = isset($input['id']) ? intval($input['id']) : 1;
    $heroTitle = sanitizeInput($input['hero_title'] ?? '');
    $heroSubtitle = sanitizeInput($input['hero_subtitle'] ?? '');
    $heroImageUrl = sanitizeInput($input['hero_image_url'] ?? '');
    $heroButtonText = sanitizeInput($input['hero_button_text'] ?? '');
    $aboutTitle = sanitizeInput($input['about_title'] ?? '');
    $aboutDescription = sanitizeInput($input['about_description'] ?? '');
    $companyName = sanitizeInput($input['company_name'] ?? '');
    $companyPhone = sanitizeInput($input['company_phone'] ?? '');
    $companyEmail = sanitizeInput($input['company_email'] ?? '');
    $companyAddress = sanitizeInput($input['company_address'] ?? '');
    
    // Validation
    if (empty($heroTitle) || empty($companyName)) {
        throw new Exception('Le titre principal et le nom de l\'entreprise sont obligatoires');
    }
    
    // Vérifier si l'enregistrement existe
    $stmt = $conn->prepare("SELECT id FROM homepage_config WHERE id = ?");
    $stmt->bind_param('i', $configId);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    
    if ($exists) {
        // Mise à jour
        $stmt = $conn->prepare("
            UPDATE homepage_config 
            SET hero_title = ?, hero_subtitle = ?, hero_image_url = ?, hero_button_text = ?, 
                about_title = ?, about_description = ?, company_name = ?, company_phone = ?, 
                company_email = ?, company_address = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->bind_param('ssssssssssi', $heroTitle, $heroSubtitle, $heroImageUrl, $heroButtonText, 
                         $aboutTitle, $aboutDescription, $companyName, $companyPhone, 
                         $companyEmail, $companyAddress, $configId);
    } else {
        // Insertion
        $stmt = $conn->prepare("
            INSERT INTO homepage_config (hero_title, hero_subtitle, hero_image_url, hero_button_text, 
                                       about_title, about_description, company_name, company_phone, 
                                       company_email, company_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('ssssssssss', $heroTitle, $heroSubtitle, $heroImageUrl, $heroButtonText, 
                         $aboutTitle, $aboutDescription, $companyName, $companyPhone, 
                         $companyEmail, $companyAddress);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de la mise à jour de la configuration');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Configuration mise à jour avec succès'
    ]);
}
?>