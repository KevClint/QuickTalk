<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();
$currentUser = getCurrentUser($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Chat</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-layout" id="appLayout">
        
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="user-profile-summary">
                    <div class="avatar-large">
                        <?php if($currentUser['avatar']): ?>
                            <img src="<?= escape($currentUser['avatar']) ?>" alt="Me">
                        <?php else: ?>
                            <span><?= strtoupper(substr($currentUser['username'], 0, 1)) ?></span>
                        <?php endif; ?>
                        <div class="status-dot online"></div>
                    </div>
                    <div class="profile-info">
                        <h3><?= escape($currentUser['username']) ?></h3>
                        <p>Chats</p>
                    </div>
                </div>
                
                <div class="sidebar-actions">
                    <button class="btn-circle" id="themeBtn" aria-label="Toggle Theme">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path></svg>
                    </button>
                    <button class="btn-circle" id="profileBtn" aria-label="Settings">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </button>
                    <button class="btn-circle" id="logoutBtn" aria-label="Logout">
                         <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </div>
            </div>

            <div class="search-container">
                <div class="search-input-wrapper">
                    <svg class="search-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" id="searchUsers" placeholder="Search Messenger">
                </div>
            </div>

            <div class="pill-tabs">
                <button class="pill-tab active" data-tab="conversations">Chats</button>
                <button class="pill-tab" data-tab="requests">Requests</button>
                <button class="pill-tab" data-tab="people">People</button>
            </div>

            <div class="list-container custom-scrollbar">
                <div id="conversations-tab" class="tab-content active">
                    </div>
                <div id="requests-tab" class="tab-content">
                    </div>
                <div id="people-tab" class="tab-content">
                    </div>
            </div>
        </aside>

        <main class="chat-area" id="chatArea">
            <div class="empty-state" id="welcomeScreen">
                <div class="empty-content">
                    <h2>Welcome, <?= escape($currentUser['username']) ?></h2>
                    <p>Select a conversation to start messaging.</p>
                </div>
            </div>

            <div class="chat-view" id="chatWindow" style="display: none;">
                <header class="chat-header">
                    <div class="header-left">
                        <button class="btn-icon mobile-back" id="backToSidebar">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
                        <div class="chat-avatar-wrapper">
                            <div class="avatar-header" id="chatAvatar"></div>
                            <div class="status-dot-header" id="chatStatusDot"></div>
                        </div>
                        <div class="header-info">
                            <h3 id="chatUsername">User</h3>
                            <span id="chatStatus">Active now</span>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="btn-icon color-primary" id="pinnedBtn" title="Pinned Messages">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M16 12V4h2V2h-2V0h-2v2h-2v2h2v8H8V7h2V5H8V3h2V1H8v2H6v2h2v8H4v-8h2V5H4V3h2V1H4v2H2v2h2v8c0 1.1.9 2 2 2h8v8h-2v2h2v2h2v-2h2v-2h-2v-8h8c1.1 0 2-.9 2-2V5h2V3h-2V1h-2v2h-2v2h2v8h-8Z"/></svg>
                        </button>
                        <button class="btn-icon color-primary" id="chatInfoBtn">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                        </button>
                    </div>
                </header>

                <div class="pinned-panel" id="pinnedPanel" style="display: none;">
                    <div class="pinned-header">
                        <h4>📌 Pinned Messages <span id="pinnedCount">0</span></h4>
                        <button class="close-pinned" id="closePinnedBtn" title="Close pinned messages">✕</button>
                    </div>
                    <div class="pinned-messages" id="pinnedMessages"></div>
                </div>

                <div class="messages-wrapper custom-scrollbar" id="messagesContainer">
                    </div>

                <div class="typing-indicator" id="typingIndicator" style="display:none">
                    <div class="typing-bubble">
                        <div class="dot"></div><div class="dot"></div><div class="dot"></div>
                    </div>
                </div>

                <footer class="chat-footer" id="messageFooter">
                    <button class="btn-icon-footer" id="attachBtn">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5a2.5 2.5 0 015 0v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5a2.5 2.5 0 005 0V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6h-1.5z"/></svg>
                    </button>
                    <input type="file" id="fileInput" hidden>
                    
                    <div class="input-wrapper">
                        <textarea id="messageInput" placeholder="Aa" rows="1"></textarea>
                    </div>
                    
                    <button class="btn-send" id="sendBtn">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </button>
                </footer>
            </div>
        </main>
    </div>

    <script>
        const CONFIG = {
            userId: <?= $currentUser['id'] ?>,
            username: <?= json_encode($currentUser['username']) ?>,
            avatar: <?= json_encode($currentUser['avatar']) ?>,
            email: <?= json_encode($currentUser['email']) ?>
        };
        // Set global variables for profile modal
        window.currentUsername = CONFIG.username;
        window.currentUserEmail = CONFIG.email;
        window.currentUserAvatar = CONFIG.avatar;
    </script>

    <!-- Profile Modal -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Profile</h2>
                <button class="modal-close" id="closeProfileModal">×</button>
            </div>
            <form id="profileForm" class="profile-form">
                <div class="form-group">
                    <label for="profileUsername">Username</label>
                    <input type="text" id="profileUsername" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="profileEmail">Email</label>
                    <input type="email" id="profileEmail" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="profileAvatar">Avatar</label>
                    <div class="avatar-upload">
                        <img id="avatarPreview" src="" alt="Avatar Preview" style="display: none;">
                        <input type="file" id="profileAvatar" name="avatar" accept="image/*">
                        <small>PNG, JPG, GIF up to 5MB</small>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content delete-modal">
            <div class="modal-header">
                <h2>Delete Message</h2>
                <button class="modal-close" id="closeDeleteModal">×</button>
            </div>
            <p>How do you want to delete this message?</p>
            <div class="delete-options">
                <button id="deleteForMeBtn" class="btn btn-secondary">Delete for Me Only</button>
                <button id="deleteForAllBtn" class="btn btn-danger">Delete for Everyone</button>
            </div>
        </div>
    </div>

    <!-- Conversation Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content details-modal">
            <div class="modal-header">
                <h2>Details</h2>
                <button class="modal-close" id="closeDetailsModal">×</button>
            </div>
            <div class="details-content">
                <div class="user-detail-section">
                    <div class="detail-avatar-large">
                        <span id="detailAvatar">?</span>
                    </div>
                    <h3 id="detailUsername">Loading...</h3>
                    <p id="detailStatus" class="detail-status">Active now</p>
                </div>
                
                <div class="detail-section">
                    <h4>Conversation Info</h4>
                    <div class="info-item">
                        <span class="info-label">Started</span>
                        <span class="info-value" id="convStarted">—</span>
                    </div>
                </div>
                
                <div class="detail-actions">
                    <button id="blockUserBtn" class="btn btn-danger btn-block">Block User</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>