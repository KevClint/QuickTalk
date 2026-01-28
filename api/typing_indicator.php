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
$isTyping = $input['is_typing'] ?? false;

if (!$conversationId) {
    echo json_encode(['success' => false, 'message' => 'Conversation ID required']);
    exit;
}

try {
    if ($isTyping) {
        // Add or update typing indicator
        $stmt = $pdo->prepare("
            INSERT INTO typing_indicators (conversation_id, user_id, timestamp)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE timestamp = NOW()
        ");
        $stmt->execute([$conversationId, $currentUserId]);
    } else {
        // Remove typing indicator
        $stmt = $pdo->prepare("
            DELETE FROM typing_indicators 
            WHERE conversation_id = ? AND user_id = ?
        ");
        $stmt->execute([$conversationId, $currentUserId]);
    }
    
    echo json_encode(['success' => true]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update typing status: ' . $e->getMessage()
    ]);
}
?>
