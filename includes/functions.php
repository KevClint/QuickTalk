<?php
// Sanitize output
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Format timestamp for display
function formatTime($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $time);
    }
}

// Get or create direct conversation between two users
function getOrCreateConversation($pdo, $userId1, $userId2) {
    // Check if conversation already exists
    $stmt = $pdo->prepare("
        SELECT c.id 
        FROM conversations c
        INNER JOIN conversation_participants cp1 ON c.id = cp1.conversation_id
        INNER JOIN conversation_participants cp2 ON c.id = cp2.conversation_id
        WHERE c.type = 'direct'
        AND cp1.user_id = ?
        AND cp2.user_id = ?
        AND (
            SELECT COUNT(*) FROM conversation_participants 
            WHERE conversation_id = c.id
        ) = 2
    ");
    $stmt->execute([$userId1, $userId2]);
    $conversation = $stmt->fetch();
    
    if ($conversation) {
        return $conversation['id'];
    }
    
    // Create new conversation
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO conversations (type, created_by) VALUES ('direct', ?)");
        $stmt->execute([$userId1]);
        $conversationId = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)");
        $stmt->execute([$conversationId, $userId1]);
        $stmt->execute([$conversationId, $userId2]);
        
        $pdo->commit();
        return $conversationId;
    } catch(Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Create group conversation
function createGroupConversation($pdo, $creatorId, $name, $participantIds) {
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO conversations (type, name, created_by) VALUES ('group', ?, ?)");
        $stmt->execute([$name, $creatorId]);
        $conversationId = $pdo->lastInsertId();
        
        // Add creator as admin
        $stmt = $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id, role) VALUES (?, ?, 'admin')");
        $stmt->execute([$conversationId, $creatorId]);
        
        // Add other participants
        $stmt = $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)");
        foreach ($participantIds as $userId) {
            if ($userId != $creatorId) {
                $stmt->execute([$conversationId, $userId]);
            }
        }
        
        $pdo->commit();
        return $conversationId;
    } catch(Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Check if user is participant in conversation
function isConversationParticipant($pdo, $conversationId, $userId) {
    $stmt = $pdo->prepare("
        SELECT id FROM conversation_participants 
        WHERE conversation_id = ? AND user_id = ?
    ");
    $stmt->execute([$conversationId, $userId]);
    return $stmt->fetch() !== false;
}

// Upload file
function uploadFile($file, $uploadDir = '../assets/uploads/') {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'application/pdf'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload failed'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large (max 10MB)'];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $mediaType = 'file';
        if (strpos($file['type'], 'image') !== false) {
            $mediaType = 'image';
        } elseif (strpos($file['type'], 'video') !== false) {
            $mediaType = 'video';
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => 'assets/uploads/' . $filename,
            'media_type' => $mediaType
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to save file'];
}

// Get unread message count for user
function getUnreadCount($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM messages m
        INNER JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id
        LEFT JOIN message_status ms ON m.id = ms.message_id AND ms.user_id = ?
        WHERE cp.user_id = ?
        AND m.sender_id != ?
        AND (ms.status IS NULL OR ms.status != 'read')
    ");
    $stmt->execute([$userId, $userId, $userId]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}
?>
