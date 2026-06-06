<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Repositories\MySQLUserRepository;
use App\Controllers\ChatController;
use App\Repositories\MySQLMessageRepository;
use App\Controllers\BiographyController;

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
$messageRepository = new MySQLMessageRepository($pdo);
$chatController = new ChatController($pdo);
$biographyController = new BiographyController($pdo);

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
        require __DIR__ . '/../src/Views/blog/index.php';
        break;

    case '/blogpost':
        require __DIR__ . '/../src/Views/blog/blog.php';
        break;

    case '/api/blog/posts':
        header('Content-Type: application/json');
        try {
            $stmt = $pdo->query("SELECT id, title, summary, author, created_at FROM blog_posts ORDER BY id DESC");
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($posts);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to retrieve publications."]);
        }
        exit;

    case '/api/blog/post-detail':
        header('Content-Type: application/json');
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        try {
            $stmt = $pdo->prepare("SELECT id, title, summary, content, author, created_at FROM blog_posts WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($post) {
                echo json_encode($post);
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Publication context not located."]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Database processing error."]);
        }
        exit;

    case '/api/blog/save':
        \App\Controllers\AuthController::requireRole('admin');
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method Rejection']);
            exit;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $title = trim($_POST['title'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if (empty($title) || empty($summary) || empty($content)) {
            echo json_encode(['success' => false, 'error' => 'All layout parameters mandatory.']);
            exit;
        }

        if ($id) {
            // Update (U)
            $stmt = $pdo->prepare("UPDATE blog_posts SET title = ?, summary = ?, content = ? WHERE id = ?");
            $success = $stmt->execute([$title, $summary, $content, $id]);
        } else {
            // Create (C)
            $stmt = $pdo->prepare("INSERT INTO blog_posts (title, summary, content) VALUES (?, ?, ?)");
            $success = $stmt->execute([$title, $summary, $content]);
        }

        echo json_encode(['success' => $success]);
        exit;

    case '/api/blog/delete':
        \App\Controllers\AuthController::requireRole('admin');
        header('Content-Type: application/json');

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = ?");
        $success = $stmt->execute([$id]);

        echo json_encode(['success' => $success]);
        exit;
    
    case '/register':
        if ($requestMethod === 'GET') {
            require_once __DIR__ . '/../src/Views/register.php';
            exit;
        }

        if ($requestMethod === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['password_confirm'] ?? '';

            if (strlen($username) < 5 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || $password !== $confirm) {
                $_SESSION['error'] = "Registration failed: Structural input criteria not satisfied.";
                header('Location: /register');
                exit;
            }

            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->fetch()) {
                    $_SESSION['error'] = "Username or email is already registered.";
                    header('Location: /register');
                    exit;
                }

                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                $insert = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                $insert->execute([$username, $email, $hashedPassword]);

                $_SESSION['registration_success'] = "Registration successful! You can now authenticate your session below.";
                header('Location: /login');
                exit;

            } catch (PDOException $e) {
                $_SESSION['error'] = "Database error encountered: " . $e->getMessage();
                header('Location: /register');
                exit;
            }
        }
        break;

    case '/login':
        if ($requestMethod === 'GET') {
            require_once __DIR__ . '/../src/Views/login.php';
            exit;
        }

        if ($requestMethod === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $_SESSION['login_error'] = "Please provide valid identity authorization parameters.";
                header('Location: /login');
                exit;
            }

            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    session_regenerate_id(true);

                    $_SESSION['user_id']  = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role']     = $user['role']; 

                    header('Location: /');
                    exit;
                } else {
                    $_SESSION['login_error'] = "Invalid verification matrix tokens. Please double check credentials.";
                    header('Location: /login');
                    exit;
                }
            } catch (PDOException $e) {
                $_SESSION['login_error'] = "Database connectivity failure exception encountered: " . $e->getMessage();
                header('Location: /login');
                exit;
            }
        }
        break;

    case '/logout':
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header('Location: /');
        exit;

    case '/dashboard':
        // Decoupled Route Gateway Shell - Authorization Gated Only
        \App\Controllers\AuthController::requireRole('admin');
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
        $status = trim($_POST['status'] ?? 'In Progress');

        if (empty($course_name)) {
            echo json_encode(['success' => false, 'error' => 'Invalid structural content parameters.']);
            exit;
        }

        if ($id) {
            $stmt = $pdo->prepare("UPDATE courses SET course_name = ?, ec_points = ?, grade = ?, status = ? WHERE id = ?");
            $success = $stmt->execute([$course_name, $ec_points, $grade, $status, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO courses (course_name, ec_points, grade, status) VALUES (?, ?, ?, ?)");
            $success = $stmt->execute([$course_name, $ec_points, $grade, $status]);
        }

        echo json_encode(['success' => $success]);
        exit;

    case '/api/dashboard/delete':
        \App\Controllers\AuthController::requireRole('admin');
        header('Content-Type: application/json');

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $success = $stmt->execute([$id]);

        echo json_encode(['success' => $success]);
        exit;

    case '/api/check-availability':
        header('Content-Type: application/json');

        $username = trim($_GET['username'] ?? '');
        $email    = trim($_GET['email'] ?? '');
        $currentUserId = $_SESSION['user_id'] ?? null;

        $usernameExists = false;
        $emailExists = false;

        if (!empty($username)) {
            if ($currentUserId) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$username, $currentUserId]);
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $stmt->execute([$username]);
            }
            $usernameExists = $stmt->fetchColumn() > 0;
        }

        if (!empty($email)) {
            if ($currentUserId) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $currentUserId]);
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $stmt->execute([$email]);
            }
            $emailExists = $stmt->fetchColumn() > 0;
        }

        echo json_encode([
            'usernameExists' => $usernameExists,
            'emailExists' => $emailExists
        ]);
        exit;

    case '/profile':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($requestMethod === 'GET') {
            $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $userProfile = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userProfile) {
                die("Account database profile parameter synchronization failure.");
            }

            require_once __DIR__ . '/../src/Views/profile.php';
            exit;
        }

        if ($requestMethod === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (strlen($username) < 5 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['profile_error'] = "Update rejected: Input criteria requirements structural failure.";
                header('Location: /profile');
                exit;
            }

            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $stmt->execute([$username, $email, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    $_SESSION['profile_error'] = "The username or email address configuration is already linked to another account profile.";
                    header('Location: /profile');
                    exit;
                }

                if (!empty($password)) {
                    if (strlen($password) < 8) {
                        $_SESSION['profile_error'] = "Password must be at least 8 characters long.";
                        header('Location: /profile');
                        exit;
                    }
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $update = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                    $update->execute([$username, $email, $hashedPassword, $_SESSION['user_id']]);
                } else {
                    $update = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                    $update->execute([$username, $email, $_SESSION['user_id']]);
                }

                $_SESSION['username'] = $username;
                $_SESSION['profile_success'] = "Profile metrics saved and applied successfully!";
                header('Location: /profile');
                exit;

            } catch (PDOException $e) {
                $_SESSION['profile_error'] = "Database processing configuration runtime error: " . $e->getMessage();
                header('Location: /profile');
                exit;
            }
        }
        break;

    case '/api/student-courses':
        header('Content-Type: application/json');
        try {
            // Standardized to fetch database primary keys to match decoupling specifications
            $stmt = $pdo->query("SELECT id, course_name, ec_points, grade, status FROM courses ORDER BY id ASC");
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($courses);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Database progress tracking matrix unavailable."]);
        }
        exit;

    case '/api/recent-registrations':
        header('Content-Type: application/json');
        $stmt = $pdo->query("SELECT username, role, created_at FROM users ORDER BY id DESC");
        $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($recentUsers);
        exit;

    case '/chat':
        $chatController->index();
        break;

    case '/api/chat-threads':
        $chatController->getThreads();
        break;

    case '/api/chat-messages':
        $chatController->getMessages();
        break;

    case '/api/chat-send':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $chatController->sendMessage();
        }
        break;

    case '/api/biography-data':
        $biographyController->getBiographyData();
        break;

    case '/api/admin/update-bio':
        $biographyController->updateBiography();
        break;

    case '/api/admin/skills/create':
        $biographyController->createSkill();
        break;

    case '/api/admin/skills/update':
        $biographyController->updateSkill();
        break;

    case '/api/admin/skills/delete':
        $biographyController->deleteSkill();
        break;

    default:
        http_response_code(404);
        echo "<h1>404 Not Found</h1>";
        echo "<p>The requested route configuration structure does not exist on this application server.</p>";
        break;
}