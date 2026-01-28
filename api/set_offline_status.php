<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentUserId = getCurrentUserId();

try {
    // Update user status to offline and set last_seen
    $stmt = $pdo->prepare("
        UPDATE users 
        SET status = 'offline', last_seen = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$currentUserId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'User marked as offline'
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update status: ' . $e->getMessage()
    ]);
}
?>
