<?php
require_once '../config/database.php';
setCorsHeaders();

// Cette page est pour l'administration - ajouter une vérification d'admin si nécessaire
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    // Paramètres de pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    // Filtres
    $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
    
    // Construction de la requête
    $whereConditions = [];
    $params = [];
    
    if (!empty($status) && in_array($status, ['new', 'read', 'replied'])) {
        $whereConditions[] = 'status = ?';
        $params[] = $status;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Compter le total
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM contact_messages $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Récupérer les messages
    $stmt = $pdo->prepare("
        SELECT id, nom, email, sujet, message, status, created_at
        FROM contact_messages 
        $whereClause
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $messages = $stmt->fetchAll();
    
    // Formater les données
    $formattedMessages = array_map(function($message) {
        return [
            'id' => intval($message['id']),
            'nom' => $message['nom'],
            'email' => $message['email'],
            'sujet' => $message['sujet'],
            'message' => $message['message'],
            'status' => $message['status'],
            'created_at' => $message['created_at']
        ];
    }, $messages);
    
    echo json_encode([
        'success' => true,
        'data' => $formattedMessages,
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
        'message' => 'Erreur lors de la récupération des messages: ' . $e->getMessage()
    ]);
}
?>