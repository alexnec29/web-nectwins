CREATE DATABASE IF NOT EXISTS wow_db;
USE wow_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100)
);

-- Utilizator de test: username = testuser, parola = test123
INSERT INTO users (username, password, email)
VALUES ('testuser', '$2y$10$YwOtdbGsd9cz5zIlbzK2iO.x5DuAoySoEl3nb6xIvmdZnGoKkjqFu', 'test@example.com');
