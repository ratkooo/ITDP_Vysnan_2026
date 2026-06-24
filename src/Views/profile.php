<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Profile Settings</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<header>
    <h1>Account Profile</h1>
</header>

<div class="container">
    <main>
        <h2>Edit Profile Information</h2>
        <p>Modify your account information.</p>

        <?php if (isset($_SESSION['profile_success'])) : ?>
            <div class="alert success" style="color: #28a745; font-weight: bold; margin-bottom: 15px;">
                <?= htmlspecialchars($_SESSION['profile_success']); ?>
                <?php unset($_SESSION['profile_success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['profile_error'])) : ?>
            <div class="alert error" style="color: #dc3545; font-weight: bold; margin-bottom: 15px;">
                <?= htmlspecialchars($_SESSION['profile_error']); ?>
                <?php unset($_SESSION['profile_error']); ?>
            </div>
        <?php endif; ?>

        <form action="/profile" method="POST" id="profile-form">
            <div>
                <label for="username">Username</label>
                <input type="text" id="username" name="username" 
                       value="<?= htmlspecialchars($userProfile['username'] ?? '') ?>" 
                       required autocomplete="username">
                <span id="username-error" class="validation-message"></span>
            </div>

            <div>
                <label for="email">Email Address</label>
                <input type="text" id="email" name="email" 
                       value="<?= htmlspecialchars($userProfile['email'] ?? '') ?>" 
                       required autocomplete="email">
                <span id="email-error" class="validation-message"></span>
            </div>

            <div>
                <label for="password">New Password (Leave blank to keep old password)</label>
                <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="new-password">
                <span id="password-error" class="validation-message"></span>
            </div>

            <button type="submit" id="submit-btn" class="btn" style="margin-top: 15px;">Save Settings</button>
        </form>
    </main>
</div>

<script>
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const submitBtn = document.getElementById('submit-btn');

    // Initial state is true because pre-loaded DB values are valid by default
    let statuses = { username: true, email: true, password: true };
    let debounceTimer;

    function applyFeedback(inputEl, errorEl, isValid, message) {
        if (isValid) {
            inputEl.classList.remove('invalid-field');
            inputEl.classList.add('valid-field');
            errorEl.className = "validation-message valid";
            errorEl.textContent = message;
        } else {
            inputEl.classList.remove('valid-field');
            inputEl.classList.add('invalid-field');
            errorEl.className = "validation-message invalid";
            errorEl.textContent = message;
        }
        toggleSubmitButton();
    }

    function toggleSubmitButton() {
        const formIsValid = statuses.username && statuses.email && statuses.password;
        submitBtn.disabled = !formIsValid;
    }

    // 1. Username Real-time Availability Watcher 
    usernameInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const val = usernameInput.value.trim();
        const errorEl = document.getElementById('username-error');

        if (val.length < 5) {
            statuses.username = false;
            applyFeedback(usernameInput, errorEl, false, "Username must be at least 5 characters long.");
            return;
        }

        errorEl.className = "validation-message";
        errorEl.textContent = "Checking availability...";

        debounceTimer = setTimeout(() => {
            fetch(`/api/check-availability?username=${encodeURIComponent(val)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.usernameExists) {
                        statuses.username = false;
                        applyFeedback(usernameInput, errorEl, false, "❌ This username is already taken by another account.");
                    } else {
                        statuses.username = true;
                        applyFeedback(usernameInput, errorEl, true, "✔ Username is available!");
                    }
                });
        }, 400);
    });

    // 2. Email Verification Layer (Format Check + Uniqueness API Check)
    emailInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const val = emailInput.value.trim();
        const errorEl = document.getElementById('email-error');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailRegex.test(val)) {
            statuses.email = false;
            applyFeedback(emailInput, errorEl, false, "Please enter a valid email format (e.g., name@domain.com).");
            return;
        }

        errorEl.className = "validation-message";
        errorEl.textContent = "Verifying email database parameters...";

        debounceTimer = setTimeout(() => {
            fetch(`/api/check-availability?email=${encodeURIComponent(val)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.emailExists) {
                        statuses.email = false;
                        applyFeedback(emailInput, errorEl, false, "❌ This email address is already registered to another account.");
                    } else {
                        statuses.email = true;
                        applyFeedback(emailInput, errorEl, true, "✔ Email is valid and available!");
                    }
                });
        }, 400);
    });

    // 3. Password Integrity Check (Optional field on profile view)
    passwordInput.addEventListener('input', () => {
        const val = passwordInput.value;
        const errorEl = document.getElementById('password-error');

        if (val.length > 0 && val.length < 8) {
            statuses.password = false;
            applyFeedback(passwordInput, errorEl, false, "New passwords must be at least 8 characters long.");
        } else {
            statuses.password = true;
            applyFeedback(passwordInput, errorEl, true, val.length > 0 ? "✔ Password length satisfies infrastructure rules." : "");
        }
    });
</script>
</body>
</html>