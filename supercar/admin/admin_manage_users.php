<?php
require_once '../client/api/config/database.php';
setCorsHeaders();

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            getUsers();
            break;
        case 'DELETE':
            deleteUser();
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

function getUsers() {
    global $conn;
    
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    
    // Construire la requête avec recherche optionnelle
    $whereClause = '';
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $whereClause = 'WHERE name LIKE ? OR email LIKE ?';
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam];
        $types = 'ss';
    }
    
    // Compter le total
    $countQuery = "SELECT COUNT(*) as total FROM users $whereClause";
    $countStmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    // Récupérer les utilisateurs avec leurs statistiques
    $query = "
        SELECT 
            u.id, u.name, u.email, u.phone, u.created_at,
            COUNT(td.id) as bookings_count
        FROM users u
        LEFT JOIN test_drives td ON u.id = td.user_id
        $whereClause
        GROUP BY u.id, u.name, u.email, u.phone, u.created_at
        ORDER BY u.created_at DESC 
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $conn->prepare($query);
    $allParams = array_merge($params, [$limit, $offset]);
    $allTypes = $types . 'ii';
    
    if (!empty($allParams)) {
        $stmt->bind_param($allTypes, ...$allParams);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    
    // Formater les données
    $formattedUsers = array_map(function($user) {
        return [
            'id' => intval($user['id']),
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'bookings_count' => intval($user['bookings_count']),
            'created_at' => $user['created_at'],
            'formatted_date' => date('d/m/Y', strtotime($user['created_at']))
        ];
    }, $users);
    
    echo json_encode([
        'success' => true,
        'data' => $formattedUsers,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => intval($total),
            'items_per_page' => $limit
        ]
    ]);
}

function deleteUser() {
    global $conn;
    
    $userId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($userId <= 0) {
        throw new Exception('ID utilisateur invalide');
    }
    
    // Vérifier s'il y a des demandes d'essai en cours
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM test_drives WHERE user_id = ? AND status IN ('pending', 'confirmed')");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];
    
    if ($count > 0) {
        throw new Exception('Impossible de supprimer cet utilisateur car il a des demandes d\'essai en cours');
    }
    
    // Commencer une transaction
    $conn->begin_transaction();
    
    try {
        // Supprimer d'abord les demandes d'essai de l'utilisateur
        $stmt = $conn->prepare("DELETE FROM test_drives WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        
        // Puis supprimer l'utilisateur
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        
        if (!$stmt->execute()) {
            throw new Exception('Erreur lors de la suppression de l\'utilisateur');
        }
        
        if ($stmt->affected_rows === 0) {
            throw new Exception('Utilisateur non trouvé');
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}
?>