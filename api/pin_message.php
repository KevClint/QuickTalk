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
$conversationId = $input['conversation_id'] ?? null;
$unpin = $input['unpin'] ?? false;

if (!$messageId || !$conversationId) {
    echo json_encode(['success' => false, 'message' => 'Message ID and Conversation ID are required']);
    exit;
}

try {
    // Check if user is participant
    if (!isConversationParticipant($pdo, $conversationId, $currentUserId)) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    if ($unpin) {
        // Unpin message
        $stmt = $pdo->prepare("DELETE FROM pinned_messages WHERE message_id = ? AND conversation_id = ?");
        $stmt->execute([$messageId, $conversationId]);
        echo json_encode(['success' => true, 'message' => 'Message unpinned']);
    } else {
        // Check current pinned count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pinned_messages WHERE conversation_id = ?");
        $stmt->execute([$conversationId]);
        $result = $stmt->fetch();
        $pinnedCount = $result['count'];
        
        // If already 3 pinned, reject
        if ($pinnedCount >= 3) {
            echo json_encode(['success' => false, 'message' => 'Maximum 3 pinned messages allowed']);
            exit;
        }
        
        // Pin message
        $stmt = $pdo->prepare("INSERT IGNORE INTO pinned_messages (conversation_id, message_id, pinned_by) VALUES (?, ?, ?)");
        $stmt->execute([$conversationId, $messageId, $currentUserId]);
        echo json_encode(['success' => true, 'message' => 'Message pinned']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error pinning message']);
}
?>
