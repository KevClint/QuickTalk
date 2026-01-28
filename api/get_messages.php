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
$afterId = $_GET['after_id'] ?? 0;
$limit = $_GET['limit'] ?? 50;

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
    
    // Get messages (including deleted ones for display purposes)
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.conversation_id,
            m.sender_id,
            m.content,
            m.media_url,
            m.media_type,
            m.is_deleted,
            m.deleted_for_all,
            m.created_at,
            m.edited_at,
            u.username,
            u.avatar,
            ms.status as read_status,
            CASE WHEN udm.id IS NOT NULL THEN 1 ELSE 0 END as deleted_for_user
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        LEFT JOIN message_status ms ON m.id = ms.message_id AND ms.user_id = ?
        LEFT JOIN user_deleted_messages udm ON m.id = udm.message_id AND udm.user_id = ?
        WHERE m.conversation_id = ? 
        AND m.id > ?
        AND (m.deleted_for_all = 0 OR m.deleted_for_all = 1)
        AND (udm.id IS NULL OR udm.id IS NOT NULL)
        ORDER BY m.created_at ASC
        LIMIT ?
    ");
    $stmt->execute([$currentUserId, $currentUserId, $conversationId, $afterId, $limit]);
    $messages = $stmt->fetchAll();
    
    // Mark messages as delivered for current user
    $messageIds = array_column($messages, 'id');
    if (!empty($messageIds)) {
        $placeholders = str_repeat('?,', count($messageIds) - 1) . '?';
        $stmt = $pdo->prepare("
            INSERT INTO message_status (message_id, user_id, status)
            VALUES " . implode(',', array_fill(0, count($messageIds), '(?, ?, ?)')) . "
            ON DUPLICATE KEY UPDATE 
            status = IF(status = 'sent', 'delivered', status),
            timestamp = NOW()
        ");
        
        $params = [];
        foreach ($messageIds as $msgId) {
            $params[] = $msgId;
            $params[] = $currentUserId;
            $params[] = 'delivered';
        }
        $stmt->execute($params);
    }
    
    // Format messages
    $formattedMessages = array_map(function($msg) use ($currentUserId) {
        // Check if message should be hidden for current user
        $isHidden = ($msg['deleted_for_all'] == 1) || ($msg['deleted_for_user'] == 1);
        
        return [
            'id' => $msg['id'],
            'conversation_id' => $msg['conversation_id'],
            'sender_id' => $msg['sender_id'],
            'sender_username' => $msg['username'],
            'sender_avatar' => $msg['avatar'],
            'content' => $msg['content'],
            'media_url' => $msg['media_url'],
            'media_type' => $msg['media_type'],
            'created_at' => $msg['created_at'],
            'edited_at' => $msg['edited_at'],
            'is_own' => $msg['sender_id'] == $currentUserId,
            'read_status' => $msg['read_status'],
            'is_deleted' => $isHidden,
            'deleted_for_all' => $msg['deleted_for_all'] == 1
        ];
    }, $messages);
    
    echo json_encode([
        'success' => true,
        'messages' => $formattedMessages,
        'count' => count($formattedMessages)
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch messages: ' . $e->getMessage()
    ]);
}
?>
