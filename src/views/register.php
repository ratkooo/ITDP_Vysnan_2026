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
    <div class="nav-left">
        <a href="/">Home</a>
        <a href="/blog">Blog</a>
    </div>
</nav>

<header>
    <h1>Account Registration</h1>
    <p>Create a secure visitor account</p>
</header>

<div class="container">
    <main>
        <h2>Register</h2>
        <p>Please enter your details below to create an account.</p>

        <form action="/register" method="POST" id="registration-form">
            <div>
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
                <span id="username-error" class="validation-message"></span>
            </div>

            <div>
                <label for="email">Email Address</label>
                <input type="text" id="email" name="email" required autocomplete="email">
                <span id="email-error" class="validation-message"></span>
            </div>

            <div>
                <label for="password">Password</label>
                <div class="input-inline-row">
                    <input type="password" id="password" name="password" required autocomplete="new-password">
                </div>
                <span id="password-error" class="validation-message"></span>
            </div>

            <div>
                <label for="password_confirm">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
                <span id="confirm-error" class="validation-message"></span>
                <div></div>
                <div class="checkbox-container">
                    <input type="checkbox" id="toggle-password-visibility">
                    <label for="toggle-password-visibility">Show Password</label>
                </div>
            </div>

            <button type="submit" id="submit-btn" class="btn">Register Account</button>
        </form>
    </main>

    <aside>
        <sidebar>
            <h2>Security Baseline</h2>
            <p>All verification rules run actively on input to avoid server rejections and adhere to the "Visibility of System Status" Heuristic.</p>
        </sidebar>
    </aside>
</div>

<script>
    // Track visual elements
    const form = document.getElementById('registration-form');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirm');
    const submitBtn = document.getElementById('submit-btn');

    // Validation trackers (Heuristic 5: Error Prevention State Map)
    let statuses = { username: false, email: false, password: false, confirm: false };
    let debounceTimer;

    // Visual helper function to apply feedback (Heuristic 1 & 9)
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

    // Toggles form submission authorization state (Heuristic 5)
    function toggleSubmitButton() {
        const formIsValid = statuses.username && statuses.email && statuses.password && statuses.confirm;
        submitBtn.disabled = !formIsValid;
    }

    // 1. Username Real-time Availability Watcher
    usernameInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const val = usernameInput.value.trim();
        const errorEl = document.getElementById('username-error');

        if (val.length < 3) {
            statuses.username = false;
            applyFeedback(usernameInput, errorEl, false, "Username must be at least 3 characters long.");
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
                        applyFeedback(usernameInput, errorEl, false, "❌ This username is already taken.");
                    } else {
                        statuses.username = true;
                        applyFeedback(usernameInput, errorEl, true, "✔ Username is available!");
                    }
                });
        }, 400); // 400ms debounce
    });

    // 2. Email Verification Layer (Format Check + Database Uniqueness Check)
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
                        applyFeedback(emailInput, errorEl, false, "❌ This email address is already registered.");
                    } else {
                        statuses.email = true;
                        applyFeedback(emailInput, errorEl, true, "✔ Email is valid and available!");
                    }
                });
        }, 400);
    });

    // 3. Password Integrity Check (Length constraint >= 8)
    passwordInput.addEventListener('input', () => {
        const val = passwordInput.value;
        const errorEl = document.getElementById('password-error');

        if (val.length < 8) {
            statuses.password = false;
            applyFeedback(passwordInput, errorEl, false, "Password must be at least 8 characters long.");
        } else {
            statuses.password = true;
            applyFeedback(passwordInput, errorEl, true, "✔ Password length satisfies infrastructure rules.");
        }
        // Trigger confirm check in case password changed after match
        validateConfirmation();
    });

    // 4. Password Double-Entry Match Check
    function validateConfirmation() {
        const errorEl = document.getElementById('confirm-error');
        if (confirmInput.value === passwordInput.value && passwordInput.value !== "") {
            statuses.confirm = true;
            applyFeedback(confirmInput, errorEl, true, "✔ Password inputs match perfectly.");
        } else {
            statuses.confirm = false;
            applyFeedback(confirmInput, errorEl, false, "Passwords do not match yet.");
        }
    }
    confirmInput.addEventListener('input', validateConfirmation);

    // Keep Show Password Toggle feature
    document.getElementById('toggle-registration-passwords').addEventListener('change', function() {
        const resolveType = this.checked ? 'text' : 'password';
        passwordInput.type = resolveType;
        confirmInput.type = resolveType;
    });
</script>
</body>
</html>