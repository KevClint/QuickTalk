<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin($pdo);

$currentUserId = $_SESSION['user_id'];
$recipientId = $_GET['recipient_id'] ?? null;

if (!$recipientId) {
    echo json_encode(['success' => false, 'message' => 'Recipient ID required']);
    exit;
}

// Check friendship status (both directions)
$stmt = $pdo->prepare("
    SELECT status FROM friendships 
    WHERE (sender_id = ? AND receiver_id = ?) 
       OR (sender_id = ? AND receiver_id = ?)
    LIMIT 1
");

$stmt->execute([$currentUserId, $recipientId, $recipientId, $currentUserId]);
$friendship = $stmt->fetch();

if ($friendship) {
    echo json_encode([
        'success' => true,
        'status' => $friendship['status']
    ]);
} else {
    echo json_encode([
        'success' => true,
        'status' => 'none'
    ]);
}
?>
