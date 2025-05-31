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

CREATE TABLE location (
   id   serial       PRIMARY KEY,
   name varchar(50)  NOT NULL UNIQUE
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

CREATE TABLE exercise_muscle_group (
   exercise_id        integer REFERENCES exercise(id)         ON DELETE CASCADE,
   muscle_subgroup_id integer REFERENCES muscle_subgroup(id) ON DELETE CASCADE,
   PRIMARY KEY (exercise_id, muscle_subgroup_id)
);

CREATE TABLE workout (
   id                serial       PRIMARY KEY,
   name              varchar(100) NOT NULL,
    user_id           integer      NOT NULL
     REFERENCES users(id) ON DELETE CASCADE,
   duration_minutes  integer      NOT NULL CHECK (duration_minutes > 0),
   type_id           integer      REFERENCES training_type(id),
   level_id          integer      REFERENCES training_level(id),
   split_id          integer      REFERENCES split_type(id),
   location_id       integer      REFERENCES location(id),
   created_at        timestamp    DEFAULT CURRENT_TIMESTAMP,
   started_at        timestamp,
   completed_at      timestamp,
   completed_count   integer      DEFAULT 0
);

CREATE TABLE workout_exercise (
   workout_id        integer REFERENCES workout(id) ON DELETE CASCADE,
   exercise_id       integer REFERENCES exercise(id) ON DELETE CASCADE,
   order_in_workout  integer NOT NULL,
   sets              integer,
   reps              integer,
   PRIMARY KEY (workout_id, exercise_id)
);