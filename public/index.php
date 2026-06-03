<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\ProjectApiController;
use App\Repositories\MySQLUserRepository;

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'use_strict_mode' => true,
    ]);
}
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

    case '/dashboard':
        // Enforce strict protection checking right at the gateway line
        \App\Controllers\AuthController::requireRole('admin');

        // Safeguard connection parameters against infrastructure dropping
        if (!isset($pdo)) {
            die("Database infrastructure configuration error. Global connection vector down.");
        }

        // Fetch dynamic roadmap blocks
        $stmt = $pdo->query("SELECT * FROM courses ORDER BY id ASC");
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate dynamic total metric metrics
        $passedStmt = $pdo->query("SELECT SUM(ec_points) FROM courses WHERE status = 'Passed'");
        $totalPassedEC = (int)$passedStmt->fetchColumn();

        require_once __DIR__ . '/../src/Views/dashboard.php';
        break;

    case '/api/dashboard/save':
        \App\Controllers\AuthController::requireRole('admin');
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Method rejection']);
            exit;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $course_name = trim($_POST['course_name'] ?? '');
        $ec_points = (int)($_POST['ec_points'] ?? 0);
        $grade = trim($_POST['grade'] ?? '-');
        $status = trim($_POST['status'] ?? 'Active Run');

        if (empty($course_name)) {
            echo json_encode(['success' => false, 'error' => 'Invalid structural content parameters.']);
            exit;
        }

        if ($id) {
            // Update existing entry statement track
            $stmt = $pdo->prepare("UPDATE courses SET course_name = ?, ec_points = ?, grade = ?, status = ? WHERE id = ?");
            $success = $stmt->execute([$course_name, $ec_points, $grade, $status, $id]);
        } else {
            // Create a brand new record profile inside the cluster
            $stmt = $pdo->prepare("INSERT INTO courses (course_name, ec_points, grade, status) VALUES (?, ?, ?, ?)");
            $success = $stmt->execute([$course_name, $ec_points, $grade, $status]);
        }

        echo json_encode(['success' => $success]);
        exit;

    case '/api/dashboard/delete':
        \App\Controllers\AuthController::requireRole('admin');
        header('Content-Type: application/json');

        $id = isset($_POST['id']) ? (int)$POST['id'] : 0;
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $success = $stmt->execute([$id]);

        echo json_encode(['success' => $success]);
        exit;
    // RESTful JSON API Endpoint: Dynamic Account Availability Engine
    case '/api/check-availability':
        header('Content-Type: application/json');

        $username = trim($_GET['username'] ?? '');
        $email = trim($_GET['email'] ?? '');

        $usernameExists = false;
        $emailExists = false;

        // Dynamic queries against the database engine connection mapping
        if (!empty($username)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $usernameExists = $stmt->fetchColumn() > 0;
        }

        if (!empty($email)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $emailExists = $stmt->fetchColumn() > 0;
        }

        echo json_encode([
            'usernameExists' => $usernameExists,
            'emailExists' => $emailExists
        ]);
        exit;
}