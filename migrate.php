<?php

require_once __DIR__ . '/vendor/autoload.php';

// Point to our SQLite database file
$dbPath = __DIR__ . '/database/database.sqlite';

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Starting database migrations...\n";

    // 1. Create Blog Posts Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        status TEXT DEFAULT 'draft',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");
    echo "✔ Created 'posts' table successfully.\n";

    // 2. Create Study Progress Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS course_progress (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        course_name TEXT NOT NULL,
        ec_points INTEGER NOT NULL,
        grade REAL,
        status TEXT DEFAULT 'not_started'
    );");
    echo "✔ Created 'course_progress' table successfully.\n";

    echo "All migrations completed successfully!\n";

} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}