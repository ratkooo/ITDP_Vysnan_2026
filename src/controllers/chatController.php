<?php

namespace App\Controllers;

use PDO;
use PDOException;

/**
 * ChatController manages secure messaging and thread retrieval.
 */
class ChatController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        include_once __DIR__ . '/../Views/chat.php';
    }

    public function getThreads(): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized access resource configuration.']);
            return;
        }

        try {
            $sql = "
                SELECT 
                    u.id AS user_id, 
                    u.username,
                    u.username AS thread_owner,
                    (SELECT COUNT(*) FROM messages WHERE user_id = u.id) AS message_count,
                    (SELECT sender_username FROM messages 
                    WHERE user_id = u.id ORDER BY created_at DESC, id DESC LIMIT 1) AS last_sender
                FROM users u 
                WHERE u.role = 'user' 
                ORDER BY u.username ASC
            ";
            $stmt = $this->pdo->query($sql);

            if ($stmt !== false) {
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            } else {
                throw new PDOException("Query execution failed.");
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database trace failure.']);
        }
    }

    public function getMessages(): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }

        $targetUserId = ($_SESSION['role'] === 'admin')
            ? (isset($_GET['user_id']) ? (int)$_GET['user_id'] : null)
            : $_SESSION['user_id'];

        if (!$targetUserId) {
            echo json_encode([]);
            return;
        }

        try {
            $stmt = $this->pdo->prepare(
                "SELECT sender_username, message_text, created_at 
            FROM messages WHERE user_id = :user_id ORDER BY created_at ASC"
            );

            if ($stmt !== false) {
                $stmt->execute(['user_id' => $targetUserId]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            } else {
                throw new PDOException("Statement preparation failed.");
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Query engine failure']);
        }
    }

    public function sendMessage(): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Authentication required']);
            return;
        }

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput !== false ? $rawInput : '', true);
        $messageText = isset($input['message']) ? trim((string)$input['message']) : '';

        if (empty($messageText)) {
            echo json_encode(['success' => false, 'error' => 'Empty message payload context.']);
            return;
        }

        $targetUserId = ($_SESSION['role'] === 'admin')
            ? (isset($input['user_id']) ? (int)$input['user_id'] : null)
            : $_SESSION['user_id'];

        if (!$targetUserId) {
            echo json_encode(['success' => false, 'error' => 'No active conversation channel target.']);
            return;
        }

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO messages (user_id, sender_id, sender_username, message_text) 
            VALUES (:user_id, :sender_id, :sender_username, :message_text)"
            );

            if ($stmt !== false) {
                $stmt->execute(
                    [
                    'user_id'         => $targetUserId,
                    'sender_id'       => $_SESSION['user_id'],
                    'sender_username' => $_SESSION['username'],
                    'message_text'    => $messageText
                    ]
                );
                echo json_encode(['success' => true]);
            } else {
                throw new PDOException("Insertion preparation failed.");
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Write error.']);
        }
    }
}
