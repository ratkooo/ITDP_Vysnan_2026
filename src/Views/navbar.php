<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<nav class="site-navigation-bar">
    <div class="nav-left">
        <a href="/">Home</a>
        <a href="/blog">Blog</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') : ?>
            <a href="/dashboard">Study Dashboard</a>
        <?php endif; ?>
    </div>

    <div class="nav-right">
        <?php if (isset($_SESSION['user_id'])) : ?>
            <a href="/chat" class="nav-icon-link" title="Chat Support">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <span id="msgExclamationBadge" class="nav-exclamation-badge" style="display: none;">!</span>
            </a>
            <a href="/profile" class="nav-profile-link"><?= htmlspecialchars($_SESSION['username']); ?></a>
            <a href="/logout" class="nav-logout-btn">Logout</a>
        <?php else : ?>
            <a href="/login">Login</a>
            <a href="/register">Register</a>
        <?php endif; ?>
    </div>
</nav>

<script>
    (function() {
        const badge = document.getElementById('msgExclamationBadge');
        if (!badge) return;

        const isAdmin = <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'true' : 'false'; ?>;

        // Environment-agnostic regex URL parser path check (matches /chat, /chat.php, /chat/)
        const isChatPage = /\/chat(\.php)?\/?$/.test(window.location.pathname);

        // Parse target conversation channels if admin directory tracks exist
        const urlParams = new URLSearchParams(window.location.search);
        const activeTargetUserId = urlParams.get('user_id') || '';

        // Immediate cleanup wrapper for standard user on chat page
        if (isChatPage && !isAdmin) {
            badge.style.display = 'none';
        }

        async function checkGlobalMessages() {
            try {
                if (isAdmin) {
                    const response = await fetch('/api/chat-threads');
                    if (!response.ok) return;
                    const threads = await response.json();

                    let showBadge = false;
                    let seenThreads = JSON.parse(localStorage.getItem('chat_seen_threads') || '{}');

                    threads.forEach(thread => {
                        const uid = thread.user_id;
                        const count = parseInt(thread.message_count || 0);
                        const lastSender = thread.last_sender;

                        // If viewing this thread right now, update local storage reference point
                        if (isChatPage && activeTargetUserId == uid) {
                            seenThreads[uid] = count;
                        }

                        const lastSeenCount = seenThreads[uid] || 0;

                        // Trigger badge if new messages exist and the last person who typed isn't the admin themselves
                        if (count > lastSeenCount && lastSender !== 'admin') {
                            if (!(isChatPage && activeTargetUserId == uid)) {
                                showBadge = true;
                            }
                        }
                    });

                    localStorage.setItem('chat_seen_threads', JSON.stringify(seenThreads));
                    badge.style.display = showBadge ? 'flex' : 'none';

                } else {
                    const response = await fetch('/api/chat-messages');
                    if (!response.ok) return;
                    const messages = await response.json();

                    const count = messages.length;
                    const lastMsg = messages[count - 1];

                    if (isChatPage) {
                        localStorage.setItem('chat_user_seen_count', count);
                        badge.style.display = 'none';
                    } else {
                        const lastSeenCount = parseInt(localStorage.getItem('chat_user_seen_count') || '0');
                        // Alert standard user only if there are new updates and the admin was the last sender
                        if (count > lastSeenCount && lastMsg && lastMsg.sender_username === 'admin') {
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                }
            } catch (error) {
                console.error('Error polling notifications:', error);
            }
        }

        // Run interval updates
        checkGlobalMessages();
        setInterval(checkGlobalMessages, 3000);

        // Drop alert badge instantly when chat window gains focus or receives interaction
        if (isChatPage) {
            const clearBadge = () => {
                badge.style.display = 'none';
                checkGlobalMessages();
            };
            window.addEventListener('focus', clearBadge);
            document.addEventListener('click', clearBadge);
        }
    })();
</script>
