<?php

namespace App\Repositories;

use App\Models\Message;

/**
 * Interface for Message Repository.
 */
interface MessageRepositoryInterface
{
    /**
     * Retrieves a list of active conversation threads.
     * @return array<int, array<string, mixed>>
     */
    public function getActiveThreads(): array;

    /**
     * Retrieves all messages associated with a specific user ID.
     * @param int $userId
     * @return array<int, array<string, mixed>>
     */
    public function getMessagesByUserId(int $userId): array;

    /**
     * Saves a new message to the repository.
     * @param Message $message
     * @return bool
     */
    public function save(Message $message): bool;
}
