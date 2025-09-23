<?php
require_once '../client/api/config/database.php';
setCorsHeaders();

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            getBookings();
            break;
        case 'PUT':
            updateBookingStatus();
            break;
        case 'DELETE':
            deleteBooking();
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

function getBookings() {
    global $conn;
    
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
    $recent = isset($_GET['recent']) ? (bool)$_GET['recent'] : false;
    
    // Construire la clause WHERE
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if (!empty($status) && in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
        $whereConditions[] = 'td.status = ?';
        $params[] = $status;
        $types .= 's';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Si c'est pour le dashboard (recent), limiter à 5 et prendre les plus récents
    if ($recent) {
        $limit = 5;
        $offset = 0;
    }
    
    // Compter le total
    $countQuery = "SELECT COUNT(*) as total FROM test_drives td $whereClause";
    $countStmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    // Récupérer les demandes d'essai avec les informations utilisateur et voiture
    $query = "
        SELECT 
            td.id, td.test_date, td.test_time, td.phone, td.message, td.status, td.created_at, td.updated_at,
            u.id as user_id, u.name as user_name, u.email as user_email,
            c.id as car_id, c.name as car_name, c.brand as car_brand, c.image_url as car_image
        FROM test_drives td
        JOIN users u ON td.user_id = u.id
        JOIN cars c ON td.car_id = c.id
        $whereClause
        ORDER BY td.created_at DESC 
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $conn->prepare($query);
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
    
    // Formater les données
    $formattedBookings = array_map(function($booking) {
        return [
            'id' => intval($booking['id']),
            'user' => [
                'id' => intval($booking['user_id']),
                'name' => $booking['user_name'],
                'email' => $booking['user_email']
            ],
            'car' => [
                'id' => intval($booking['car_id']),
                'name' => $booking['car_name'],
                'brand' => $booking['car_brand'],
                'image' => $booking['car_image']
            ],
            'test_date' => $booking['test_date'],
            'test_time' => $booking['test_time'],
            'phone' => $booking['phone'],
            'message' => $booking['message'],
            'status' => $booking['status'],
            'status_label' => getStatusLabel($booking['status']),
            'created_at' => $booking['created_at'],
            'updated_at' => $booking['updated_at']
        ];
    }, $bookings);
    
    echo json_encode([
        'success' => true,
        'data' => $formattedBookings,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => intval($total),
            'items_per_page' => $limit
        ]
    ]);
}

function updateBookingStatus() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id']) || !isset($input['status'])) {
        throw new Exception('ID et statut requis');
    }
    
    $bookingId = intval($input['id']);
    $status = sanitizeInput($input['status']);
    
    if (!in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
        throw new Exception('Statut invalide');
    }
    
    $stmt = $conn->prepare("UPDATE test_drives SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param('si', $status, $bookingId);
    
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de la mise à jour du statut');
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Demande d\'essai non trouvée');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Statut mis à jour avec succès'
    ]);
}

function deleteBooking() {
    global $conn;
    
    $bookingId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($bookingId <= 0) {
        throw new Exception('ID de demande invalide');
    }
    
    $stmt = $conn->prepare("DELETE FROM test_drives WHERE id = ?");
    $stmt->bind_param('i', $bookingId);
    
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de la suppression');
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Demande d\'essai non trouvée');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Demande d\'essai supprimée avec succès'
    ]);
}

function getStatusLabel($status) {
    $labels = [
        'pending' => 'En attente',
        'confirmed' => 'Confirmé',
        'completed' => 'Terminé',
        'cancelled' => 'Annulé'
    ];
    
    return $labels[$status] ?? $status;
}
?>