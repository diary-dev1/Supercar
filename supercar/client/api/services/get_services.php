<?php
require_once '../config/database.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    // Paramètres de pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 9;
    $offset = ($page - 1) * $limit;
    
    // Filtres
    $category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
    $available = isset($_GET['available']) ? filter_var($_GET['available'], FILTER_VALIDATE_BOOLEAN) : true;
    
    // Construction de la requête
    $whereConditions = ['available = ?'];
    $params = [$available];
    $types = 'i';
    
    if (!empty($category)) {
        $whereConditions[] = 'category = ?';
        $params[] = $category;
        $types .= 's';
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Compter le total
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM services WHERE $whereClause");
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    // Récupérer les services
    $stmt = $conn->prepare("
        SELECT id, title, description, price, icon, category, available 
        FROM services 
        WHERE $whereClause 
        ORDER BY created_at ASC 
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
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
            'icon' => $service['icon'],
            'category' => $service['category'],
            'available' => (bool)$service['available']
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
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des services: ' . $e->getMessage()
    ]);
}
?>