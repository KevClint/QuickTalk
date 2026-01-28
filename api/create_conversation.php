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

$recipientId = $input['recipient_id'] ?? null;

if (!$recipientId) {
    echo json_encode(['success' => false, 'message' => 'Recipient ID is required']);
    exit;
}

if ($recipientId == $currentUserId) {
    echo json_encode(['success' => false, 'message' => 'Cannot chat with yourself']);
    exit;
}

try {
    // Get or create conversation
    $conversationId = getOrCreateConversation($pdo, $currentUserId, $recipientId);
    
    echo json_encode([
        'success' => true,
        'conversation_id' => $conversationId
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error creating conversation']);
}
?>
