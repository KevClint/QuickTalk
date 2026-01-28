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

$username = $_POST['username'] ?? null;
$email = $_POST['email'] ?? null;
$avatar = $_FILES['avatar'] ?? null;

$updates = [];
$values = [];

try {
    // Check username
    if ($username) {
        if (strlen($username) < 3) {
            echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters']);
            exit;
        }
        
        // Check if username already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $currentUserId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username already taken']);
            exit;
        }
        
        $updates[] = "username = ?";
        $values[] = $username;
    }
    
    // Check email
    if ($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit;
        }
        
        // Check if email already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $currentUserId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already in use']);
            exit;
        }
        
        $updates[] = "email = ?";
        $values[] = $email;
    }
    
    // Handle avatar upload
    if ($avatar && $avatar['size'] > 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($avatar['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image type']);
            exit;
        }
        
        if ($avatar['size'] > 5000000) { // 5MB limit
            echo json_encode(['success' => false, 'message' => 'Image too large (max 5MB)']);
            exit;
        }
        
        $uploadDir = '../assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $filename = 'avatar_' . $currentUserId . '_' . time() . '.' . pathinfo($avatar['name'], PATHINFO_EXTENSION);
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($avatar['tmp_name'], $filepath)) {
            $updates[] = "avatar = ?";
            $values[] = 'uploads/' . $filename;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
            exit;
        }
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No updates provided']);
        exit;
    }
    
    $values[] = $currentUserId;
    $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute($values);
    
    // Get updated user
    $stmt = $pdo->prepare("SELECT id, username, email, avatar FROM users WHERE id = ?");
    $stmt->execute([$currentUserId]);
    $user = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated',
        'user' => $user
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error updating profile: ' . $e->getMessage()]);
}
?>
