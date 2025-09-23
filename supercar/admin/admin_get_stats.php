<?php
require_once '../client/api/config/database.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    // Compter les voitures
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cars");
    $stmt->execute();
    $totalCars = $stmt->get_result()->fetch_assoc()['total'];

    // Compter les utilisateurs
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users");
    $stmt->execute();
    $totalUsers = $stmt->get_result()->fetch_assoc()['total'];

    // Compter les demandes d'essai
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM test_drives");
    $stmt->execute();
    $totalBookings = $stmt->get_result()->fetch_assoc()['total'];

    // Compter les messages
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM contact_messages");
    $stmt->execute();
    $totalMessages = $stmt->get_result()->fetch_assoc()['total'];

    // Demandes d'essai en attente
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM test_drives WHERE status = 'pending'");
    $stmt->execute();
    $pendingBookings = $stmt->get_result()->fetch_assoc()['total'];

    // Messages non lus
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM contact_messages WHERE status = 'new'");
    $stmt->execute();
    $unreadMessages = $stmt->get_result()->fetch_assoc()['total'];

    echo json_encode([
        'success' => true,
        'data' => [
            'total_cars' => intval($totalCars),
            'total_users' => intval($totalUsers),
            'total_bookings' => intval($totalBookings),
            'total_messages' => intval($totalMessages),
            'pending_bookings' => intval($pendingBookings),
            'unread_messages' => intval($unreadMessages)
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()
    ]);
}
?>