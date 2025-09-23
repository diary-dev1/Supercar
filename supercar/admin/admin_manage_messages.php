<?php
require_once '../client/api/config/database.php';
setCorsHeaders();

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['id'])) {
                getMessage();
            } else {
                getMessages();
            }
            break;
        case 'PUT':
            updateMessageStatus();
            break;
        case 'DELETE':
            deleteMessage();
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

function getMessages() {
    global $conn;
    
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
    
    // Construire la clause WHERE
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if (!empty($status) && in_array($status, ['new', 'read', 'replied'])) {
        $whereConditions[] = 'status = ?';
        $params[] = $status;
        $types .= 's';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Compter le total
    $countQuery = "SELECT COUNT(*) as total FROM contact_messages $whereClause";
    $countStmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    // Récupérer les messages
    $query = "
        SELECT id, nom, email, sujet, message, status, created_at
        FROM contact_messages 
        $whereClause
        ORDER BY created_at DESC 
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
    $messages = $result->fetch_all(MYSQLI_ASSOC);
    
    // Formater les données
    $formattedMessages = array_map(function($message) {
        return [
            'id' => intval($message['id']),
            'nom' => $message['nom'],
            'email' => $message['email'],
            'sujet' => $message['sujet'],
            'message' => strlen($message['message']) > 100 ? 
                substr($message['message'], 0, 100) . '...' : 
                $message['message'],
            'full_message' => $message['message'],
            'status' => $message['status'],
            'status_label' => getStatusLabel($message['status']),
            'created_at' => $message['created_at'],
            'formatted_date' => date('d/m/Y H:i', strtotime($message['created_at']))
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
}

function getMessage() {
    global $conn;
    
    $messageId = intval($_GET['id']);
    
    if ($messageId <= 0) {
        throw new Exception('ID de message invalide');
    }
    
    $stmt = $conn->prepare("SELECT id, nom, email, sujet, message, status, created_at FROM contact_messages WHERE id = ?");
    $stmt->bind_param('i', $messageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $message = $result->fetch_assoc();
    
    if (!$message) {
        throw new Exception('Message non trouvé');
    }
    
    // Marquer comme lu si c'était nouveau
    if ($message['status'] === 'new') {
        $updateStmt = $conn->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
        $updateStmt->bind_param('i', $messageId);
        $updateStmt->execute();
        $message['status'] = 'read';
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => intval($message['id']),
            'nom' => $message['nom'],
            'email' => $message['email'],
            'sujet' => $message['sujet'],
            'message' => $message['message'],
            'status' => $message['status'],
            'status_label' => getStatusLabel($message['status']),
            'created_at' => $message['created_at'],
            'formatted_date' => date('d/m/Y H:i', strtotime($message['created_at']))
        ]
    ]);
}

function updateMessageStatus() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id']) || !isset($input['status'])) {
        throw new Exception('ID et statut requis');
    }
    
    $messageId = intval($input['id']);
    $status = sanitizeInput($input['status']);
    
    if (!in_array($status, ['new', 'read', 'replied'])) {
        throw new Exception('Statut invalide');
    }
    
    $stmt = $conn->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $messageId);
    
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de la mise à jour du statut');
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Message non trouvé');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Statut mis à jour avec succès'
    ]);
}

function deleteMessage() {
    global $conn;
    
    $messageId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($messageId <= 0) {
        throw new Exception('ID de message invalide');
    }
    
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->bind_param('i', $messageId);
    
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de la suppression');
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Message non trouvé');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Message supprimé avec succès'
    ]);
}

function getStatusLabel($status) {
    $labels = [
        'new' => 'Nouveau',
        'read' => 'Lu',
        'replied' => 'Répondu'
    ];
    
    return $labels[$status] ?? $status;
}
?>