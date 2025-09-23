<?php
require_once '../config/database.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    session_start();
    
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Vous devez être connecté pour voir vos réservations');
    }
    
    $userId = $_SESSION['user_id'];
    
    // Récupérer les réservations de l'utilisateur
    $stmt = $conn->prepare("
        SELECT td.*, c.name as car_name, c.image_url, c.brand
        FROM test_drives td
        JOIN cars c ON td.car_id = c.id
        WHERE td.user_id = ?
        ORDER BY td.created_at DESC
    ");
    
    if (!$stmt) {
        throw new Exception('Erreur de préparation de la requête: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Formater les données
    $formattedBookings = array_map(function($booking) {
        return [
            'id' => intval($booking['id']),
            'car_id' => intval($booking['car_id']),
            'car_name' => $booking['car_name'],
            'car_brand' => $booking['brand'],
            'car_image' => $booking['image_url'],
            'test_date' => $booking['test_date'],
            'test_time' => $booking['test_time'],
            'phone' => $booking['phone'],
            'message' => $booking['message'],
            'status' => $booking['status'],
            'created_at' => $booking['created_at'],
            'updated_at' => $booking['updated_at']
        ];
    }, $bookings);
    
    echo json_encode([
        'success' => true,
        'data' => $formattedBookings
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>