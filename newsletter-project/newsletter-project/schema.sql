-- schema.sql
-- Run this in phpMyAdmin (XAMPP/WAMP) or via `mysql -u root -p < schema.sql`


CREATE TABLE IF NOT EXISTS subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    topics VARCHAR(255) DEFAULT '',      -- comma-separated topic list, e.g. "Tech,Sports"
    status ENUM('active', 'unsubscribed') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Optional: a couple of sample rows so the admin table isn't empty on first run
INSERT INTO subscribers (name, email, topics, status) VALUES
('Test User', 'test.user@example.com', 'Technology,Sports', 'active');
