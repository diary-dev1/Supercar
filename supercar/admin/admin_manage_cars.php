<?php
require_once '../client/api/config/database.php';
setCorsHeaders();

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            getAllCars();
            break;
        case 'POST':
            createCar();
            break;
        case 'PUT':
            updateCar();
            break;
        case 'DELETE':
            deleteCar();
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

function getAllCars() {
    global $conn;
    
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    // Compter le total
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM cars");
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    // Récupérer les voitures avec pagination
    $stmt = $conn->prepare("
        SELECT id, name, brand, price, image_url, power, max_speed, acceleration, description, available, created_at
        FROM cars 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $cars = $result->fetch_all(MYSQLI_ASSOC);
    
    // Formater les données
    $formattedCars = array_map(function($car) {
        return [
            'id' => intval($car['id']),
            'name' => $car['name'],
            'brand' => $car['brand'],
            'price' => floatval($car['price']),
            'price_formatted' => number_format($car['price'], 0, ',', ' ') . ' €',
            'image_url' => $car['image_url'],
            'power' => $car['power'],
            'max_speed' => $car['max_speed'],
            'acceleration' => $car['acceleration'],
            'description' => $car['description'],
            'available' => (bool)$car['available'],
            'created_at' => $car['created_at']
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
}

function createCar() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Données JSON invalides');
    }
    
    $name = sanitizeInput($input['name'] ?? '');
    $brand = sanitizeInput($input['brand'] ?? '');
    $price = floatval($input['price'] ?? 0);
    $image_url = sanitizeInput($input['image_url'] ?? '');
    $power = sanitizeInput($input['power'] ?? '');
    $max_speed = sanitizeInput($input['max_speed'] ?? '');
    $acceleration = sanitizeInput($input['acceleration'] ?? '');
    $description = sanitizeInput($input['description'] ?? '');
    $available = isset($input['available']) ? (bool)$input['available'] : true;
    
    // Validation
    if (empty($name) || empty($brand) || $price <= 0) {
        throw new Exception('Nom, marque et prix sont obligatoires');
    }
    
    $stmt = $conn->prepare("
        INSERT INTO cars (name, brand, price, image_url, power, max_speed, acceleration, description, available) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('ssdsssssi', $name, $brand, $price, $image_url, $power, $max_speed, $acceleration, $description, $available);
    
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de la création de la voiture');
    }
    
    $carId = $conn->insert_id;
    
    echo json_encode([
        'success' => true,
        'message' => 'Voiture créée avec succès',
        'data' => ['id' => $carId]
    ]);
}

function updateCar() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        throw new Exception('ID de voiture requis');
    }
    
    $carId = intval($input['id']);
    $name = sanitizeInput($input['name'] ?? '');
    $brand = sanitizeInput($input['brand'] ?? '');
    $price = floatval($input['price'] ?? 0);
    $image_url = sanitizeInput($input['image_url'] ?? '');
    $power = sanitizeInput($input['power'] ?? '');
    $max_speed = sanitizeInput($input['max_speed'] ?? '');
    $acceleration = sanitizeInput($input['acceleration'] ?? '');
    $description = sanitizeInput($input['description'] ?? '');
    $available = isset($input['available']) ? (bool)$input['available'] : true;
    
    // Validation
    if (empty($name) || empty($brand) || $price <= 0) {
        throw new Exception('Nom, marque et prix sont obligatoires');
    }
    
    $stmt = $conn->prepare("
        UPDATE cars 
        SET name = ?, brand = ?, price = ?, image_url = ?, power = ?, max_speed = ?, acceleration = ?, description = ?, available = ?
        WHERE id = ?
    ");
    $stmt->bind_param('ssdsssssii', $name, $brand, $price, $image_url, $power, $max_speed, $acceleration, $description, $available, $carId);
    
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de la mise à jour de la voiture');
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Voiture non trouvée');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Voiture mise à jour avec succès'
    ]);
}

function deleteCar() {
    global $conn;
    
    $carId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($carId <= 0) {
        throw new Exception('ID de voiture invalide');
    }
    
    // Vérifier s'il y a des demandes d'essai en cours pour cette voiture
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM test_drives WHERE car_id = ? AND status IN ('pending', 'confirmed')");
    $stmt->bind_param('i', $carId);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];
    
    if ($count > 0) {
        throw new Exception('Impossible de supprimer cette voiture car elle a des demandes d\'essai en cours');
    }
    
    $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
    $stmt->bind_param('i', $carId);
    
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de la suppression de la voiture');
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Voiture non trouvée');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Voiture supprimée avec succès'
    ]);
}
?>