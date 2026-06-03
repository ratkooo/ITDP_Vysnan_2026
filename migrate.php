<?php

require_once __DIR__ . '/vendor/autoload.php';

// Exact baseline infrastructure parameters matching your active container mesh
$host = 'db_server';
$db   = 'portfolio_db';
$user = 'portfolio_user';
$pass = 'portfolio_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    echo "\033[36m🚀 Starting Database Migration Runner Engine...\033[0m\n";

    // --- HANDLE DATABASE REFRESH FLAG ---
    if (isset($argv) && in_array('--refresh', $argv)) {
        echo "\033[33m🔄 Refresh flag detected. Resetting database environment state...\033[0m\n";

        // Disable foreign key checks to prevent drop order restriction errors
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // Fetch all existing tables dynamically
        $tablesStmt = $pdo->query("SHOW TABLES");
        $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "🗑️ Dropped table: \033[31m$table\033[0m\n";
        }

        // Re-enable foreign key constraints
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "\033[32m✨ Database cleared successfully. Executing clean installation.\033[0m\n\n";
    }

    // 1. Ensure the system inventory tracking log exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration_name VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Scan the dedicated migrations sub-folder for SQL modules
    $migrationsDir = __DIR__ . '/migrations';
    if (!is_dir($migrationsDir)) {
        throw new Exception("Target migration path directory does not exist: $migrationsDir");
    }

    $files = glob($migrationsDir . '/*.sql');
    sort($files); // Process files sequentially in order (001, 002, etc.)

    // 3. Extract inventory history of changes already applied
    $executed = $pdo->query("SELECT migration_name FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
    $appliedCount = 0;

    foreach ($files as $file) {
        $migrationName = basename($file);

        // Skip the file if it has already been processed successfully
        if (in_array($migrationName, $executed)) {
            continue;
        }

        echo "⏳ Executing module: \033[33m$migrationName\033[0m... ";

        // Read and evaluate script parameters
        $sql = file_get_contents($file);
        if (trim($sql) !== '') {
            $pdo->exec($sql);
        }

        // Write trace record entry inside logging table mapping
        $stmt = $pdo->prepare("INSERT INTO migrations (migration_name) VALUES (?)");
        $stmt->execute([$migrationName]);

        echo "\033[32m✔ SUCCESS\033[0m\n";
        $appliedCount++;
    }

    // --- DYNAMIC & SAFE ADMIN SEEDING ---
    // This generates a 100% accurate mathematical hash for 'x63HaL6qcTzjij' at runtime
    $testUsername = 'admin';
    $testEmail = 'admin@gmail.com';
    $testPasswordHash = password_hash('x63HaL6qcTzjij', PASSWORD_BCRYPT);
    $testRole = 'admin';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$testUsername]);

    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $insert->execute([$testUsername, $testEmail, $testPasswordHash, $testRole]);
        echo "\033[32m👤 Default admin account seeded programmatically with dynamic BCRYPT hash.\033[0m\n";
    }

    if ($appliedCount === 0) {
        echo "\033[32m✨ Database structure matches schema completely. No migrations pending.\033[0m\n";
    } else {
        echo "\033[32m🎉 Success! Sequentially applied $appliedCount file updates.\033[0m\n";
    }

} catch (Exception $e) {
    echo "\033[31m❌ Migration Processing Failure:\033[0m " . $e->getMessage() . "\n";
    exit(1);
}