CREATE TABLE IF NOT EXISTS messages (
                                        id INT AUTO_INCREMENT PRIMARY KEY,
                                        user_id INT NOT NULL,                   -- Grouping context (The regular user's thread)
                                        sender_id INT NOT NULL,                 -- Who authored the message string
                                        sender_username VARCHAR(255) NOT NULL,  -- Cached to prevent excessive JOIN lookups
    message_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );