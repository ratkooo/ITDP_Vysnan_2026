<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support Chat</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container chat-layout-wrapper">
    <div class="chat-card-box" style="width: 100%;">

        <?php if (!empty($isAdmin) && $isAdmin === true) : ?>
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

    // Track initial page load so it automatically scrolls down the first time
    let isFirstLoad = true;

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
     * Converts raw SQL datetime components down into a friendly long-form reader date
     */
    function formatDate(dateStr) {
        if (!dateStr) return '';
        try {
            const date = new Date(dateStr + 'T00:00:00');
            return date.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
        } catch (e) {
            return dateStr;
        }
    }

    /**
     * Poll and render message items matching system design patterns
     */
    async function loadMessages(forceScrollDown = false) {
        try {
            const endpoint = activeTargetUserId ? `/api/chat-messages?user_id=${activeTargetUserId}` : '/api/chat-messages';
            const response = await fetch(endpoint);
            const messages = await response.json();

            // Measure if user is close to the bottom BEFORE resetting innerHTML
            const scrollBuffer = 50;
            const isUserAtBottom = (chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight) <= scrollBuffer;

            chatMessages.innerHTML = '';

            if (messages.length === 0) {
                chatMessages.innerHTML = `<div class="chat-empty-placeholder">Awaiting conversation trace...</div>`;
                return;
            }

            // Registry context tracking changes between separate loop occurrences
            let lastDateStr = null;

            messages.forEach(msg => {
                // Extrapolate raw SQL Date context entry segment (YYYY-MM-DD)
                const currentDateStr = msg.created_at ? msg.created_at.split(' ')[0] : '';

                // When date switches, inject a decorative dividing line element node
                if (currentDateStr && currentDateStr !== lastDateStr) {
                    const separatorElement = document.createElement('div');
                    separatorElement.className = 'chat-date-separator';
                    separatorElement.innerHTML = `<span>${formatDate(currentDateStr)}</span>`;
                    chatMessages.appendChild(separatorElement);

                    lastDateStr = currentDateStr;
                }

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

            // Only snap down if they were already at the bottom, page just loaded, or they sent a message
            if (isUserAtBottom || isFirstLoad || forceScrollDown) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
                isFirstLoad = false;
            }

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
                await loadMessages(true); // Force push scroll position downward to view new trace entry
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

    // Initial load forces down
    loadMessages(true);
    // Background polling loop does not force down unless user is already scrolled to bottom
    setInterval(() => loadMessages(false), 2000);

    /**
     * Parses thread message statistics to inject sidebar real-time alert markers
     */
    async function loadAdminThreads() {
        const sidebar = document.getElementById('adminThreadsList');
        if (!sidebar) return;

        try {
            const response = await fetch('/api/chat-threads');
            const threads = await response.json();

            // Load tracking registry from localStorage
            let seenThreads = JSON.parse(localStorage.getItem('chat_seen_threads') || '{}');

            sidebar.innerHTML = '';
            threads.forEach(thread => {
                const uid = thread.user_id;
                const totalCount = parseInt(thread.message_count || 0);
                const lastSender = thread.last_sender;

                // Sync currently selected chat thread snapshot count
                if (activeTargetUserId == uid) {
                    seenThreads[uid] = totalCount;
                }

                const lastSeenCount = seenThreads[uid] || 0;
                const unreadCount = totalCount - lastSeenCount;

                // Show badge only if new messages exist and were not sent by an admin
                const showBadge = (unreadCount > 0 && lastSender !== 'admin' && activeTargetUserId != uid);

                const pill = document.createElement('div');
                pill.className = 'thread-item-pill';
                if (activeTargetUserId == uid) {
                    pill.classList.add('active-thread');
                }

                // Add name wrapper element
                const nameLabel = document.createElement('span');
                nameLabel.textContent = '@' + (thread.username || thread.thread_owner);
                pill.appendChild(nameLabel);

                // Inject notification marker element if requirements are fulfilled
                if (showBadge) {
                    const badge = document.createElement('span');
                    badge.className = 'thread-badge';
                    badge.textContent = unreadCount;
                    pill.appendChild(badge);
                }

                pill.onclick = () => {
                    seenThreads[uid] = totalCount;
                    localStorage.setItem('chat_seen_threads', JSON.stringify(seenThreads));
                    window.location.href = '/chat?user_id=' + uid;
                };

                sidebar.appendChild(pill);
            });

            localStorage.setItem('chat_seen_threads', JSON.stringify(seenThreads));
        } catch (error) {
            console.error("Error loading threads:", error);
        }
    }

    // Initialize only for admins
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') : ?>
    loadAdminThreads();
    setInterval(loadAdminThreads, 5000); // Refresh list every 5 seconds
    <?php endif; ?>
</script>
</body>
</html>
