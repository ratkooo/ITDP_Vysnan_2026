<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Chat Hub</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        /* Fallback Global Reset to guarantee formatting match even if style.css is partially loaded */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            color: var(--text, #334155);
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
    <div class="card chat-card-box">

        <?php if ($isAdmin): ?>
            <aside class="chat-sidebar">
                <h4 class="chat-sidebar-title">Active Users</h4>
                <div id="admin-threads-box" class="threads-list-box"></div>
            </aside>
        <?php endif; ?>

        <main class="chat-main-stream">
            <div class="chat-stream-header">
                <span id="chat-header-title">
                    <?= $isAdmin ? "Select a user conversation thread" : "Direct Message Support Administration" ?>
                </span>
            </div>

            <div id="chat-messages-stream" class="chat-messages-body">
                <p class="chat-empty-placeholder">Awaiting channel selection trace...</p>
            </div>

            <form id="chat-input-form" class="chat-input-area" <?= $isAdmin ? 'style="display: none;"' : '' ?>>
                <input type="text" id="chat-message-payload" class="chat-input-field" placeholder="Type your message context..." autocomplete="off" required>
                <button type="submit" class="btn-primary chat-submit-btn">Send</button>
            </form>
        </main>
    </div>
</div>

<script>
    const userIsAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
    let activeThreadId = null;

    function escapeHtml(str) {
        if (!str) return '';
        return str.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (userIsAdmin) {
            pollAdminThreads();
            setInterval(pollAdminThreads, 5000);
        } else {
            pollMessages();
            setInterval(pollMessages, 3000);
        }
        document.getElementById('chat-input-form').addEventListener('submit', dispatchMessage);
    });

    function pollAdminThreads() {
        fetch('/api/chat-threads')
            .then(res => res.json())
            .then(threads => {
                const container = document.getElementById('admin-threads-box');
                container.innerHTML = '';
                threads.forEach(t => {
                    const el = document.createElement('div');
                    el.className = `thread-item-pill ${activeThreadId == t.user_id ? 'active' : ''}`;
                    el.innerHTML = `@${escapeHtml(t.thread_owner)}`;

                    el.onclick = () => {
                        activeThreadId = t.user_id;
                        document.getElementById('chat-header-title').innerText = `Chatting with @${t.thread_owner}`;
                        document.getElementById('chat-input-form').style.display = 'flex';
                        pollMessages();
                        pollAdminThreads();
                    };
                    container.appendChild(el);
                });
            });
    }

    function pollMessages() {
        if (userIsAdmin && !activeThreadId) return;
        let endpoint = '/api/chat-messages';
        if (userIsAdmin) endpoint += `?user_id=${activeThreadId}`;

        fetch(endpoint)
            .then(res => res.json())
            .then(messages => {
                const stream = document.getElementById('chat-messages-stream');
                stream.innerHTML = messages.length === 0 ? '<p class="chat-empty-placeholder">No message logs found.</p>' : '';

                messages.forEach(m => {
                    const row = document.createElement('div');
                    row.className = 'chat-message-card';
                    row.innerHTML = `
                        <small>@${escapeHtml(m.sender_username)}</small>
                        <p>${escapeHtml(m.message_text)}</p>
                    `;
                    stream.appendChild(row);
                });
                stream.scrollTop = stream.scrollHeight;
            });
    }

    function dispatchMessage(e) {
        e.preventDefault();
        const input = document.getElementById('chat-message-payload');
        const msg = input.value.trim();
        if (!msg) return;

        const body = { message: msg };
        if (userIsAdmin) body.user_id = activeThreadId;

        fetch('/api/chat-send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        }).then(res => res.json()).then(data => {
            if (data.success) {
                input.value = '';
                pollMessages();
            }
        });
    }
</script>
</body>
</html>