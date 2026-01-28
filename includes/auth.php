<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user data
function getCurrentUser($pdo) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $pdo->prepare("
        SELECT id, username, email, avatar, status, last_seen, created_at 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([getCurrentUserId()]);
    return $stmt->fetch();
}

// Login user
function loginUser($pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Update user status to online
        $updateStmt = $pdo->prepare("UPDATE users SET status = 'online', last_seen = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email']
            ]
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Invalid username or password'
    ];
}

// Register new user
function registerUser($pdo, $username, $email, $password) {
    // Validate input
    if (strlen($username) < 3) {
        return ['success' => false, 'message' => 'Username must be at least 3 characters'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
    
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters'];
    }
    
    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }
    
    // Hash password and insert user
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $passwordHash]);
        
        return [
            'success' => true,
            'message' => 'Registration successful',
            'user_id' => $pdo->lastInsertId()
        ];
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

// Logout user
function logoutUser($pdo) {
    if (isLoggedIn()) {
        // Update user status to offline
        $stmt = $pdo->prepare("UPDATE users SET status = 'offline', last_seen = NOW() WHERE id = ?");
        $stmt->execute([getCurrentUserId()]);
    }
    
    session_destroy();
    return ['success' => true, 'message' => 'Logged out successfully'];
}

// Require login (redirect if not logged in)
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}
?>
