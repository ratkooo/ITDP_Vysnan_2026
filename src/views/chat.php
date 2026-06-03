<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support Chat</title>
    <link rel="stylesheet" href="/css/style.css">

    <style>
        /* Scoped structural helpers to handle dynamic rightsided alignment */
        .chat-message-card.admin-align {
            align-self: flex-end !important;
            background-color: #e0f2fe !important; /* Soft sky-blue tint */
            border-color: #bae6fd !important;
        }

        .chat-message-card.admin-align small {
            color: #0369a1 !important; /* Contrasting admin label */
            text-align: right;
        }

        /* Container element for displaying message timing metrics */
        .chat-msg-time {
            display: block;
            font-size: 0.68rem;
            color: #64748b;
            text-align: right;
            margin-top: 0.35rem;
            font-weight: 500;
        }
    </style>
</head>
<body>

<nav class="site-navigation-bar">
    <div class="nav-left">
        <a href="/">Home</a>
        <a href="/blog">Blog</a>
        <?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="/dashboard">Study Dashboard</a>
        <?php endif; ?>
    </div>

    <div class="nav-right">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/chat" class="nav-icon-link" title="Chat Support">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </a>
            <a href="/profile" class="nav-profile-link"><?= htmlspecialchars($_SESSION['username']); ?></a>
            <a href="/logout" class="nav-logout-btn">Logout</a>
        <?php else: ?>
            <a href="/login">Login</a>
            <a href="/register">Register</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container chat-layout-wrapper">
    <div class="chat-card-box" style="width: 100%;">

        <?php if (!empty($isAdmin) && $isAdmin === true): ?>
            <div class="chat-sidebar">
                <h3 class="chat-sidebar-title">Active Users</h3>
                <div class="threads-list-box" id="adminThreadsList">
                </div>
            </div>
        <?php endif; ?>

        <div class="chat-main-stream">
            <div class="chat-stream-header">
                <div id="chat-header-title">
                    <?php echo ($isAdmin) ? 'Select a user thread' : 'Support Live Chat'; ?>
                </div>
            </div>

            <div class="chat-messages-body" id="chatMessages">
            </div>

            <div class="chat-input-area">
                <input type="text" id="messageInput" class="chat-input-field" placeholder="Type message...">
                <button id="sendBtn" class="btn chat-submit-btn">Send</button>
            </div>
        </div>
    </div>
</div>

<script>
    const chatMessages = document.getElementById('chatMessages');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');

    // Extracts active parameters if Admin context directory exists
    const urlParams = new URLSearchParams(window.location.search);
    const activeTargetUserId = urlParams.get('user_id') || '';

    /**
     * Converts raw SQL datetime strings down into a localized clean short time
     */
    function formatTime(dateTimeStr) {
        if (!dateTimeStr) return '';
        try {
            // Replace space with T to guarantee clean cross-browser parsing properties
            const date = new Date(dateTimeStr.replace(' ', 'T'));
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
        } catch(e) {
            return '';
        }
    }

    /**
     * Poll and render message items matching system design patterns
     */
    async function loadMessages() {
        try {
            const endpoint = activeTargetUserId ? `/api/chat-messages?user_id=${activeTargetUserId}` : '/api/chat-messages';
            const response = await fetch(endpoint);
            const messages = await response.json();

            chatMessages.innerHTML = '';

            if (messages.length === 0) {
                chatMessages.innerHTML = `<div class="chat-empty-placeholder">Awaiting conversation trace...</div>`;
                return;
            }

            messages.forEach(msg => {
                const isMsgFromAdmin = (msg.sender_username === 'admin');

                // Keep standard left-aligned look unless the user writing is the admin
                const dynamicClass = isMsgFromAdmin ? 'chat-message-card admin-align' : 'chat-message-card';

                const msgElement = document.createElement('div');
                msgElement.className = dynamicClass;

                msgElement.innerHTML = `
                    <small>@${msg.sender_username}</small>
                    <p>${escapeHtml(msg.message_text)}</p>
                    <span class="chat-msg-time">${formatTime(msg.created_at)}</span>
                `;

                chatMessages.appendChild(msgElement);
            });

            // Keep view sticky at bottom boundaries to monitor fresh records
            chatMessages.scrollTop = chatMessages.scrollHeight;

        } catch (error) {
            console.error("Failed to map dynamic tracking entries:", error);
        }
    }

    /**
     * Post new payload contexts onto the relational engine
     */
    async function sendMessage() {
        const text = messageInput.value.trim();
        if (!text) return;

        const payload = { message: text };
        if (activeTargetUserId) {
            payload.user_id = parseInt(activeTargetUserId);
        }

        try {
            const response = await fetch('/api/chat-send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            if (result.success) {
                messageInput.value = '';
                loadMessages(); // Instantly update view tracking layouts
            } else {
                alert("Error sending message: " + (result.error || "Unknown response context"));
            }
        } catch (error) {
            console.error("Network interface error:", error);
        }
    }

    function escapeHtml(text) {
        return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    sendBtn.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') sendMessage(); });

    // Instantly load data tracks and begin long poll loop intervals
    loadMessages();
    setInterval(loadMessages, 2000);

    async function loadAdminThreads() {
        const sidebar = document.getElementById('adminThreadsList');
        if (!sidebar) return;

        try {
            const response = await fetch('/api/chat-threads');
            const threads = await response.json();

            sidebar.innerHTML = '';
            threads.forEach(thread => {
                const pill = document.createElement('div');
                pill.className = 'thread-item-pill';

                // FIX: Use 'thread_owner' instead of 'username'
                pill.textContent = '@' + (thread.thread_owner || 'Unknown');

                pill.onclick = () => window.location.href = '/chat?user_id=' + thread.user_id;
                sidebar.appendChild(pill);
            });
        } catch (error) {
            console.error("Error loading threads:", error);
        }
    }

    // Initialize only for admins
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    loadAdminThreads();
    setInterval(loadAdminThreads, 5000); // Refresh list every 5 seconds
    <?php endif; ?>
</script>
</body>
</html>