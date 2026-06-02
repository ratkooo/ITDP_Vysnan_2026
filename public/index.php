<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\ProjectApiController;
use App\Repositories\MySQLUserRepository;

// =========================================================================
// 1. DATABASE CONFIGURATION (Multi-Container DevOps Step 3)
// =========================================================================
$host    = 'db_server'; // Matches the MySQL service name in docker-compose.yml
$db      = 'portfolio_db';
$user    = 'portfolio_user';
$pass    = 'portfolio_password';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Mitigation against SQL Injection (OWASP A05:2025)
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Safety fallback: ensure errors don't leak full connection traces
    http_response_code(500);
    die("Database infrastructure configuration error.");
}

// =========================================================================
// 2. DEPENDENCY INJECTION ENGINE
// =========================================================================
$userRepository = new MySQLUserRepository($pdo);
$authController = new AuthController($userRepository);
//$apiController  = new ProjectApiController();

// =========================================================================
// 3. REQUEST PARSING & CLEAN URL EXTRACTION
// =========================================================================
$requestUri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// =========================================================================
// 4. CENTRALIZED ARCHITECTURAL FRONT ROUTER SWITCH
// =========================================================================
switch ($requestUri) {

    // Core Homepage (Biographical Info & Client-Side API Showcase Area)
    case '/':
        require_once __DIR__ . '/../src/Views/home.php';
        break;

    // Academic Performance Tracking Dashboard
    case '/dashboard':
        // Restricts access to authenticated accounts (OWASP A01:2025 Broken Access Control Protection)
        AuthController::requireRole('admin');
        require_once __DIR__ . '/../src/Views/dashboard.php';
        break;

    // Blog Publishing Workflow Index Area
    case '/blog':
        require_once __DIR__ . '/../src/Views/blog/index.php';
        break;

    // Individual Isolated Blog Article View
    case '/blog/view':
        require_once __DIR__ . '/../src/Views/blog/view.php';
        break;

    // User Session Authentication Entrypoint
    case '/login':
        if ($requestMethod === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $authController->login($username, $password);
        } else {
            $authController->showLogin();
        }
        break;

    // Account Creation Management Endpoint
    case '/register':
        if ($requestMethod === 'POST') {
            $username        = $_POST['username'] ?? '';
            $email           = $_POST['email'] ?? '';
            $password        = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';
            $authController->register($username, $email, $password, $passwordConfirm);
        } else {
            $authController->showRegister();
        }
        break;

    // Destroy Authentication Token & Clear Session State
    case '/logout':
        $authController->logout();
        break;

    // RESTful JSON API Endpoint 1: Active Portfolio Projects Collection
    case '/api/projects':
        $apiController->getProjects();
        break;

    // RESTful JSON API Endpoint 2: System Health Monitor Parameters
    case '/api/status':
        $apiController->getSystemStatus();
        break;

    // Catch-All Exception Handling Page
    default:
        http_response_code(404);
        echo "<h1>404 Not Found</h1>";
        echo "<p>The requested route configuration structure does not exist on this application server.</p>";
        break;
}