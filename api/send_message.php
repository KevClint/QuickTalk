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

// Get JSON input for text content
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    // Try to get POST data for file uploads
    $input = $_POST;
}

$conversationId = $input['conversation_id'] ?? null;
$content = $input['content'] ?? '';
$recipientId = $input['recipient_id'] ?? null;

// Handle file upload
$mediaUrl = null;
$mediaType = null;

if (isset($_FILES['media']) && $_FILES['media']['size'] > 0) {
    $file = $_FILES['media'];
    $allowedMimes = [
        'image/jpeg' => 'image',
        'image/png' => 'image',
        'image/gif' => 'image',
        'audio/mpeg' => 'audio',
        'audio/wav' => 'audio',
        'video/mp4' => 'video',
        'application/pdf' => 'file'
    ];
    
    if (!isset($allowedMimes[$file['type']])) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type']);
        exit;
    }
    
    if ($file['size'] > 50000000) { // 50MB limit
        echo json_encode(['success' => false, 'message' => 'File too large (max 50MB)']);
        exit;
    }
    
    $uploadDir = '../assets/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $filename = 'media_' . time() . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $mediaUrl = 'assets/uploads/' . $filename;
        $mediaType = $allowedMimes[$file['type']];
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        exit;
    }
}

// Validate input
if (empty(trim($content)) && !$mediaUrl) {
    echo json_encode(['success' => false, 'message' => 'Message content is required']);
    exit;
}

try {
    // If recipient_id is provided, get or create conversation
    if ($recipientId && !$conversationId) {
        $conversationId = getOrCreateConversation($pdo, $currentUserId, $recipientId);
    }
    
    if (!$conversationId) {
        echo json_encode(['success' => false, 'message' => 'Conversation not found']);
        exit;
    }
    
    // Check if user is participant
    if (!isConversationParticipant($pdo, $conversationId, $currentUserId)) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, content, media_url, media_type, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$conversationId, $currentUserId, $content, $mediaUrl, $mediaType]);
    $messageId = $pdo->lastInsertId();
    
    // Update conversation timestamp
    $stmt = $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?");
    $stmt->execute([$conversationId]);
    
    // Create message status for all participants (except sender)
    // If recipient is offline, mark as 'sent', if online mark as 'delivered'
    $stmt = $pdo->prepare("
        INSERT INTO message_status (message_id, user_id, status)
        SELECT ?, cp.user_id, CASE WHEN u.status = 'offline' THEN 'sent' ELSE 'delivered' END
        FROM conversation_participants cp
        JOIN users u ON cp.user_id = u.id
        WHERE cp.conversation_id = ? AND cp.user_id != ?
    ");
    $stmt->execute([$messageId, $conversationId, $currentUserId]);
    
    // Get the created message with delivery status
    $stmt = $pdo->prepare("
        SELECT m.*, u.username, u.avatar,
               GROUP_CONCAT(CONCAT(ms.user_id, ':', ms.status) SEPARATOR ',') as delivery_status
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        LEFT JOIN message_status ms ON m.id = ms.message_id
        WHERE m.id = ?
        GROUP BY m.id
    ");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch();
    
    // Parse delivery status
    $deliveryStatus = 'sent'; // Default
    if (!empty($message['delivery_status'])) {
        // Check if any recipient is offline (status = 'sent')
        $statuses = explode(',', $message['delivery_status']);
        $hasUndelivered = false;
        foreach ($statuses as $status) {
            list($userId, $statusVal) = explode(':', $status);
            if ($statusVal === 'sent') {
                $hasUndelivered = true;
                break;
            }
        }
        $deliveryStatus = $hasUndelivered ? 'sent' : 'delivered';
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Message sent',
        'data' => [
            'id' => $message['id'],
            'conversation_id' => $message['conversation_id'],
            'sender_id' => $message['sender_id'],
            'sender_username' => $message['username'],
            'content' => $message['content'],
            'media_url' => $message['media_url'],
            'media_type' => $message['media_type'],
            'created_at' => $message['created_at'],
            'is_own' => true,
            'delivery_status' => $deliveryStatus
        ]
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send message: ' . $e->getMessage()
    ]);
}
?>
