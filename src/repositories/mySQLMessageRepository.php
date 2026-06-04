<?php

namespace App\Repositories;

use App\Models\Message;
use PDO;

/**
 * ITDP Criteria: Security (OWASP A05:2025 - Injection Prevention)
 * Functional User Story: Support chat messaging system with thread management.
 * Prepared statements prevent SQL injection via parameterized queries.
 */
class MySQLMessageRepository implements MessageRepositoryInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getActiveThreads(): array
    {
        $stmt = $this->db->query("
            SELECT DISTINCT m.user_id, u.username as thread_owner 
            FROM messages m 
            JOIN users u ON m.user_id = u.id 
            ORDER BY m.id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMessagesByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY created_at ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save(Message $message): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO messages (user_id, sender_id, sender_username, message_text) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $message->userId,
            $message->senderId,
            $message->senderUsername,
            $message->messageText
        ]);
    }
}
