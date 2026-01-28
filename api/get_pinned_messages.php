<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

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
    // Check if user is participant
    if (!isConversationParticipant($pdo, $conversationId, $currentUserId)) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Get pinned messages
    $stmt = $pdo->prepare("
        SELECT 
            pm.id as pin_id,
            m.id as message_id,
            m.content,
            m.media_url,
            m.media_type,
            m.created_at,
            u.username,
            u.avatar,
            pm.pinned_at,
            pbu.username as pinned_by_username
        FROM pinned_messages pm
        JOIN messages m ON pm.message_id = m.id
        JOIN users u ON m.sender_id = u.id
        LEFT JOIN users pbu ON pm.pinned_by = pbu.id
        WHERE pm.conversation_id = ?
        ORDER BY pm.pinned_at DESC
    ");
    $stmt->execute([$conversationId]);
    $pinnedMessages = $stmt->fetchAll();
    
    $formattedMessages = array_map(function($msg) {
        return [
            'pin_id' => $msg['pin_id'],
            'message_id' => $msg['message_id'],
            'content' => $msg['content'],
            'media_url' => $msg['media_url'],
            'media_type' => $msg['media_type'],
            'created_at' => $msg['created_at'],
            'sender_username' => $msg['username'],
            'sender_avatar' => $msg['avatar'],
            'pinned_at' => $msg['pinned_at'],
            'pinned_by' => $msg['pinned_by_username']
        ];
    }, $pinnedMessages);
    
    echo json_encode([
        'success' => true,
        'pinned_messages' => $formattedMessages,
        'count' => count($formattedMessages)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching pinned messages']);
}
?>
