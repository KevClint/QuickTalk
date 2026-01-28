<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

$currentUserId = $_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);
$recipientId = $input['recipient_id'] ?? null;

if (!$recipientId) {
    echo json_encode(['success' => false, 'message' => 'Recipient ID required']);
    exit;
}

if ($recipientId == $currentUserId) {
    echo json_encode(['success' => false, 'message' => 'Cannot send request to yourself']);
    exit;
}

try {
    // Check if request already exists
    $stmt = $pdo->prepare("
        SELECT id, status FROM friendships 
        WHERE (sender_id = ? AND receiver_id = ?) 
        OR (sender_id = ? AND receiver_id = ?)
    ");
    $stmt->execute([$currentUserId, $recipientId, $recipientId, $currentUserId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $status = $existing['status'];
        if ($status === 'pending') {
            echo json_encode(['success' => false, 'message' => 'Request already pending']);
        } else if ($status === 'accepted') {
            echo json_encode(['success' => false, 'message' => 'You are already friends']);
        } else if ($status === 'blocked') {
            echo json_encode(['success' => false, 'message' => 'This user is blocked']);
        }
        exit;
    }
    
    // Create new friend request
    $stmt = $pdo->prepare("
        INSERT INTO friendships (sender_id, receiver_id, status, created_at, updated_at)
        VALUES (?, ?, 'pending', NOW(), NOW())
    ");
    $stmt->execute([$currentUserId, $recipientId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Friend request sent'
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error sending request: ' . $e->getMessage()
    ]);
}
?>
