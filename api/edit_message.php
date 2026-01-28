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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$messageId = $input['message_id'] ?? null;
$content = $input['content'] ?? '';

if (!$messageId) {
    echo json_encode(['success' => false, 'message' => 'Message ID is required']);
    exit;
}

if (empty(trim($content))) {
    echo json_encode(['success' => false, 'message' => 'Message content cannot be empty']);
    exit;
}

try {
    // Get message
    $stmt = $pdo->prepare("SELECT sender_id FROM messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch();
    
    if (!$message) {
        echo json_encode(['success' => false, 'message' => 'Message not found']);
        exit;
    }
    
    // Check if user is sender
    if ($message['sender_id'] != $currentUserId) {
        echo json_encode(['success' => false, 'message' => 'You can only edit your own messages']);
        exit;
    }
    
    // Update message
    $stmt = $pdo->prepare("UPDATE messages SET content = ?, edited_at = NOW() WHERE id = ?");
    $stmt->execute([$content, $messageId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Message edited',
        'edited_at' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error editing message']);
}
?>
