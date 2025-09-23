<?php
require_once '../config/database.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    $carId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($carId <= 0) {
        throw new Exception('ID de voiture invalide');
    }
    
    // Récupérer les détails de la voiture
    $stmt = $conn->prepare("
        SELECT id, name, brand, price, image_url, power, max_speed, acceleration, description, available, created_at
        FROM cars 
        WHERE id = ?
    ");
    $stmt->bind_param('i', $carId);
    $stmt->execute();
    $result = $stmt->get_result();
    $car = $result->fetch_assoc();
    
    if (!$car) {
        throw new Exception('Voiture non trouvée');
    }
    
    // Formater les données
    $formattedCar = [
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
        'available' => (bool)$car['available'],
        'created_at' => $car['created_at']
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $formattedCar
    ]);
    
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>