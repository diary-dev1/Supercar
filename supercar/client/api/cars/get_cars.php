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
    $brand = isset($_GET['brand']) ? sanitizeInput($_GET['brand']) : '';
    $minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
    $maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 999999999;
    $available = isset($_GET['available']) ? filter_var($_GET['available'], FILTER_VALIDATE_BOOLEAN) : true;
    
    // Construction de la requête
    $whereConditions = ['available = ?'];
    $params = [$available];
    $types = 'i';
    
    if (!empty($brand)) {
        $whereConditions[] = 'brand LIKE ?';
        $params[] = "%$brand%";
        $types .= 's';
    }
    
    if ($minPrice > 0) {
        $whereConditions[] = 'price >= ?';
        $params[] = $minPrice;
        $types .= 'd';
    }
    
    if ($maxPrice < 999999999) {
        $whereConditions[] = 'price <= ?';
        $params[] = $maxPrice;
        $types .= 'd';
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Compter le total
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM cars WHERE $whereClause");
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    // Récupérer les voitures
    $stmt = $conn->prepare("
        SELECT id, name, brand, price, image_url, power, max_speed, acceleration, description, available 
        FROM cars 
        WHERE $whereClause 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $cars = $result->fetch_all(MYSQLI_ASSOC);
    
    // Formater les données
    $formattedCars = array_map(function($car) {
        return [
            'id' => intval($car['id']),
            'name' => $car['name'],
            'brand' => $car['brand'],
            'price' => number_format($car['price'], 0, ',', ' ') . ' €',
            'price_numeric' => floatval($car['price']),
            'image' => $car['image_url'],
            'power' => $car['power'],
            'speed' => $car['max_speed'],
            'acceleration' => $car['acceleration'],
            'description' => $car['description'],
            'available' => (bool)$car['available']
        ];
    }, $cars);
    
    echo json_encode([
        'success' => true,
        'data' => $formattedCars,
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
        'message' => 'Erreur lors de la récupération des voitures: ' . $e->getMessage()
    ]);
}
?>