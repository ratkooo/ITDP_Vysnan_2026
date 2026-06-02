<?php

namespace App\Models;

class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly string $role = 'user',
    ) {}
}