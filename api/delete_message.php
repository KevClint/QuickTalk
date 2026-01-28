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
$deleteForAll = $input['delete_for_all'] ?? false;

if (!$messageId) {
    echo json_encode(['success' => false, 'message' => 'Message ID is required']);
    exit;
}

try {
    // Get message
    $stmt = $pdo->prepare("SELECT sender_id, conversation_id FROM messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch();
    
    if (!$message) {
        echo json_encode(['success' => false, 'message' => 'Message not found']);
        exit;
    }
    
    // Check if user is sender or admin (for delete for all)
    if ($deleteForAll && $message['sender_id'] != $currentUserId) {
        echo json_encode(['success' => false, 'message' => 'Only sender can delete for everyone']);
        exit;
    }
    
    if ($deleteForAll) {
        // Delete for everyone
        $stmt = $pdo->prepare("UPDATE messages SET deleted_for_all = TRUE WHERE id = ?");
        $stmt->execute([$messageId]);
    } else {
        // Delete for you only
        $stmt = $pdo->prepare("INSERT IGNORE INTO user_deleted_messages (message_id, user_id) VALUES (?, ?)");
        $stmt->execute([$messageId, $currentUserId]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Message deleted']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error deleting message']);
}
?>
