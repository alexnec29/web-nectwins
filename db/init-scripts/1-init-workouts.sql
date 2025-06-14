DROP TABLE IF EXISTS workout_exercise CASCADE;
DROP TABLE IF EXISTS exercise_muscle_group CASCADE;
DROP TABLE IF EXISTS workout CASCADE;
DROP TABLE IF EXISTS exercise CASCADE;
DROP TABLE IF EXISTS muscle_subgroup CASCADE;
DROP TABLE IF EXISTS muscle_group CASCADE;
DROP TABLE IF EXISTS training_type CASCADE;
DROP TABLE IF EXISTS training_level CASCADE;
DROP TABLE IF EXISTS split_type CASCADE;
DROP TABLE IF EXISTS location CASCADE;
DROP TABLE IF EXISTS users;

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
   rol      int DEFAULT 1 CHECK (rol IN (1, 2, 3))
);

create table muscle_group (
   id   serial primary key,
   name varchar(100) not null unique
);

CREATE TABLE muscle_subgroup (
   id               serial       PRIMARY KEY,
   name             varchar(100) NOT NULL UNIQUE,
   principal_group  integer      NOT NULL
     REFERENCES muscle_group(id) ON DELETE CASCADE
);

CREATE TABLE training_type (
   id   serial       PRIMARY KEY,
   name varchar(50)  NOT NULL UNIQUE
);

CREATE TABLE training_level (
   id   serial       PRIMARY KEY,
   name varchar(50)  NOT NULL UNIQUE
);

CREATE TABLE split_type (
   id   serial       PRIMARY KEY,
   name varchar(50)  NOT NULL UNIQUE
);

-- Legatura dintre split_type si sectiune (gym, kineto, fizio etc.)
CREATE TABLE section_split (
    id SERIAL PRIMARY KEY,
    section VARCHAR(20) NOT NULL,
    split_id INTEGER NOT NULL REFERENCES split_type(id) ON DELETE CASCADE,
    UNIQUE (section, split_id)
);

CREATE TABLE split_subtype (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    split_id INTEGER NOT NULL REFERENCES split_type(id) ON DELETE CASCADE,
    UNIQUE (name, split_id)
);

CREATE TABLE split_subtype_muscle_group (
    id SERIAL PRIMARY KEY,
    split_subtype_id INTEGER NOT NULL REFERENCES split_subtype(id) ON DELETE CASCADE,
    muscle_group_id INTEGER NOT NULL REFERENCES muscle_group(id) ON DELETE CASCADE,
    UNIQUE (split_subtype_id, muscle_group_id)
);

CREATE TABLE location (
   id   serial       PRIMARY KEY,
   name varchar(50)  NOT NULL UNIQUE,
   section VARCHAR(20) NOT NULL
);

CREATE TABLE exercise (
   id               serial       PRIMARY KEY,
   name             varchar(100) NOT NULL,
   description      text,
   dificulty        integer      REFERENCES training_level(id),
   type_id          integer      REFERENCES training_type(id),
   is_bodyweight    boolean      NOT NULL DEFAULT false,
   equipment_needed boolean      NOT NULL DEFAULT false,
   link             varchar(100)
);

CREATE TABLE exercise_location (
    exercise_id INTEGER REFERENCES exercise(id) ON DELETE CASCADE,
    location_id INTEGER REFERENCES location(id) ON DELETE CASCADE,
    PRIMARY KEY (exercise_id, location_id)
);

CREATE TABLE exercise_muscle_group (
   exercise_id        integer REFERENCES exercise(id)         ON DELETE CASCADE,
   muscle_subgroup_id integer REFERENCES muscle_subgroup(id) ON DELETE CASCADE,
   PRIMARY KEY (exercise_id, muscle_subgroup_id)
);

-- Adăugăm tabela pentru probleme de sănătate
CREATE TABLE health_condition (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- Legătura many-to-many între user și probleme de sănătate
CREATE TABLE user_health_condition (
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    condition_id INTEGER REFERENCES health_condition(id) ON DELETE CASCADE,
    PRIMARY KEY (user_id, condition_id)
);

-- Legătura many-to-many între exerciții și probleme de sănătate
CREATE TABLE exercise_health_condition (
    exercise_id INTEGER REFERENCES exercise(id) ON DELETE CASCADE,
    condition_id INTEGER REFERENCES health_condition(id) ON DELETE CASCADE,
    PRIMARY KEY (exercise_id, condition_id)
);

-- Adăugăm tabelă pentru legătura many-to-many între exerciții și secțiuni (gym, kineto, fizio)
CREATE TABLE exercise_section (
    exercise_id INTEGER REFERENCES exercise(id) ON DELETE CASCADE,
    section VARCHAR(20) NOT NULL,
    PRIMARY KEY (exercise_id, section)
);

CREATE TABLE workout (
   id               SERIAL       PRIMARY KEY,
   name             VARCHAR(100) NOT NULL,
   user_id          INTEGER      NOT NULL REFERENCES users(id) ON DELETE CASCADE,
   duration_minutes INTEGER      NOT NULL CHECK (duration_minutes > 0),
   type_id          INTEGER      REFERENCES training_type(id),
   level_id         INTEGER      REFERENCES training_level(id),
   split_id         INTEGER      REFERENCES split_type(id),
   location_id      INTEGER      REFERENCES location(id),
   section          VARCHAR(10)  NOT NULL,
   created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
   started_at       TIMESTAMP,
   completed_at     TIMESTAMP,
   completed_count  INTEGER      DEFAULT 0
);

CREATE TABLE workout_exercise (
   workout_id        integer REFERENCES workout(id) ON DELETE CASCADE,
   exercise_id       integer REFERENCES exercise(id) ON DELETE CASCADE,
   order_in_workout  integer NOT NULL,
   sets              integer,
   reps              integer,
   PRIMARY KEY (workout_id, exercise_id)
);

CREATE TABLE workout_session (
    id SERIAL PRIMARY KEY,
    workout_id INTEGER NOT NULL REFERENCES workout(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    completed_count INTEGER DEFAULT 0
);

-- NOTĂ PENTRU LOGICĂ:
-- Dacă un utilizator nu are nicio condiție de sănătate => i se vor afișa toate exercițiile (fără filtru pe health_condition).
-- Dacă un utilizator are condiții de sănătate => i se vor afișa DOAR exercițiile asociate explicit condițiilor respective.