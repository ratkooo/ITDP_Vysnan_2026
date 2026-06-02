<?php

namespace App\controllers;

use App\Repositories\userRepositoryInterface;

class authController
{
    public function __construct(private readonly userRepositoryInterface $userRepository)
    {
        if (session_status() === PHP_SESSION_NONE) {
            // OWASP Security: Ensuring session configuration matches security baselines
            session_start([
                'cookie_httponly' => true, // Blocks XSS from reading session tokens
                'cookie_secure' => false,  // Set to true in your production remote HTTPS build!
                'use_strict_mode' => true,
            ]);
        }
    }

    public function showLogin(?string $error = null): void
    {
        require_once __DIR__ . '/../Views/login.php';
    }

    public function login(string $username, string $password): void
    {
        $user = $this->userRepository->findByUsername($username);

        // Security timing-attack and leak protection: Always use native password_verify
        if ($user && password_verify($password, $user->passwordHash)) {
            // Regenerate session ID to completely prevent Session Fixation attacks
            session_regenerate_id(true);

            $_isLoggedIn = true;
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            $_SESSION['email'] = $user->email;
            $_SESSION['role'] = $user->role;

            header("Location: /");
            exit;
        }

        // Keep errors vague to prevent malicious account enumeration
        $this->showLogin("Invalid username or password configuration.");
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header("Location: /login");
        exit;
    }

    public function showRegister(?string $error = null, ?string $success = null): void
    {
        require_once __DIR__ . '/../Views/register.php';
    }

    public function register(string $username, string $email, string $password, string $passwordConfirm): void
    {
        // 1. Basic validation checks
        if (empty($username) || empty($password) || empty($email)) {
            $this->showRegister("All form fields are required.");
            return;
        }

        if ($password !== $passwordConfirm) {
            $this->showRegister("Passwords do not match.");
            return;
        }

        if (strlen($password) < 8) {
            $this->showRegister("Password must be at least 8 characters long.");
            return;
        }

        // 2. Check if username is already taken
        if ($this->userRepository->findByUsername($username) !== null) {
            $this->showRegister("Username is already registered inside our system.");
            return;
        }

        if ($this->userRepository->findByEmail($email) !== null) {
            $this->showRegister("Email is already registered inside our system.");
            return;
        }

        // 3. OWASP A07:2025 Mitigation - Secure Password Hashing
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // 4. Save to database (defaulting to the 'student' role)
        $success = $this->userRepository->insert($username, $email, $passwordHash, 'student');

        if ($success) {
            $this->showRegister(null, "Registration successful! You can now log in.");
        } else {
            $this->showRegister("An unexpected server error occurred during registration.");
        }
    }

    public static function requireRole(string $requiredRole): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'use_strict_mode' => true,
            ]);
        }

        // Check 1: Verify the session context contains an active login record [cite: 61]
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }

        // Check 2: Verify the user holds the proper clearance role level [cite: 63]
        if ($_SESSION['role'] !== $requiredRole) {
            http_response_code(403);
            echo "<h1>403 Forbidden</h1>";
            echo "<p>Access Denied: Your account role does not have permission to view this resource.</p>";
            exit;
        }
    }
}