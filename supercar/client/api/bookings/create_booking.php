<?php
require_once '../config/database.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    session_start();
    
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Vous devez être connecté pour faire une demande d\'essai');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Données JSON invalides');
    }
    
    $userId = $_SESSION['user_id'];
    $carId = intval($input['car'] ?? 0);
    $testDate = sanitizeInput($input['date'] ?? '');
    $testTime = sanitizeInput($input['time'] ?? '');
    $phone = sanitizeInput($input['phone'] ?? '');
    $message = sanitizeInput($input['message'] ?? '');
    
    // Validation des données
    if ($carId <= 0) {
        throw new Exception('Veuillez sélectionner une voiture');
    }
    
    if (empty($testDate)) {
        throw new Exception('Veuillez sélectionner une date');
    }
    
    if (empty($testTime)) {
        throw new Exception('Veuillez sélectionner une heure');
    }
    
    if (empty($phone)) {
        throw new Exception('Numéro de téléphone requis');
    }
    
    if (!validatePhone($phone)) {
        throw new Exception('Format de téléphone invalide');
    }
    
    // Vérifier que la date n'est pas dans le passé
    $selectedDate = new DateTime($testDate);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($selectedDate < $today) {
        throw new Exception('La date sélectionnée ne peut pas être dans le passé');
    }
    
    // Vérifier que la voiture existe et est disponible
    $stmt = $conn->prepare("SELECT name, available FROM cars WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Erreur de préparation de la requête: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $carId);
    $stmt->execute();
    $result = $stmt->get_result();
    $car = $result->fetch_assoc();
    $stmt->close();
    
    if (!$car) {
        throw new Exception('Voiture non trouvée');
    }
    
    if (!$car['available']) {
        throw new Exception('Cette voiture n\'est pas disponible pour les essais');
    }
    
    // Vérifier s'il n'y a pas déjà une réservation pour cette date/heure/voiture
    $stmt = $conn->prepare("
        SELECT id FROM test_drives 
        WHERE car_id = ? AND test_date = ? AND test_time = ? AND status IN ('pending', 'confirmed')
    ");
    if (!$stmt) {
        throw new Exception('Erreur de préparation de la requête: ' . $conn->error);
    }
    
    $stmt->bind_param("iss", $carId, $testDate, $testTime);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingBooking = $result->fetch_assoc();
    $stmt->close();
    
    if ($existingBooking) {
        throw new Exception('Ce créneau est déjà réservé pour cette voiture');
    }
    
    // Vérifier si l'utilisateur n'a pas déjà une demande en attente pour cette voiture
    $stmt = $conn->prepare("
        SELECT id FROM test_drives 
        WHERE user_id = ? AND car_id = ? AND status IN ('pending', 'confirmed')
    ");
    if (!$stmt) {
        throw new Exception('Erreur de préparation de la requête: ' . $conn->error);
    }
    
    $stmt->bind_param("ii", $userId, $carId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userBooking = $result->fetch_assoc();
    $stmt->close();
    
    if ($userBooking) {
        throw new Exception('Vous avez déjà une demande d\'essai en cours pour cette voiture');
    }
    
    // Créer la demande d'essai
    $stmt = $conn->prepare("
        INSERT INTO test_drives (user_id, car_id, test_date, test_time, phone, message) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        throw new Exception('Erreur de préparation de la requête: ' . $conn->error);
    }
    
    $stmt->bind_param("iissss", $userId, $carId, $testDate, $testTime, $phone, $message);
    
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de l\'insertion: ' . $stmt->error);
    }
    
    $bookingId = $conn->insert_id;
    $stmt->close();
    
    // Récupérer les détails de la réservation
    $stmt = $conn->prepare("
        SELECT td.*, c.name as car_name, u.name as user_name, u.email as user_email
        FROM test_drives td
        JOIN cars c ON td.car_id = c.id
        JOIN users u ON td.user_id = u.id
        WHERE td.id = ?
    ");
    if (!$stmt) {
        throw new Exception('Erreur de préparation de la requête: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();
    
    if (!$booking) {
        throw new Exception('Erreur lors de la récupération des détails de la réservation');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Demande d\'essai créée avec succès',
        'data' => [
            'id' => intval($booking['id']),
            'car_name' => $booking['car_name'],
            'test_date' => $booking['test_date'],
            'test_time' => $booking['test_time'],
            'status' => $booking['status'],
            'created_at' => $booking['created_at']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>