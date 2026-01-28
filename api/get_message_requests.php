<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$currentUserId = $_SESSION['user_id'];

// Get all pending message requests for the current user
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.username,
        u.avatar,
        u.status,
        u.last_seen,
        f.created_at as request_sent_at,
        MAX(m.created_at) as last_message_time,
        m.content as last_message
    FROM friendships f
    JOIN users u ON f.sender_id = u.id
    LEFT JOIN messages m ON (
        m.conversation_id IN (
            SELECT id FROM conversations c
            WHERE (
                (SELECT COUNT(*) FROM conversation_participants cp1 
                 WHERE cp1.conversation_id = c.id AND cp1.user_id = ?) = 1
                AND
                (SELECT COUNT(*) FROM conversation_participants cp2 
                 WHERE cp2.conversation_id = c.id AND cp2.user_id = u.id) = 1
            )
        )
        AND m.deleted_for_all = FALSE
    )
    WHERE f.receiver_id = ? AND f.status = 'pending'
    GROUP BY u.id
    ORDER BY f.created_at DESC
");

$stmt->execute([$currentUserId, $currentUserId]);
$requests = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'requests' => $requests
]);
?>
