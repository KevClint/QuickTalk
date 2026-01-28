# Messaging App - PHP & MySQL

A complete real-time messaging application built with PHP, MySQL, and vanilla JavaScript.

## Features

- ✅ User registration and authentication
- ✅ Real-time messaging with polling
- ✅ Direct messaging between users
- ✅ Group chat support (database ready)
- ✅ Typing indicators
- ✅ Read receipts
- ✅ Message status tracking (sent/delivered/read)
- ✅ User online/offline status
- ✅ Unread message counts
- ✅ Search users
- ✅ Conversation list with last messages
- ✅ Responsive design

## Requirements

- XAMPP (Apache + MySQL + PHP)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

## Installation Steps

### 1. Copy Project to XAMPP

Copy the entire `messaging-app` folder to your XAMPP htdocs directory:
```
C:\xampp\htdocs\messaging-app\
```

### 2. Start XAMPP Services

1. Open XAMPP Control Panel
2. Start **Apache**
3. Start **MySQL**

### 3. Create Database

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click "New" to create a new database
3. Name it: `messaging_app`
4. Click "Create"

### 4. Import Database Schema

1. In phpMyAdmin, select the `messaging_app` database
2. Click on the "SQL" tab
3. Open the file: `sql/schema.sql`
4. Copy all the SQL content and paste it into the SQL tab
5. Click "Go" to execute

Alternatively, you can use the "Import" tab:
- Click "Import"
- Choose file: `sql/schema.sql`
- Click "Go"

### 5. Configure Database Connection (if needed)

If you changed the default MySQL password, edit `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'messaging_app');
define('DB_USER', 'root');
define('DB_PASS', ''); // Change this if you set a MySQL password
```

### 6. Set File Permissions

Make sure the `assets/uploads/` directory is writable:
```
Right-click on assets/uploads/ > Properties > Security > Edit
Give "Users" write permissions
```

### 7. Access the Application

Open your web browser and go to:
```
http://localhost/messaging-app/
```

## Usage Guide

### First Time Setup

1. **Register a new account**
   - Click "Register here" on the login page
   - Fill in username, email, and password
   - Click "Register"

2. **Login**
   - Enter your username or email
   - Enter your password
   - Click "Login"

3. **Start Messaging**
   - Click on "Users" tab to see all registered users
   - Click on a user to start a conversation
   - Type your message and press Enter or click Send

### Features Guide

**Conversations Tab**
- View all your active conversations
- See last message and time
- See unread message count (blue badge)
- Click to open conversation

**Users Tab**
- See all registered users
- Search for users by username
- Click to start new conversation
- See online/offline status

**Chat Window**
- Send messages by typing and pressing Enter
- See typing indicators when someone is typing
- Messages are marked as read automatically
- Scroll to see message history

**Additional Features**
- Real-time updates every 2-3 seconds
- Automatic message delivery tracking
- Persistent sessions (stay logged in)
- Clean and modern UI

## Testing the Application

### Create Test Users

To test the messaging functionality, create at least 2 users:

1. **User 1**: Register with username "alice"
2. **User 2**: Register with username "bob"

Then:
- Login as Alice in one browser/tab
- Login as Bob in another browser/tab (incognito mode)
- Start chatting between them!

## Troubleshooting

### Database Connection Error
- Make sure MySQL is running in XAMPP
- Check database credentials in `config/database.php`
- Verify database `messaging_app` exists in phpMyAdmin

### Page Not Found (404)
- Make sure you're accessing `http://localhost/messaging-app/`
- Check that Apache is running in XAMPP
- Verify files are in `C:\xampp\htdocs\messaging-app\`

### Messages Not Appearing
- Check browser console for JavaScript errors (F12)
- Verify database tables were created correctly
- Clear browser cache and reload

### Upload Directory Error
- Make sure `assets/uploads/` folder exists
- Give write permissions to the folder
- Check folder path in upload functions

## Project Structure

```
messaging-app/
├── index.php              # Login page
├── register.php           # Registration page
├── chat.php               # Main chat interface
├── config/
│   └── database.php       # Database configuration
├── includes/
│   ├── auth.php          # Authentication functions
│   └── functions.php     # Helper functions
├── api/
│   ├── send_message.php      # Send message endpoint
│   ├── get_messages.php      # Fetch messages endpoint
│   ├── get_conversations.php # Get conversations list
│   ├── get_users.php         # Get users list
│   ├── mark_as_read.php      # Mark messages as read
│   ├── typing_indicator.php  # Update typing status
│   ├── get_typing.php        # Check typing status
│   └── logout.php            # Logout endpoint
├── assets/
│   ├── css/
│   │   └── style.css     # Main stylesheet
│   ├── js/
│   │   └── app.js        # Main JavaScript
│   └── uploads/          # User uploaded files
└── sql/
    └── schema.sql        # Database schema
```

## Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Server**: Apache (XAMPP)
- **Architecture**: RESTful API

## Security Features

- Password hashing with PHP's `password_hash()`
- Prepared statements to prevent SQL injection
- XSS protection with `htmlspecialchars()`
- Session-based authentication
- Input validation on both client and server
- CSRF protection for forms

## Future Enhancements

- [ ] File/image upload in messages
- [ ] Voice/video calls
- [ ] Message reactions (emoji)
- [ ] Message editing and deletion
- [ ] User blocking
- [ ] Group chat creation UI
- [ ] Push notifications
- [ ] WebSocket for true real-time updates
- [ ] Message search functionality
- [ ] User profiles with avatars

## Credits

Created as a complete messaging application demonstration using PHP and MySQL.

## License

Free to use for educational purposes.
