<?php

namespace App\Models;

class Message
{
    public ?int $id;
    public int $userId;
    public int $senderId;
    public string $senderUsername;
    public string $messageText;
    public ?string $createdAt;

    public function __construct(
        ?int $id,
        int $userId,
        int $senderId,
        string $senderUsername,
        string $messageText,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->senderId = $senderId;
        $this->senderUsername = $senderUsername;
        $this->messageText = $messageText;
        $this->createdAt = $createdAt;
    }
}
