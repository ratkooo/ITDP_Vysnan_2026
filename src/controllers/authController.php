<?php

namespace App\Controllers;

use App\Repositories\UserRepositoryInterface;

class AuthController
{
    public function __construct(private readonly userRepositoryInterface $userRepository)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => false,
                'use_strict_mode' => true,
            ]);
        }
    }
    
    /**
     * ITDP Criteria: Security (OWASP A07:2025 - Authentication Failures)
     * Functional User Story: User registration and login for portfolio access.
     * Implements session-based authentication with password hashing (BCRYPT).
     */"

    public function showLogin(?string $error = null): void
    {
        require_once __DIR__ . '/../Views/login.php';
    }

    public function login(string $username, string $password): void
    {
        $user = $this->userRepository->findByUsername($username);

        if ($user && password_verify($password, $user->passwordHash)) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            $_SESSION['email'] = $user->email;
            $_SESSION['role'] = $user->role;

            header("Location: /");
            exit;
        }

        $this->showLogin("Invalid username or password configuration.");
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
        header("Location: /");
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

        if ($this->userRepository->findByUsername($username) !== null) {
            $this->showRegister("Username is already registered inside our system.");
            return;
        }

        if ($this->userRepository->findByEmail($email) !== null) {
            $this->showRegister("Email is already registered inside our system.");
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $success = $this->userRepository->insert($username, $email, $passwordHash, 'user');

        if ($success) {
            $this->showRegister(null, "Registration successful! You can now log in.");
        } else {
            $this->showRegister("An unexpected server error occurred during registration.");
        }
    }

    public static function requireRole(string $targetRole): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== $targetRole) {
            header('HTTP/1.1 403 Forbidden');
            echo "<h2 style='color:#dc2626; font-family:sans-serif; text-align:center; margin-top:4rem;'>
                    403 Unauthorized Access - " . htmlspecialchars(strtoupper($targetRole)) . " Token Required.
                  </h2>";
            exit;
        }
    }
    
    /**
     * ITDP Criteria: Security (OWASP A01:2025 - Broken Access Control)
     * Ensures only authorized users can access restricted features.
     */"
}
