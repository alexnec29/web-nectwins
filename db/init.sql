CREATE DATABASE IF NOT EXISTS wow_db;
USE wow_db;

DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE
);

INSERT INTO users (username, password, email) VALUES
('admin', SHA2('admin123', 256), 'admin@example.com'),
('test', SHA2('testpass', 256), 'test@example.com');

