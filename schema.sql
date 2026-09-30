-- Run this once in phpMyAdmin (local) or your hosted MySQL (Aiven).
CREATE DATABASE IF NOT EXISTS newsletter_db;
USE newsletter_db;

-- One table for everyone. role decides which portal you get.
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('user','admin') NOT NULL DEFAULT 'user',
    bio           VARCHAR(255) DEFAULT '',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Articles are written by admins (writers).
CREATE TABLE articles (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    author_id  INT NOT NULL,
    title      VARCHAR(200) NOT NULL,
    body       TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);

-- A user subscribes to an admin. The pair is unique, so no double subscriptions.
CREATE TABLE subscriptions (
    user_id    INT NOT NULL,
    admin_id   INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, admin_id),
    FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);
