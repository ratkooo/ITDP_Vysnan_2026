<?php
namespace App\Repositories;

use App\Models\Message;

interface MessageRepositoryInterface {
    public function getActiveThreads(): array;
    public function getMessagesByUserId(int $userId): array;
    public function save(Message $message): bool;
}