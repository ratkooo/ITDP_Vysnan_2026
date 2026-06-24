<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Login</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<nav>
    <div class="nav-left">
        <a href="/">Home</a>
        <a href="/blog">Blog</a>
    </div>
    <div class="nav-right">
        <a href="/login">Login</a>
        <a href="/register">Register</a>
    </div>
</nav>

<div class="container" style="display: flex; justify-content: center; align-items: center; min-height: 65vh;">
    <main style="max-width: 420px; width: 100%; background: #ffffff; padding: 2.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
        <h2 style="font-size: 1.85rem; color: #1e293b; margin-bottom: 0.5rem; font-weight: 700;">System Login</h2>
        <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 1.5rem;">
            Enter your credentials to log in.
        </p>

        <?php if (!empty($_SESSION['registration_success'])) : ?>
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; padding: 0.85rem; border-radius: 6px; margin-bottom: 1.25rem; font-size: 0.95rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem;">
                <span>✔</span> <span><?= htmlspecialchars($_SESSION['registration_success']); ?></span>
            </div>
            <?php unset($_SESSION['registration_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['login_error'])) : ?>
            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 0.85rem; border-radius: 6px; margin-bottom: 1.25rem; font-size: 0.95rem; font-weight: 500;">
                ❌ Invalid account credentials.
            </div>
            <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>

        <form action="/login" method="POST">
            <div style="margin-bottom: 1.25rem;">
                <label for="username" style="display: block; font-weight: 500; margin-bottom: 0.5rem; color: #334155;">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username" style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label for="password" style="display: block; font-weight: 500; margin-bottom: 0.5rem; color: #334155;">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password" style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <input type="checkbox" id="toggle-password-visibility" style="width: auto; margin: 0; cursor: pointer;">
                <label for="toggle-password-visibility" style="margin: 0; font-weight: 400; color: #475569; cursor: pointer; font-size: 0.95rem;">Show Password</label>
            </div>

            <button type="submit" class="btn" style="width: 100%; padding: 0.75rem; background: #0f172a; color: #ffffff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                Login
            </button>

            <div style="margin-top: 1.25rem; text-align: left; font-size: 0.95rem; color: #334155;">
                Click here to <a href="/register" style="color: #6366f1; text-decoration: underline; font-weight: 500;">register</a>
            </div>
        </form>
    </main>
</div>

<script>
    document.getElementById('toggle-password-visibility').addEventListener('change', function() {
        document.getElementById('password').type = this.checked ? 'text' : 'password';
    });
</script>
</body>
</html>
