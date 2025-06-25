-- Şterge vechile tabele (în ordine inversă a dependențelor)
DROP TABLE IF EXISTS workout_session CASCADE;
DROP TABLE IF EXISTS workout_exercise CASCADE;
DROP TABLE IF EXISTS exercise_health_condition CASCADE;
DROP TABLE IF EXISTS user_health_condition CASCADE;
DROP TABLE IF EXISTS exercise_goal CASCADE;
DROP TABLE IF EXISTS exercise_section CASCADE;
DROP TABLE IF EXISTS exercise_location CASCADE;
DROP TABLE IF EXISTS exercise_muscle_group CASCADE;
DROP TABLE IF EXISTS workout CASCADE;
DROP TABLE IF EXISTS exercise CASCADE;
DROP TABLE IF EXISTS training_goal CASCADE;
DROP TABLE IF EXISTS health_condition CASCADE;
DROP TABLE IF EXISTS split_subtype_muscle_group CASCADE;
DROP TABLE IF EXISTS split_subtype CASCADE;
DROP TABLE IF EXISTS section_split CASCADE;
DROP TABLE IF EXISTS split_type CASCADE;
DROP TABLE IF EXISTS training_level CASCADE;
DROP TABLE IF EXISTS training_type CASCADE;
DROP TABLE IF EXISTS muscle_subgroup CASCADE;
DROP TABLE IF EXISTS muscle_group CASCADE;
DROP TABLE IF EXISTS location CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- 1. Utilizatori
CREATE TABLE users (
   id       SERIAL PRIMARY KEY,
   username VARCHAR(50) NOT NULL UNIQUE,
   password VARCHAR(255) NOT NULL,
   email    VARCHAR(100) NOT NULL UNIQUE,
   nume     VARCHAR(100),
   varsta   INT,
   gen      VARCHAR(20),
   inaltime INT,
   greutate INT,
   rol      INT DEFAULT 1 CHECK (rol IN (1, 2, 3))
);

-- 2. Grupe musculare
CREATE TABLE muscle_group (
   id   SERIAL PRIMARY KEY,
   name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE muscle_subgroup (
   id               SERIAL       PRIMARY KEY,
   name             VARCHAR(100) NOT NULL UNIQUE,
   principal_group  INTEGER      NOT NULL
     REFERENCES muscle_group(id) ON DELETE CASCADE
);

-- 3. Tipuri de antrenament (modalitate: Gym, Kineto, Fizio)
CREATE TABLE training_type (
   id   SERIAL PRIMARY KEY,
   name VARCHAR(50) NOT NULL UNIQUE
);

-- 4. Nivel de dificultate
CREATE TABLE training_level (
   id   SERIAL PRIMARY KEY,
   name VARCHAR(50) NOT NULL UNIQUE
);

-- 5. Split-uri
CREATE TABLE split_type (
   id   SERIAL PRIMARY KEY,
   name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE section_split (
    id SERIAL PRIMARY KEY,
    section   VARCHAR(20) NOT NULL,
    split_id  INTEGER      NOT NULL REFERENCES split_type(id) ON DELETE CASCADE,
    UNIQUE (section, split_id)
);

-- 6. Sub‐tipuri de split și legătura cu grupele musculare
CREATE TABLE split_subtype (
    id       SERIAL PRIMARY KEY,
    name     VARCHAR(50) NOT NULL,
    split_id INTEGER      NOT NULL REFERENCES split_type(id) ON DELETE CASCADE,
    UNIQUE (name, split_id)
);

CREATE TABLE split_subtype_muscle_group (
    id                   SERIAL PRIMARY KEY,
    split_subtype_id     INTEGER NOT NULL REFERENCES split_subtype(id) ON DELETE CASCADE,
    muscle_group_id      INTEGER NOT NULL REFERENCES muscle_group(id) ON DELETE CASCADE,
    UNIQUE (split_subtype_id, muscle_group_id)
);

-- 7. Locații (Gym/Kineto/Fizio)
CREATE TABLE location (
   id      SERIAL PRIMARY KEY,
   name    VARCHAR(50) NOT NULL UNIQUE,
);

CREATE TABLE location_section (
    location_id INTEGER REFERENCES location(id) ON DELETE CASCADE,
    section     VARCHAR(20) NOT NULL,
    PRIMARY KEY (location_id, section)
);

-- 8. Exerciții
CREATE TABLE exercise (
   id               SERIAL       PRIMARY KEY,
   name             VARCHAR(100) NOT NULL,
   description      TEXT,
   dificulty        INTEGER      REFERENCES training_level(id),
   type_id          INTEGER      REFERENCES training_type(id),
   is_bodyweight    BOOLEAN      NOT NULL DEFAULT FALSE,
   equipment_needed BOOLEAN      NOT NULL DEFAULT FALSE,
   link             VARCHAR(100)
);

CREATE TABLE exercise_location (
    exercise_id INTEGER REFERENCES exercise(id) ON DELETE CASCADE,
    location_id INTEGER REFERENCES location(id) ON DELETE CASCADE,
    PRIMARY KEY (exercise_id, location_id)
);

CREATE TABLE exercise_muscle_group (
   exercise_id        INTEGER REFERENCES exercise(id)         ON DELETE CASCADE,
   muscle_subgroup_id INTEGER REFERENCES muscle_subgroup(id) ON DELETE CASCADE,
   PRIMARY KEY (exercise_id, muscle_subgroup_id)
);

CREATE TABLE exercise_section (
    exercise_id INTEGER REFERENCES exercise(id) ON DELETE CASCADE,
    section     VARCHAR(20) NOT NULL,
    PRIMARY KEY (exercise_id, section)
);

-- 9. Probleme de sănătate
CREATE TABLE health_condition (
    id   SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE user_health_condition (
    user_id      INTEGER REFERENCES users(id)             ON DELETE CASCADE,
    condition_id INTEGER REFERENCES health_condition(id) ON DELETE CASCADE,
    PRIMARY KEY (user_id, condition_id)
);

CREATE TABLE exercise_health_condition (
    exercise_id  INTEGER REFERENCES exercise(id)         ON DELETE CASCADE,
    condition_id INTEGER REFERENCES health_condition(id) ON DELETE CASCADE,
    PRIMARY KEY (exercise_id, condition_id)
);

-- 10. Obiective de antrenament (Forță / Enduranță / Hipertrofie)
CREATE TABLE training_goal (
    id   SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- 11. Workouts
CREATE TABLE workout (
   id               SERIAL       PRIMARY KEY,
   name             VARCHAR(100) NOT NULL,
   user_id          INTEGER      NOT NULL REFERENCES users(id) ON DELETE CASCADE,
   duration_minutes INTEGER      NOT NULL CHECK (duration_minutes > 0),
   type_id          INTEGER      REFERENCES training_type(id),
   level_id         INTEGER      REFERENCES training_level(id),
   split_id         INTEGER      REFERENCES split_type(id),
   location_id      INTEGER      REFERENCES location(id),
   goal_id          INTEGER      REFERENCES training_goal(id),    -- nou
   section          VARCHAR(20)  NOT NULL,
   created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
   started_at       TIMESTAMP,
   completed_at     TIMESTAMP,
   completed_count  INTEGER      DEFAULT 0
);

CREATE TABLE workout_exercise (
   workout_id       INTEGER REFERENCES workout(id) ON DELETE CASCADE,
   exercise_id      INTEGER REFERENCES exercise(id) ON DELETE CASCADE,
   order_in_workout INTEGER NOT NULL,
   sets             INTEGER,
   reps             INTEGER,
   PRIMARY KEY (workout_id, exercise_id)
);

CREATE TABLE workout_session (
    id              SERIAL PRIMARY KEY,
    workout_id      INTEGER NOT NULL REFERENCES workout(id) ON DELETE CASCADE,
    user_id         INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    started_at      TIMESTAMP,
    completed_at    TIMESTAMP,
    completed_count INTEGER DEFAULT 0
);

-- NOTĂ PENTRU LOGICĂ:
-- Dacă un utilizator nu are nicio condiție de sănătate => i se vor afișa toate exercițiile.
-- Dacă are condiții => i se vor afișa doar exercițiile asociate.
