// Global variables
let currentConversationId = null;
let lastMessageId = 0;
let messagePolling = null;
let typingPolling = null;
let conversationPolling = null;
let typingTimeout = null;
let currentUsername = '';
let currentUserEmail = '';
let currentUserAvatar = '';

// Initialize app
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Mark user as online
    markUserOnline();
    
    // Load initial data
    loadConversations();
    loadMessageRequests();
    
    // Setup event listeners
    setupEventListeners();
    
    // Start polling for conversations
    startConversationPolling();
    
    // Load saved theme
    loadTheme();
}

function setupEventListeners() {
    // Tab switching
    document.querySelectorAll('.pill-tab').forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.dataset.tab;
            switchTab(tab);
            
            // Load users when switching to people tab
            if (tab === 'people') {
                loadUsers();
            }
        });
    });
    
    // Conversation item clicks (event delegation)
    document.getElementById('conversations-tab').addEventListener('click', function(e) {
        const conversationItem = e.target.closest('.conversation-item');
        if (conversationItem) {
            const conversationId = conversationItem.dataset.id;
            openConversation(conversationId);
        }
    });
    
    // Message requests clicks (event delegation)
    document.getElementById('requests-tab').addEventListener('click', function(e) {
        // Accept button
        if (e.target.classList.contains('accept-request-btn')) {
            const senderId = e.target.dataset.id;
            acceptRequest(senderId);
        }
        // Block button
        else if (e.target.classList.contains('block-request-btn')) {
            const senderId = e.target.dataset.id;
            blockRequest(senderId);
        }
        // Request item click to open conversation
        else {
            const requestItem = e.target.closest('.request-item');
            if (requestItem) {
                const senderId = requestItem.dataset.id;
                openMessageRequest(senderId);
            }
        }
    });
    
    // People tab clicks (event delegation)
    document.getElementById('people-tab').addEventListener('click', function(e) {
        // Message button
        if (e.target.classList.contains('btn-message')) {
            const userId = e.target.dataset.userId;
            startChatWithUser(userId);
        }
        // User item click to open chat
        else {
            const userItem = e.target.closest('.user-item');
            if (userItem) {
                const userId = userItem.dataset.id;
                startChatWithUser(userId);
            }
        }
    });
    
    // Send message
    document.getElementById('sendBtn').addEventListener('click', sendMessage);
    
    // Message input - handle Enter key
    const messageInput = document.getElementById('messageInput');
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    // Auto-resize textarea
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
        
        // Send typing indicator
        handleTyping();
    });
    
    // Search users
    document.getElementById('searchUsers').addEventListener('input', function() {
        const query = this.value.toLowerCase();
        filterUsers(query);
    });
    
    // Logout
    document.getElementById('logoutBtn').addEventListener('click', logout);
    
    // Theme toggle
    document.getElementById('themeBtn').addEventListener('click', toggleTheme);
    
    // Profile button
    document.getElementById('profileBtn').addEventListener('click', openProfileModal);
    
    // Profile modal close
    document.getElementById('closeProfileModal').addEventListener('click', closeProfileModal);
    
    // Profile form submit
    document.getElementById('profileForm').addEventListener('submit', updateProfile);
    
    // File attachment
    document.getElementById('attachBtn').addEventListener('click', function() {
        document.getElementById('fileInput').click();
    });
    
    // Pinned messages button
    document.getElementById('pinnedBtn').addEventListener('click', function() {
        const panel = document.getElementById('pinnedPanel');
        if (panel.style.display === 'none') {
            loadPinnedMessages(currentConversationId);
            panel.style.display = 'block';
        } else {
            panel.style.display = 'none';
        }
    });
    
    // Close pinned messages panel
    document.getElementById('closePinnedBtn').addEventListener('click', function() {
        document.getElementById('pinnedPanel').style.display = 'none';
    });
    
    // Chat info/details button
    const chatInfoBtn = document.getElementById('chatInfoBtn');
    if (chatInfoBtn) {
        chatInfoBtn.addEventListener('click', function() {
            openConversationDetails();
        });
    }
    
    // Close details modal
    const closeDetailsModalBtn = document.getElementById('closeDetailsModal');
    if (closeDetailsModalBtn) {
        closeDetailsModalBtn.addEventListener('click', function() {
            document.getElementById('detailsModal').classList.remove('active');
        });
    }
    
    // Block user button
    const blockUserBtn = document.getElementById('blockUserBtn');
    if (blockUserBtn) {
        blockUserBtn.addEventListener('click', function() {
            if (confirm('Block this user? You won\'t receive messages from them.')) {
                blockUser(currentRecipientId);
            }
        });
    }
    
    // Pinned messages actions
    document.getElementById('pinnedMessages').addEventListener('click', function(e) {
        if (e.target.classList.contains('unpin-btn')) {
            const messageId = e.target.dataset.id;
            unpinMessage(messageId);
        } else if (e.target.classList.contains('scroll-to-msg')) {
            const messageId = e.target.dataset.id;
            scrollToMessage(messageId);
        }
    });
    
    // Message actions (edit, delete, pin)
    document.getElementById('messagesContainer').addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-btn')) {
            const messageId = e.target.dataset.id;
            editMessage(messageId);
        } else if (e.target.classList.contains('delete-btn')) {
            const messageId = e.target.dataset.id;
            deleteMessage(messageId);
        } else if (e.target.classList.contains('pin-btn')) {
            const messageId = e.target.dataset.id;
            pinMessage(messageId);
        }
    });
    
    // Back to sidebar on mobile
    const backBtn = document.getElementById('backToSidebar');
    if (backBtn) {
        backBtn.addEventListener('click', function() {
            document.body.classList.remove('show-chat-view');
        });
    }
}

function switchTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.pill-tab').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`${tab}-tab`).classList.add('active');
}

// Load conversations
function loadConversations() {
    fetch('api/get_conversations.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayConversations(data.conversations);
            } else {
                console.error('Failed to load conversations:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading conversations:', error);
        });
}

function displayConversations(conversations) {
    const container = document.getElementById('conversations-tab');
    
    if (conversations.length === 0) {
        container.innerHTML = '<div class="loading">No conversations yet</div>';
        return;
    }
    
    container.innerHTML = conversations.map(conv => {
        const avatar = conv.avatar === 'group' ? '👥' : conv.name.charAt(0).toUpperCase();
        const unreadBadge = conv.unread_count > 0 
            ? `<span class="unread-badge">${conv.unread_count}</span>` 
            : '';
        
        // Get recipient ID for direct chats
        let recipientId = '';
        if (conv.type === 'direct' && conv.participants && conv.participants.length > 0) {
            recipientId = conv.participants[0].id;
        }
        
        return `
            <div class="conversation-item" data-id="${conv.id}" data-recipient-id="${recipientId}">
                <div class="avatar">${avatar}</div>
                <div class="conversation-info">
                    <h4>${escapeHtml(conv.name)}</h4>
                    <p>${escapeHtml(conv.last_message)}</p>
                </div>
                <div class="conversation-meta">
                    <span class="conversation-time">${formatTime(conv.last_message_time)}</span>
                    ${unreadBadge}
                </div>
            </div>
        `;
    }).join('');
}

// Load message requests
function loadMessageRequests() {
    fetch('api/get_message_requests.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMessageRequests(data.requests);
            } else {
                console.error('Failed to load message requests:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading message requests:', error);
        });
}

function displayMessageRequests(requests) {
    const container = document.getElementById('requests-tab');
    
    if (requests.length === 0) {
        container.innerHTML = '<div class="loading">No message requests</div>';
        return;
    }
    
    container.innerHTML = requests.map(req => {
        const avatar = req.username.charAt(0).toUpperCase();
        const statusClass = req.status === 'online' ? 'online' : '';
        const lastMsg = req.last_message ? req.last_message.substring(0, 50) : 'No messages yet';
        
        return `
            <div class="request-item" data-id="${req.id}">
                <div class="request-left">
                    <div class="avatar">${avatar}</div>
                    <div class="request-info">
                        <h4>${escapeHtml(req.username)}</h4>
                        <p class="request-preview">${escapeHtml(lastMsg)}</p>
                    </div>
                </div>
                <div class="request-actions">
                    <button class="btn-small accept-request-btn" data-id="${req.id}" title="Accept">✓</button>
                    <button class="btn-small block-request-btn" data-id="${req.id}" title="Block">✕</button>
                </div>
            </div>
        `;
    }).join('');
}

// Load all users
function loadUsers() {
    fetch('api/get_users.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayUsers(data.users);
            } else {
                console.error('Failed to load users:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
        });
}

function displayUsers(users) {
    const container = document.getElementById('people-tab');
    
    if (users.length === 0) {
        container.innerHTML = '<div class="loading">No users found</div>';
        return;
    }
    
    container.innerHTML = users.map(user => {
        const avatar = user.username.charAt(0).toUpperCase();
        const statusClass = user.status === 'online' ? 'online' : '';
        const statusText = user.status === 'online' ? 'Active now' : 'Away';
        
        return `
            <div class="user-item" data-id="${user.id}">
                <div class="avatar ${statusClass}">${avatar}</div>
                <div class="user-info">
                    <h4>${escapeHtml(user.username)}</h4>
                    <p>${statusText}</p>
                </div>
                <button class="btn-message" data-user-id="${user.id}">Message</button>
            </div>
        `;
    }).join('');
}

// Accept message request
function acceptRequest(senderId) {
    fetch('api/accept_request.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sender_id: senderId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadMessageRequests();
            loadConversations();
            
            // Automatically open the conversation
            if (data.conversation_id) {
                setTimeout(() => {
                    openConversation(data.conversation_id);
                    switchTab('conversations');
                }, 500);
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error accepting request:', error);
    });
}

// Block message request
function blockRequest(senderId) {
    if (!confirm('Block this user? You won\'t receive messages from them.')) return;
    
    fetch('api/block_request.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sender_id: senderId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadMessageRequests();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error blocking request:', error);
    });
}

// Open conversation details modal
function openConversationDetails() {
    if (!currentRecipientId) return;
    
    // Get conversation avatar and username
    const avatar = document.getElementById('chatAvatar').textContent;
    const username = document.getElementById('chatUsername').textContent;
    const status = document.getElementById('chatStatus').textContent;
    
    // Find conversation creation date
    const convItem = document.querySelector(`.conversation-item[data-id="${currentConversationId}"]`);
    const createdDate = convItem ? formatTime(new Date()) : 'Unknown';
    
    // Update modal
    document.getElementById('detailAvatar').textContent = avatar;
    document.getElementById('detailUsername').textContent = username;
    document.getElementById('detailStatus').textContent = status;
    document.getElementById('convStarted').textContent = createdDate;
    
    // Show modal
    document.getElementById('detailsModal').classList.add('active');
}

// Block user from conversation details
function blockUser(userId) {
    fetch('api/block_request.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sender_id: userId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('User blocked');
            document.getElementById('detailsModal').classList.remove('active');
            loadConversations();
            loadMessageRequests();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error blocking user:', error);
        alert('Failed to block user');
    });
}

// Open message request conversation
function openMessageRequest(senderId) {
    // Find or create conversation with this user
    fetch('api/create_conversation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ recipient_id: senderId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            openConversation(data.conversation_id);
            // Switch to chats tab
            switchTab('conversations');
        }
    })
    .catch(error => {
        console.error('Error opening request:', error);
    });
}

// Check friendship status when opening conversation
function checkConversationFriendshipStatus(conversationId) {
    if (!currentRecipientId) return;
    
    fetch(`api/check_friendship.php?recipient_id=${currentRecipientId}`)
        .then(response => response.json())
        .then(data => {
            const messageInput = document.getElementById('messageInput');
            const sendBtn = document.getElementById('sendBtn');
            const messageFooter = document.getElementById('messageFooter');
            
            if (data.status === 'accepted') {
                // Only allow messaging if friends
                messageInput.disabled = false;
                sendBtn.disabled = false;
                
                // Remove any notices
                const notice = messageFooter.querySelector('.pending-request-notice, .blocked-notice, .not-friends-notice');
                if (notice) notice.remove();
            } else if (data.status === 'pending') {
                // Disable message input for pending requests
                messageInput.disabled = true;
                sendBtn.disabled = true;
                
                // Show pending message
                if (!messageFooter.querySelector('.pending-request-notice')) {
                    const notice = document.createElement('div');
                    notice.className = 'pending-request-notice';
                    notice.textContent = 'Request pending. You can message after they accept your request.';
                    messageFooter.insertBefore(notice, messageFooter.firstChild);
                }
            } else if (data.status === 'blocked') {
                // Disable for blocked users
                messageInput.disabled = true;
                sendBtn.disabled = true;
                
                if (!messageFooter.querySelector('.blocked-notice')) {
                    const notice = document.createElement('div');
                    notice.className = 'blocked-notice';
                    notice.textContent = 'You have blocked this user.';
                    messageFooter.insertBefore(notice, messageFooter.firstChild);
                }
            } else {
                // No friendship - disable messaging
                messageInput.disabled = true;
                sendBtn.disabled = true;
                
                if (!messageFooter.querySelector('.not-friends-notice')) {
                    const notice = document.createElement('div');
                    notice.className = 'not-friends-notice';
                    notice.textContent = 'You must be friends to message this user. Send a friend request first.';
                    messageFooter.insertBefore(notice, messageFooter.firstChild);
                }
            }
        })
        .catch(error => {
            console.error('Error checking friendship status:', error);
        });
}

function filterUsers(query) {
    const items = document.querySelectorAll('.request-item');
    items.forEach(item => {
        const username = item.querySelector('h4').textContent.toLowerCase();
        if (username.includes(query)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

// Open conversation
let currentRecipientId = null;

function openConversation(conversationId) {
    currentConversationId = conversationId;
    lastMessageId = 0;
    currentRecipientId = null;
    
    // Clear previous polling
    if (messagePolling) clearInterval(messagePolling);
    if (typingPolling) clearInterval(typingPolling);
    
    // Update active conversation
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('active');
    });
    const activeItem = document.querySelector(`[data-id="${conversationId}"]`);
    if (activeItem) {
        activeItem.classList.add('active');
    }
    
    // Show chat window and hide welcome screen
    document.getElementById('welcomeScreen').style.display = 'none';
    document.getElementById('chatWindow').style.display = 'flex';
    
    // Toggle mobile view
    if (window.innerWidth <= 768) {
        document.body.classList.add('show-chat-view');
    }
    
    // Load conversation details
    loadConversationDetails(conversationId);
    
    // Load messages
    loadMessages(conversationId);
    
    // Check friendship status
    checkConversationFriendshipStatus(conversationId);
    
    // Mark as read
    markAsRead(conversationId);
    
    // Start polling for new messages
    messagePolling = setInterval(() => {
        loadNewMessages(conversationId);
    }, 2000);
    
    // Start polling for typing indicator
    typingPolling = setInterval(() => {
        checkTyping(conversationId);
    }, 2000);
}

function loadConversationDetails(conversationId) {
    // Find conversation in list
    const convItem = document.querySelector(`.conversation-item[data-id="${conversationId}"]`);
    if (convItem) {
        const username = convItem.querySelector('h4').textContent;
        const avatar = convItem.querySelector('.avatar').textContent;
        const status = convItem.querySelector('.conversation-info p').textContent;
        // Store recipient ID from data attribute
        currentRecipientId = convItem.getAttribute('data-recipient-id');
        
        document.getElementById('chatAvatar').textContent = avatar;
        document.getElementById('chatUsername').textContent = username;
        document.getElementById('chatStatus').textContent = status;
        } else {
        // If conversation not found in DOM, set default values
        document.getElementById('chatAvatar').textContent = '?';
        document.getElementById('chatUsername').textContent = 'Loading...';
        document.getElementById('chatStatus').textContent = 'Active now';
    }
}

// Start chat with user
function startChatWithUser(userId) {
    // Send friend request instead of starting chat directly
    fetch('api/send_friend_request.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            recipient_id: userId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Friend request sent! They will see it in their Requests tab.');
            loadUsers(); // Refresh users list
            loadMessageRequests(); // Refresh requests
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error sending friend request:', error);
        alert('Failed to send friend request');
    });
}

// Load messages
function loadMessages(conversationId) {
    const container = document.getElementById('messagesContainer');
    container.innerHTML = '<div class="loading">Loading messages...</div>';
    
    fetch(`api/get_messages.php?conversation_id=${conversationId}&limit=50`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMessages(data.messages);
                if (data.messages.length > 0) {
                    lastMessageId = data.messages[data.messages.length - 1].id;
                }
                scrollToBottom();
            } else {
                container.innerHTML = `<div class="loading">${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error loading messages:', error);
            container.innerHTML = '<div class="loading">Failed to load messages</div>';
        });
}

function loadNewMessages(conversationId) {
    if (conversationId !== currentConversationId) return;
    
    fetch(`api/get_messages.php?conversation_id=${conversationId}&after_id=${lastMessageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                appendMessages(data.messages);
                lastMessageId = data.messages[data.messages.length - 1].id;
                scrollToBottom();
                markAsRead(conversationId);
            }
        })
        .catch(error => {
            console.error('Error loading new messages:', error);
        });
}

function displayMessages(messages) {
    const container = document.getElementById('messagesContainer');
    
    if (messages.length === 0) {
        container.innerHTML = '<div class="loading">No messages yet. Start the conversation!</div>';
        return;
    }
    
    container.innerHTML = messages.map(msg => createMessageElement(msg)).join('');
}

function appendMessages(messages) {
    const container = document.getElementById('messagesContainer');
    messages.forEach(msg => {
        container.innerHTML += createMessageElement(msg);
    });
}

function createMessageElement(msg) {
    const isOwn = msg.is_own ? 'own' : '';
    const avatar = msg.sender_username.charAt(0).toUpperCase();
    const senderName = !msg.is_own ? `<div class="message-sender">${escapeHtml(msg.sender_username)}</div>` : '';
    const edited = msg.edited_at ? `<span class="edited">(edited)</span>` : '';
    
    // Get delivery status indicator for own messages
    let deliveryStatus = '';
    if (isOwn) {
        // Use delivery_status if present (from send_message.php), otherwise use read_status
        const status = msg.delivery_status || msg.read_status || 'sent';
        if (status === 'sent') {
            deliveryStatus = `<span class="delivery-status" title="Sent">✓</span>`;
        } else if (status === 'delivered') {
            deliveryStatus = `<span class="delivery-status delivered" title="Delivered">✓✓</span>`;
        } else if (status === 'read') {
            deliveryStatus = `<span class="delivery-status read" title="Read">✓✓</span>`;
        }
    }
    
    // Handle deleted messages
    if (msg.is_deleted || msg.deleted_for_all) {
        let deleteText = '';
        
        if (msg.deleted_for_all) {
            // Deleted for everyone
            if (msg.is_own) {
                deleteText = 'You deleted a message';
            } else {
                deleteText = `${escapeHtml(msg.sender_username)} deleted a message`;
            }
        } else {
            // Deleted for you only (hidden to you)
            deleteText = 'This message was deleted';
        }
        
        return `
            <div class="message ${isOwn}" data-message-id="${msg.id}">
                <div class="message-avatar">${avatar}</div>
                <div class="message-content">
                    ${senderName}
                    <div class="message-bubble deleted-message">
                        <em>${deleteText}</em>
                        <div class="message-footer">
                            <span class="message-time">${formatTime(msg.created_at)}</span>
                            ${deliveryStatus}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Build media HTML if present
    let mediaHtml = '';
    if (msg.media_url) {
        if (msg.media_type === 'image') {
            mediaHtml = `<img src="${escapeHtml(msg.media_url)}" class="message-media" style="max-width: 300px; border-radius: 8px; margin-top: 8px;">`;
        } else if (msg.media_type === 'video') {
            mediaHtml = `<video class="message-media" style="max-width: 300px; border-radius: 8px; margin-top: 8px;" controls><source src="${escapeHtml(msg.media_url)}"></video>`;
        } else if (msg.media_type === 'audio') {
            mediaHtml = `<audio class="message-media" style="margin-top: 8px;" controls><source src="${escapeHtml(msg.media_url)}"></audio>`;
        } else if (msg.media_type === 'file') {
            const filename = msg.media_url.split('/').pop();
            mediaHtml = `<a href="${escapeHtml(msg.media_url)}" class="message-file" download>📎 ${escapeHtml(filename)}</a>`;
        }
    }
    
    // Message actions for own messages
    let actions = '';
    if (isOwn) {
        actions = `
            <div class="message-actions">
                <button class="msg-action edit-btn" data-id="${msg.id}" title="Edit">✏️</button>
                <button class="msg-action delete-btn" data-id="${msg.id}" title="Delete">🗑️</button>
                <button class="msg-action pin-btn" data-id="${msg.id}" title="Pin">📌</button>
            </div>
        `;
    }
    
    return `
        <div class="message ${isOwn}" data-message-id="${msg.id}">
            <div class="message-avatar">${avatar}</div>
            <div class="message-content">
                ${senderName}
                <div class="message-bubble">
                    ${escapeHtml(msg.content)}
                    ${mediaHtml}
                    <div class="message-footer">
                        <span class="message-time">${formatTime(msg.created_at)}</span>
                        ${edited}
                        ${deliveryStatus}
                    </div>
                </div>
                ${actions}
            </div>
        </div>
    `;
}

// Send message
function sendMessage() {
    const input = document.getElementById('messageInput');
    const fileInput = document.getElementById('fileInput');
    const content = input.value.trim();
    const file = fileInput.files[0];
    
    if (!content && !file) return;
    if (!currentConversationId) return;
    
    // Disable input while sending
    input.disabled = true;
    document.getElementById('sendBtn').disabled = true;
    
    // Use FormData for file uploads
    const formData = new FormData();
    formData.append('conversation_id', currentConversationId);
    formData.append('content', content);
    if (file) {
        formData.append('media', file);
    }
    
    fetch('api/send_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear input
            input.value = '';
            input.style.height = 'auto';
            fileInput.value = '';
            
            // Add message to UI
            const container = document.getElementById('messagesContainer');
            if (container.querySelector('.loading')) {
                container.innerHTML = '';
            }
            container.innerHTML += createMessageElement(data.data);
            lastMessageId = data.data.id;
            
            scrollToBottom();
            
            // Reload conversations to update last message
            loadConversations();
            
            // Stop typing indicator
            sendTypingIndicator(false);
        } else {
            alert('Failed to send message: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
        alert('Failed to send message');
    })
    .finally(() => {
        input.disabled = false;
        document.getElementById('sendBtn').disabled = false;
        input.focus();
    });
}

// Edit message
function editMessage(messageId) {
    const newContent = prompt('Edit message:');
    if (newContent === null || newContent.trim() === '') return;
    
    fetch('api/edit_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            message_id: messageId,
            content: newContent
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadMessages(currentConversationId);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error editing message:', error);
        alert('Failed to edit message');
    });
}

// Delete message
function deleteMessage(messageId) {
    // Store the messageId for the modal to use
    window.pendingDeleteMessageId = messageId;
    
    // Show delete modal
    document.getElementById('deleteModal').classList.add('active');
}

// Handle delete for me only
document.addEventListener('DOMContentLoaded', function() {
    const deleteForMeBtn = document.getElementById('deleteForMeBtn');
    if (deleteForMeBtn) {
        deleteForMeBtn.addEventListener('click', function() {
            performDeleteMessage(false);
        });
    }
    
    // Handle delete for everyone
    const deleteForAllBtn = document.getElementById('deleteForAllBtn');
    if (deleteForAllBtn) {
        deleteForAllBtn.addEventListener('click', function() {
            performDeleteMessage(true);
        });
    }
    
    // Close delete modal
    const closeDeleteModalBtn = document.getElementById('closeDeleteModal');
    if (closeDeleteModalBtn) {
        closeDeleteModalBtn.addEventListener('click', function() {
            document.getElementById('deleteModal').classList.remove('active');
        });
    }
});

function performDeleteMessage(deleteForAll) {
    const messageId = window.pendingDeleteMessageId;
    if (!messageId) return;
    
    fetch('api/delete_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            message_id: messageId,
            delete_for_all: deleteForAll
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadMessages(currentConversationId);
            document.getElementById('deleteModal').classList.remove('active');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error deleting message:', error);
        alert('Failed to delete message');
    });
    
    window.pendingDeleteMessageId = null;
}

// Pin message
function pinMessage(messageId) {
    const isPinned = confirm('Pin this message?');
    if (!isPinned) return;
    
    fetch('api/pin_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            message_id: messageId,
            conversation_id: currentConversationId,
            unpin: false
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Load and show pinned messages
            loadPinnedMessages(currentConversationId);
            document.getElementById('pinnedPanel').style.display = 'block';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error pinning message:', error);
        alert('Failed to pin message');
    });
}

// Load pinned messages
function loadPinnedMessages(conversationId) {
    fetch(`api/get_pinned_messages.php?conversation_id=${conversationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPinnedMessages(data.pinned_messages);
            } else {
                document.getElementById('pinnedMessages').innerHTML = '<div class="loading">No pinned messages</div>';
            }
        })
        .catch(error => {
            console.error('Error loading pinned messages:', error);
            document.getElementById('pinnedMessages').innerHTML = '<div class="loading">Error loading pinned messages</div>';
        });
}

// Display pinned messages
function displayPinnedMessages(pinnedMessages) {
    const container = document.getElementById('pinnedMessages');
    const pinnedCount = document.getElementById('pinnedCount');
    
    if (pinnedMessages.length === 0) {
        container.innerHTML = '<div class="loading">No pinned messages</div>';
        pinnedCount.textContent = '0';
        return;
    }
    
    // Update the count
    pinnedCount.textContent = pinnedMessages.length;
    
    container.innerHTML = pinnedMessages.map(msg => `
        <div class="pinned-message" data-message-id="${msg.message_id}" role="button" tabindex="0">
            <div class="pinned-message-header">
                <strong>${escapeHtml(msg.sender_username)}</strong> · ${formatTime(msg.created_at)}
            </div>
            <div class="pinned-message-content">
                ${escapeHtml(msg.content)}
            </div>
            <div class="pinned-message-actions">
                <button class="scroll-to-msg" data-id="${msg.message_id}" title="Jump to message">↓ Jump</button>
                <button class="unpin-btn" data-id="${msg.message_id}" title="Unpin message">📌 Unpin</button>
            </div>
        </div>
    `).join('');
    
    // Add click handler to pinned message to scroll to it
    container.querySelectorAll('.pinned-message').forEach(el => {
        el.addEventListener('click', function(e) {
            // Only scroll if clicking on the message itself, not buttons
            if (!e.target.classList.contains('scroll-to-msg') && !e.target.classList.contains('unpin-btn')) {
                const messageId = this.dataset.messageId;
                scrollToMessage(messageId);
            }
        });
        
        // Keyboard support for accessibility
        el.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const messageId = this.dataset.messageId;
                scrollToMessage(messageId);
            }
        });
    });
}

// Unpin message
function unpinMessage(messageId) {
    if (!confirm('Unpin this message?')) return;
    
    fetch('api/pin_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            message_id: messageId,
            conversation_id: currentConversationId,
            unpin: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadPinnedMessages(currentConversationId);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error unpinning message:', error);
        alert('Failed to unpin message');
    });
}

// Scroll to message
function scrollToMessage(messageId) {
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    if (messageElement) {
        messageElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Remove previous highlight
        document.querySelectorAll('.message-highlight').forEach(el => el.classList.remove('message-highlight'));
        
        // Add highlight to current message
        messageElement.classList.add('message-highlight');
        
        // Remove highlight after animation
        setTimeout(() => {
            messageElement.classList.remove('message-highlight');
        }, 3000);
        messageElement.style.backgroundColor = 'yellow';
        messageElement.style.transition = 'background-color 1s';
        setTimeout(() => {
            messageElement.style.backgroundColor = '';
        }, 1000);
    }
}

// Typing indicator
function handleTyping() {
    if (!currentConversationId) return;
    
    // Send typing indicator
    sendTypingIndicator(true);
    
    // Clear previous timeout
    if (typingTimeout) clearTimeout(typingTimeout);
    
    // Stop typing after 3 seconds of no input
    typingTimeout = setTimeout(() => {
        sendTypingIndicator(false);
    }, 3000);
}

function sendTypingIndicator(isTyping) {
    if (!currentConversationId) return;
    
    fetch('api/typing_indicator.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            conversation_id: currentConversationId,
            is_typing: isTyping
        })
    })
    .catch(error => {
        console.error('Error sending typing indicator:', error);
    });
}

function checkTyping(conversationId) {
    if (conversationId !== currentConversationId) return;
    
    fetch(`api/get_typing.php?conversation_id=${conversationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const typingIndicator = document.getElementById('typingIndicator');
                if (data.typing_users.length > 0) {
                    const names = data.typing_users.map(u => u.username).join(', ');
                    document.getElementById('typingUsername').textContent = names;
                    typingIndicator.style.display = 'flex';
                } else {
                    typingIndicator.style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error checking typing:', error);
        });
}

// Mark messages as read
function markAsRead(conversationId) {
    fetch('api/mark_as_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            conversation_id: conversationId
        })
    })
    .then(() => {
        // Reload conversations to update unread count
        loadConversations();
    })
    .catch(error => {
        console.error('Error marking as read:', error);
    });
}

// Polling for conversation updates
function startConversationPolling() {
    conversationPolling = setInterval(() => {
        loadConversations();
    }, 10000); // Every 10 seconds
}

// Logout
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        // Mark user as offline
        fetch('api/set_offline_status.php')
            .then(response => response.json())
            .then(data => {
                // Continue with logout
                return fetch('api/logout.php');
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'index.php';
                }
            })
            .catch(error => {
                console.error('Error logging out:', error);
            });
    }
}

// Mark user as online
function markUserOnline() {
    fetch('api/set_online_status.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Check for any pending "sent" messages and update them
            updateDeliveryStatuses();
        }
    })
    .catch(error => {
        console.error('Error setting online status:', error);
    });
}

// Update delivery statuses of messages
function updateDeliveryStatuses() {
    // Reload current conversation to get updated delivery statuses
    if (currentConversationId) {
        loadMessages(currentConversationId, 0);
    }
}

// Utility functions
function scrollToBottom() {
    const container = document.getElementById('messagesContainer');
    container.scrollTop = container.scrollHeight;
}

function formatTime(timestamp) {
    if (!timestamp) return '';
    
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;
    
    return date.toLocaleDateString();
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (messagePolling) clearInterval(messagePolling);
    if (typingPolling) clearInterval(typingPolling);
    if (conversationPolling) clearInterval(conversationPolling);
    if (currentConversationId) {
        sendTypingIndicator(false);
    }
});
// Dark mode functions
function toggleTheme() {
    const html = document.documentElement;
    const isDark = html.classList.contains('dark-mode');
    
    if (isDark) {
        html.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
        document.getElementById('themeBtn').textContent = '🌙';
    } else {
        html.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
        document.getElementById('themeBtn').textContent = '☀️';
    }
}

function loadTheme() {
    const theme = localStorage.getItem('theme') || 'light';
    if (theme === 'dark') {
        document.documentElement.classList.add('dark-mode');
        document.getElementById('themeBtn').textContent = '☀️';
    } else {
        document.getElementById('themeBtn').textContent = '🌙';
    }
}

// Profile modal functions
function openProfileModal() {
    document.getElementById('profileUsername').value = currentUsername;
    document.getElementById('profileEmail').value = currentUserEmail;
    
    if (currentUserAvatar) {
        document.getElementById('avatarPreview').src = currentUserAvatar;
    }
    
    document.getElementById('profileModal').classList.add('active');
}

function closeProfileModal() {
    document.getElementById('profileModal').classList.remove('active');
}

function updateProfile(e) {
    e.preventDefault();
    
    const formData = new FormData();
    const username = document.getElementById('profileUsername').value;
    const email = document.getElementById('profileEmail').value;
    const avatarFile = document.getElementById('profileAvatar').files[0];
    
    if (username) formData.append('username', username);
    if (email) formData.append('email', email);
    if (avatarFile) formData.append('avatar', avatarFile);
    
    fetch('api/update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update global variables
            if (data.user.username) window.currentUsername = data.user.username;
            if (data.user.email) window.currentUserEmail = data.user.email;
            if (data.user.avatar) window.currentUserAvatar = data.user.avatar;
            
            alert('Profile updated successfully');
            closeProfileModal();
            location.reload(); // Reload to show updated info
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error updating profile:', error);
        alert('Failed to update profile');
    });
}

// Avatar preview
document.getElementById('profileAvatar')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            document.getElementById('avatarPreview').src = event.target.result;
        };
        reader.readAsDataURL(file);
    }
});