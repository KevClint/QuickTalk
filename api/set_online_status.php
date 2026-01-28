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
    $pdo->beginTransaction();
    
    // Update user status to online and set last_seen
    $stmt = $pdo->prepare("
        UPDATE users 
        SET status = 'online', last_seen = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$currentUserId]);
    
    // Update all 'sent' messages to 'delivered' for this user
    // These are messages where this user is the recipient and status is 'sent'
    $stmt = $pdo->prepare("
        UPDATE message_status ms
        JOIN messages m ON ms.message_id = m.id
        SET ms.status = 'delivered'
        WHERE ms.user_id = ? AND ms.status = 'sent'
    ");
    $stmt->execute([$currentUserId]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'User marked as online'
    ]);
    
} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update status: ' . $e->getMessage()
    ]);
}
?>
