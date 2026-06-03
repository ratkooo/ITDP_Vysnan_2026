<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Authentication</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .login-card {
            max-width: 400px;
            margin: 5rem auto;
            background: white;
            padding: 2.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid var(--border);
            border-radius: 4px;
            box-sizing: border-box;
        }
        .alert-error {
            background: #fee2e2;
            color: var(--error);
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<nav>
    <div class="nav-left">
        <a href="/">Home</a>
        <a href="/blog">Blog</a>
        <?php if (session_status() == PHP_SESSION_NONE) session_start();  ?>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="/dashboard">Study Dashboard</a>
        <?php endif; ?>
    </div>

    <div class="nav-right">
        <?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/profile"><?= htmlspecialchars($_SESSION['username']); ?></a>
            <a href="/logout">Logout</a>
        <?php endif; ?>
    </div>
</nav>

<div class="login-card">
    <h2>Login</h2>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="/login" method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autocomplete="username">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <div class="checkbox-container">
            <input type="checkbox" id="toggle-password-visibility">
            <label for="toggle-password-visibility">Show Password</label>
        </div>
        <button type="submit" class="btn" style="width: 100%;">Login</button>
        <div class="form-group">Click here to
        <a href="/register"> register</a>
        </div>
    </form>
</div>
</body>
</html>

<script>
    document.getElementById('toggle-password-visibility').addEventListener('change', function() {
        const passwordInput = document.getElementById('password');
        passwordInput.type = this.checked ? 'text' : 'password';
    });
</script>