<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentUserId = getCurrentUserId();
$conversationId = $_GET['conversation_id'] ?? null;

if (!$conversationId) {
    echo json_encode(['success' => false, 'message' => 'Conversation ID required']);
    exit;
}

try {
    // Clean up old typing indicators (older than 10 seconds)
    $stmt = $pdo->prepare("
        DELETE FROM typing_indicators 
        WHERE timestamp < DATE_SUB(NOW(), INTERVAL 10 SECOND)
    ");
    $stmt->execute();
    
    // Get current typing users (excluding self)
    $stmt = $pdo->prepare("
        SELECT u.id, u.username
        FROM typing_indicators ti
        JOIN users u ON ti.user_id = u.id
        WHERE ti.conversation_id = ?
        AND ti.user_id != ?
        AND ti.timestamp >= DATE_SUB(NOW(), INTERVAL 10 SECOND)
    ");
    $stmt->execute([$conversationId, $currentUserId]);
    $typingUsers = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'typing_users' => $typingUsers
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to get typing status: ' . $e->getMessage()
    ]);
}
?>
