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

    case '/apipage':
        // Renders the dedicated frontend page demonstrating fetch() operations
        require_once __DIR__ . '/../src/Views/apipage.php';
        exit;

    // User Session Authentication Entrypoint
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

            // Server-side safety net validation check
            if (strlen($username) < 5 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || $password !== $confirm) {
                $_SESSION['error'] = "Registration failed: Structural input criteria not satisfied.";
                header('Location: /register');
                exit;
            }

            try {
                // Ensure duplicate credentials don't cause a database crash
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->fetch()) {
                    $_SESSION['error'] = "Username or email is already registered.";
                    header('Location: /register');
                    exit;
                }

                // Encrypt password using secure BCRYPT algorithm
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                // Insert user safely into the system database registry
                $insert = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                $insert->execute([$username, $email, $hashedPassword]);

                // --- SUCCESS SESSION BANNER FLAG SETTING ---
                $_SESSION['registration_success'] = "Registration successful! You can now authenticate your session below.";

                // Redirect user seamlessly to the login panel portal
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
                // Query system registry database mapping logs for matching account tracking entries
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Authenticate matching cryptographic signature tracks
                if ($user && password_verify($password, $user['password'])) {
                    // Regenerate session tokens to insulate against hijacking exploits (OWASP A04)
                    session_regenerate_id(true);

                    // Write explicit operational values into active session space
                    $_SESSION['user_id']  = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role']     = $user['role']; // 'student' or 'admin'

                    // SUCCESS DIRECTIVE: Smooth user redirect to the main home landing timeline portal
                    header('Location: /');
                    exit;
                } else {
                    // Graceful feedback for failed authorization attempts (Heuristic 9)
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

    // Destroy Authentication Token & Clear Session State
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
        $email    = trim($_GET['email'] ?? '');
        $currentUserId = $_SESSION['user_id'] ?? null; // Identify if a session exists

        $usernameExists = false;
        $emailExists = false;

        // Verify username uniqueness against other database records
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

        // Verify email uniqueness against other database records
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
        // Auth gate protection layer check
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($requestMethod === 'GET') {
            // Pull the latest up-to-date registry data metrics directly from the DB
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

            // Secure validation backup baseline check (Username minimal constraints = 5)
            if (strlen($username) < 5 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['profile_error'] = "Update rejected: Input criteria requirements structural failure.";
                header('Location: /profile');
                exit;
            }

            try {
                // Ensure the requested credentials aren't stolen or duplicated by another user (id != current user)
                $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $stmt->execute([$username, $email, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    $_SESSION['profile_error'] = "The username or email address configuration is already linked to another account profile.";
                    header('Location: /profile');
                    exit;
                }

                // If updating password along with text records
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
                    // Update only text properties context boundaries
                    $update = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                    $update->execute([$username, $email, $_SESSION['user_id']]);
                }

                // Sync the active runtime memory session variables state immediately
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
            // Pull tracking records directly from your database table
            $stmt = $pdo->query("SELECT course_name, ec_points, grade, status FROM courses ORDER BY id ASC");
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($courses);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Database progress tracking matrix unavailable."]);
        }
        exit;

    // ==========================================
    // ASSIGNMENT: RESTful API ENDPOINT 2
    // Returns a dynamic structured array list
    // ==========================================
    case '/api/recent-registrations':
        header('Content-Type: application/json');

        // Fetch up to 5 recent users without exposing passwords
        $stmt = $pdo->query("SELECT username, role, created_at FROM users ORDER BY id DESC");
        $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($recentUsers);
        exit;
}