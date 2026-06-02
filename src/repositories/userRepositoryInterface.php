<?php

namespace App\repositories;

use App\Models\User;

interface UserRepositoryInterface
{
    public function findByUsername(string $username): ?User;
    public function findByEmail(string $email): ?User;
    public function insert(string $username, string $email, string $passwordHash, string $role): bool;
}