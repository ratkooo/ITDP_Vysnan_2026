<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Account</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<nav>
    <a href="/">Home</a>
    <a href="/blog">Blog</a>
    <a href="/dashboard">Study Dashboard</a>
    <a href="/login">Login</a>
    <a href="/register">Register</a>
</nav>

<header>
    <h1>Account Registration</h1>
    <p>Create a secure student or visitor profile</p>
</header>

<div class="container">
    <main>
        <h2>Register</h2>
        <p>Please enter your details below to initialize your credentials.</p>

        <?php if (!empty($error)): ?>
            <p class="text-error"><?= htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p class="text-success"><?= htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <form action="/register" method="POST">
            <div>
                <label for="email">Email</label>
                <input type="text" id="email" name="email" required autocomplete="email">
            </div>

            <div>
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>

            <div>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
            </div>

            <div>
                <label for="password_confirm">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn">Register Account</button>
        </form>

        <p>
            <a href="/login">Already have an account? Log in here.</a>
        </p>
    </main>

    <aside>
        <sidebar>
            <h2>Security Baseline</h2>
            <p>All passwords are encrypted natively using strong cryptographic hashing functions (BCRYPT) to protect against credential leaks and security risks.</p>
        </sidebar>
    </aside>
</div>

<footer>
    <p>&copy; <?= date('Y'); ?> Portfolio App. Built using PSR-12 standards.</p>
</footer>

</body>
</html>