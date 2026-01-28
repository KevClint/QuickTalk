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
    // Get all conversations for current user with last message
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.type,
            c.name,
            c.updated_at,
            (
                SELECT content 
                FROM messages 
                WHERE conversation_id = c.id 
                ORDER BY created_at DESC 
                LIMIT 1
            ) as last_message,
            (
                SELECT created_at 
                FROM messages 
                WHERE conversation_id = c.id 
                ORDER BY created_at DESC 
                LIMIT 1
            ) as last_message_time,
            (
                SELECT sender_id
                FROM messages 
                WHERE conversation_id = c.id 
                ORDER BY created_at DESC 
                LIMIT 1
            ) as last_message_sender_id,
            (
                SELECT COUNT(*) 
                FROM messages m
                LEFT JOIN message_status ms ON m.id = ms.message_id AND ms.user_id = ?
                WHERE m.conversation_id = c.id 
                AND m.sender_id != ?
                AND (ms.status IS NULL OR ms.status != 'read')
            ) as unread_count
        FROM conversations c
        INNER JOIN conversation_participants cp ON c.id = cp.conversation_id
        WHERE cp.user_id = ?
        ORDER BY c.updated_at DESC
    ");
    $stmt->execute([$currentUserId, $currentUserId, $currentUserId]);
    $conversations = $stmt->fetchAll();
    
    // Get participant details for each conversation
    $formattedConversations = [];
    foreach ($conversations as $conv) {
        // Get other participants
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.avatar, u.status, u.last_seen
            FROM users u
            INNER JOIN conversation_participants cp ON u.id = cp.user_id
            WHERE cp.conversation_id = ?
            AND u.id != ?
        ");
        $stmt->execute([$conv['id'], $currentUserId]);
        $participants = $stmt->fetchAll();
        
        // For direct chats, use other user's info
        if ($conv['type'] === 'direct' && count($participants) > 0) {
            $otherUser = $participants[0];
            $displayName = $otherUser['username'];
            $displayAvatar = $otherUser['avatar'];
            $displayStatus = $otherUser['status'];
        } else {
            // For group chats, use conversation name
            $displayName = $conv['name'] ?: 'Group Chat';
            $displayAvatar = 'group';
            $displayStatus = count($participants) . ' members';
        }
        
        // Get last message sender name if not current user
        $lastMessagePrefix = '';
        if ($conv['last_message_sender_id']) {
            if ($conv['last_message_sender_id'] == $currentUserId) {
                $lastMessagePrefix = 'You: ';
            } elseif ($conv['type'] === 'group') {
                $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$conv['last_message_sender_id']]);
                $sender = $stmt->fetch();
                if ($sender) {
                    $lastMessagePrefix = $sender['username'] . ': ';
                }
            }
        }
        
        $formattedConversations[] = [
            'id' => $conv['id'],
            'type' => $conv['type'],
            'name' => $displayName,
            'avatar' => $displayAvatar,
            'status' => $displayStatus,
            'last_message' => $lastMessagePrefix . ($conv['last_message'] ?? 'No messages yet'),
            'last_message_time' => $conv['last_message_time'],
            'unread_count' => (int)$conv['unread_count'],
            'participants' => $participants
        ];
    }
    
    echo json_encode([
        'success' => true,
        'conversations' => $formattedConversations
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch conversations: ' . $e->getMessage()
    ]);
}
?>
