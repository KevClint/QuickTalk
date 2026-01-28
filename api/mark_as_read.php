<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentUserId = getCurrentUserId();
$input = json_decode(file_get_contents('php://input'), true);

$conversationId = $input['conversation_id'] ?? null;

if (!$conversationId) {
    echo json_encode(['success' => false, 'message' => 'Conversation ID required']);
    exit;
}

try {
    // Check if user is participant
    $stmt = $pdo->prepare("
        SELECT id FROM conversation_participants 
        WHERE conversation_id = ? AND user_id = ?
    ");
    $stmt->execute([$conversationId, $currentUserId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Update all messages in conversation to read status
    $stmt = $pdo->prepare("
        INSERT INTO message_status (message_id, user_id, status)
        SELECT m.id, ?, 'read'
        FROM messages m
        WHERE m.conversation_id = ?
        AND m.sender_id != ?
        ON DUPLICATE KEY UPDATE 
        status = 'read',
        timestamp = NOW()
    ");
    $stmt->execute([$currentUserId, $conversationId, $currentUserId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Messages marked as read'
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update status: ' . $e->getMessage()
    ]);
}
?>
