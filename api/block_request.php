<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

$currentUserId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$senderId = $data['sender_id'] ?? null;

if (!$senderId) {
    echo json_encode(['success' => false, 'message' => 'Sender ID required']);
    exit;
}

try {
    // Update the friendship status to blocked
    $stmt = $pdo->prepare("
        UPDATE friendships 
        SET status = 'blocked'
        WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'
    ");
    
    $stmt->execute([$senderId, $currentUserId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Request blocked'
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
