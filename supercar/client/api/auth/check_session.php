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

    if (isset($_SESSION['user_id'])) {
        $userId = (int) $_SESSION['user_id'];

        $stmt = $conn->prepare("SELECT id, name, email, phone FROM users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            echo json_encode([
                'success' => true,
                'authenticated' => true,
                'user' => $user
            ]);
        } else {
            // Utilisateur supprimé -> nettoyer la session
            session_unset();
            session_destroy();
            echo json_encode([
                'success' => true,
                'authenticated' => false
            ]);
        }
    } else {
        echo json_encode([
            'success' => true,
            'authenticated' => false
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la vérification de session: ' . $e->getMessage()
    ]);
}
?>
