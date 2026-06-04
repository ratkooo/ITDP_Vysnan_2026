<?php

namespace App\Repositories;

use App\Models\Message;
use PDO;

class MySQLMessageRepository implements MessageRepositoryInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getActiveThreads(): array
    {
        // Admin View: Grab distinct users who have an ongoing conversation thread
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
