<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: chat.php');
    exit;
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $result = loginUser($pdo, $username, $password);
        
        if ($result['success']) {
            header('Location: chat.php');
            exit;
        } else {
            $error = $result['message'];
            // Clear any existing session on login failure
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
                session_start();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickTalk - Login</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <script>
        // Load theme on page load
        const savedTheme1 = localStorage.getItem('theme');
        if (savedTheme1 === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
    <div class="login-container">
        <div class="login-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div></div>
                <button id="themeBtn" class="theme-toggle" aria-label="Toggle Theme" title="Toggle Dark Mode">
                    🌙
                </button>
            </div>
            <h1>QuickTalk</h1>
            <p class="subtitle">Connect with your friends</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= escape($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= escape($success) ?></div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Enter username or email" autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter password" autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <p class="text-center" style="margin-top: 20px;">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        </div>
    </div>
    
    <script>
        // Dark mode toggle
        document.getElementById('themeBtn').addEventListener('click', function() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark-mode');
            
            if (isDark) {
                html.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
                this.textContent = '🌙';
            } else {
                html.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
                this.textContent = '☀️';
            }
        });
        
        // Set initial button text based on saved theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.getElementById('themeBtn').textContent = '☀️';
        }
    </script>
</body>
</html>
