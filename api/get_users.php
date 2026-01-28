<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentUserId = getCurrentUserId();
$search = $_GET['search'] ?? '';

try {
    // Get all users except current user and blocked users
    $query = "
        SELECT 
            u.id,
            u.username,
            u.email,
            u.avatar,
            u.status,
            u.last_seen,
            (
                SELECT COUNT(*) 
                FROM blocked_users 
                WHERE (blocker_id = ? AND blocked_id = u.id)
                OR (blocker_id = u.id AND blocked_id = ?)
            ) as is_blocked
        FROM users u
        WHERE u.id != ?
    ";
    
    $params = [$currentUserId, $currentUserId, $currentUserId];
    
    if (!empty($search)) {
        $query .= " AND (u.username LIKE ? OR u.email LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $query .= " ORDER BY u.username ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Filter out blocked users and format response
    $formattedUsers = [];
    foreach ($users as $user) {
        if ($user['is_blocked'] == 0) {
            $formattedUsers[] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'avatar' => $user['avatar'],
                'status' => $user['status'],
                'last_seen' => $user['last_seen']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'users' => $formattedUsers
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch users: ' . $e->getMessage()
    ]);
}
?>
