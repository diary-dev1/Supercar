<?php
require_once '../client/api/config/database.php';
setCorsHeaders();

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            getServices();
            break;
        case 'POST':
            createService();
            break;
        case 'PUT':
            updateService();
            break;
        case 'DELETE':
            deleteService();
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

function getServices() {
    global $conn;
    
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    // Compter le total
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM services");
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    // Récupérer les services
    $stmt = $conn->prepare("
        SELECT id, title, description, price, category, available, created_at
        FROM services 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $services = $result->fetch_all(MYSQLI_ASSOC);
    
    // Formater les données
    $formattedServices = array_map(function($service) {
        return [
            'id' => intval($service['id']),
            'title' => $service['title'],
            'description' => $service['description'],
            'price' => $service['price'],
            'category' => $service['category'],
            'available' => (bool)$service['available'],
            'created_at' => $service['created_at']
        ];
    }, $services);
    
    echo json_encode([
        'success' => true,
        'data' => $formattedServices,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => intval($total),
            'items_per_page' => $limit
        ]
    ]);
}

function createService() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Données JSON invalides');
    }
    
    $title = sanitizeInput($input['title'] ?? '');
    $description = sanitizeInput($input['description'] ?? '');
    $price = sanitizeInput($input['price'] ?? '');
    $category = sanitizeInput($input['category'] ?? '');
    $available = isset($input['available']) ? (bool)$input['available'] : true;
    
    // Validation
    if (empty($title) || empty($price)) {
        throw new Exception('Titre et prix sont obligatoires');
    }
    
    $stmt = $conn->prepare("
        INSERT INTO services (title, description, price, category, available) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('ssssi', $title, $description, $price, $category, $available);
    
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de la création du service');
    }
    
    $serviceId = $conn->insert_id;
    
    echo json_encode([
        'success' => true,
        'message' => 'Service créé avec succès',
        'data' => ['id' => $serviceId]
    ]);
}

function updateService() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        throw new Exception('ID de service requis');
    }
    
    $serviceId = intval($input['id']);
    $title = sanitizeInput($input['title'] ?? '');
    $description = sanitizeInput($input['description'] ?? '');
    $price = sanitizeInput($input['price'] ?? '');
    $category = sanitizeInput($input['category'] ?? '');
    $available = isset($input['available']) ? (bool)$input['available'] : true;
    
    // Validation
    if (empty($title) || empty($price)) {
        throw new Exception('Titre et prix sont obligatoires');
    }
    
    $stmt = $conn->prepare("
        UPDATE services 
        SET title = ?, description = ?, price = ?, category = ?, available = ?
        WHERE id = ?
    ");
    $stmt->bind_param('ssssii', $title, $description, $price, $category, $available, $serviceId);
    
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de la mise à jour du service');
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Service non trouvé');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Service mis à jour avec succès'
    ]);
}

function deleteService() {
    global $conn;
    
    $serviceId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($serviceId <= 0) {
        throw new Exception('ID de service invalide');
    }
    
    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
    $stmt->bind_param('i', $serviceId);
    
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de la suppression du service');
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Service non trouvé');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Service supprimé avec succès'
    ]);
}
?>