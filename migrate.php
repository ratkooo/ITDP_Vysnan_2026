<?php

require_once __DIR__ . '/vendor/autoload.php';

$host = 'db_server';
$db   = 'portfolio_db';
$user = 'portfolio_user';
$pass = 'portfolio_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "Running MySQL migrations...\n";

    // Create secure users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'student',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    echo "✔ Created 'users' table structure.\n";

    // Insert dummy user if it does not exist
    $testUsername = 'admin';
    // Use standard BCRYPT hashing algorithm
    $testPasswordHash = password_hash('password123', PASSWORD_BCRYPT);
    $testEmail = 'admin@gmail.com';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$testUsername]);

    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
        $insert->execute([$testUsername, $testEmail, $testPasswordHash]);
        echo "✔ Seeded default admin credentials (Username: admin / Email: admin@gmail.com / Password: password123).\n";
    }

    echo "All database configurations fully migrated!\n";

} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}