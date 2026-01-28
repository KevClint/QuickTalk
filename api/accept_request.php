<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$currentUserId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$senderId = $data['sender_id'] ?? null;

if (!$senderId) {
    echo json_encode(['success' => false, 'message' => 'Sender ID required']);
    exit;
}

try {
    // Update the friendship status to accepted
    $stmt = $pdo->prepare("
        UPDATE friendships 
        SET status = 'accepted'
        WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'
    ");
    
    $stmt->execute([$senderId, $currentUserId]);
    
    if ($stmt->rowCount() > 0) {
        // Create conversation between sender and receiver if it doesn't exist
        $conversationId = getOrCreateConversation($pdo, $senderId, $currentUserId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Request accepted',
            'conversation_id' => $conversationId
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Request not found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

