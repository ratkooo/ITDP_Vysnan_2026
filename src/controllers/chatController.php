<?php

namespace App\Controllers;

use PDO;
use PDOException;

class ChatController
{
    private $pdo;

    public function __construct($pdo)
    {
        // Receives the database connection context
        $this->pdo = $pdo;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Route Destination: GET /chat
     * Prepares parameters and loads the chat room view interface
     */
    public function index()
    {
        // 1. Protection Gate: Redirect unauthorized clients to login
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        // 2. Extrapolate permission boolean context expected by chat.php
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

        // 3. Render view template layout
        require_once __DIR__ . '/../Views/chat.php';
    }

    /**
     * Endpoint: GET /api/chat-threads
     * Fetches all non-admin users to populate the admin's sidebar thread list with notification metrics
     */
    public function getThreads()
    {
        header('Content-Type: application/json');

        // Security Guard: Stop non-admins from scraping the account directory
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized access resource configuration.']);
            return;
        }

        try {
            // Evaluates thread statistics per user to feed individual notification badges
            $stmt = $this->pdo->query("
                SELECT 
                    u.id AS user_id, 
                    u.username,
                    u.username AS thread_owner,
                    (SELECT COUNT(*) FROM messages WHERE user_id = u.id) AS message_count,
                    (SELECT sender_username FROM messages WHERE user_id = u.id ORDER BY created_at DESC, id DESC LIMIT 1) AS last_sender
                FROM users u 
                WHERE u.role = 'user' 
                ORDER BY u.username ASC
            ");
            $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($threads);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database trace failure.']);
        }
    }

    /**
     * Endpoint: GET /api/chat-messages
     * Fetches message history for a chosen thread
     */
    public function getMessages()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }

        // Determine target channel thread context
        if ($_SESSION['role'] === 'admin') {
            $targetUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
        } else {
            $targetUserId = $_SESSION['user_id']; // Standard users only read their own thread
        }

        if (!$targetUserId) {
            echo json_encode([]);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT sender_username, message_text, created_at FROM messages WHERE user_id = :user_id ORDER BY created_at ASC");
            $stmt->execute(['user_id' => $targetUserId]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Query engine failure']);
        }
    }

    /**
     * Endpoint: POST /api/chat-send
     * Saves an incoming message
     */
    public function sendMessage()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Authentication required']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $messageText = isset($input['message']) ? trim($input['message']) : '';

        if (empty($messageText)) {
            echo json_encode(['success' => false, 'error' => 'Empty message payload context.']);
            return;
        }

        // Determine context thread channel routing
        if ($_SESSION['role'] === 'admin') {
            $targetUserId = isset($input['user_id']) ? intval($input['user_id']) : null;
        } else {
            $targetUserId = $_SESSION['user_id'];
        }

        if (!$targetUserId) {
            echo json_encode(['success' => false, 'error' => 'No active conversation channel target.']);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO messages (user_id, sender_id, sender_username, message_text) VALUES (:user_id, :sender_id, :sender_username, :message_text)");
            $stmt->execute([
                'user_id'         => $targetUserId,
                'sender_id'       => $_SESSION['user_id'],
                'sender_username' => $_SESSION['username'],
                'message_text'    => $messageText
            ]);

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Write error.']);
        }
    }
}
