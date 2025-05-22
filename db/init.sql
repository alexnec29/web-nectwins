DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id SERIAL PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  nume VARCHAR(100),
  varsta INT,
  gen VARCHAR(20),
  inaltime INT,
  greutate INT,
  conditie TEXT
);