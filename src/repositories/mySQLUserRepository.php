<?php

namespace App\Repositories;

use App\Models\User;
use PDO;

class MySQLUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}

    public function findByUsername(string $username): ?User
    {
        // OWASP Protection: Using prepared statements to block SQL Injections completely
        $stmt = $this->pdo->prepare("SELECT id, username, email, password, role FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return new User(
            (int)$row['id'],
            $row['username'],
            $row['email'],
            $row['password'],
            $row['role']
        );
    }

    public function findByEmail(string $email): ?User
    {
        // OWASP Protection: Using prepared statements to block SQL Injections completely
        $stmt = $this->pdo->prepare("SELECT id, username, email, password, role FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return new User(
            (int)$row['id'],
            $row['username'],
            $row['email'],
            $row['password'],
            $row['role']
        );
    }

    public function insert(string $username, string $email, string $passwordHash, string $role): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)");
        return $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $passwordHash,
            'role' => $role
        ]);
    }
}
