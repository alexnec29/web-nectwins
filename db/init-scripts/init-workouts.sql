drop table if exists workout_exercise

cascade;
drop table if exists exercise_muscle_group

cascade;
drop table if exists workout

cascade;
drop table if exists exercise

cascade;
drop table if exists muscle_group

cascade;
drop table if exists training_type

cascade;
drop table if exists training_level

cascade;
drop table if exists split_type

cascade;
drop table if exists location

cascade;


create table muscle_group (
   id   serial primary key,
   name varchar(100) not null unique
);

create table exercise (
   id               serial primary key,
   name             varchar(100) not null,
   description      text,
   is_bodyweight    boolean,
   equipment_needed boolean,
   link             varchar(100) -- daca facem yt
);

create table exercise_muscle_group (
   exercise_id     integer
      references exercise ( id )
         on delete cascade,
   muscle_group_id integer
      references muscle_group ( id )
         on delete cascade,
   primary key ( exercise_id,
                 muscle_group_id )
);

create table training_type (
   id   serial primary key,
   name varchar(50) not null unique
);

create table training_level (
   id   serial primary key,
   name varchar(50) not null unique
);

create table split_type (
   id   serial primary key,
   name varchar(50) not null unique
);

create table location (
   id   serial primary key,
   name varchar(50) not null unique
);

create table workout (
   id               serial primary key,
   name             varchar(100) not null,
   duration_minutes integer not null check ( duration_minutes > 0 ),
   type_id          integer
      references training_type ( id ),
   level_id         integer
      references training_level ( id ),
   split_id         integer
      references split_type ( id ),
   location_id      integer
      references location ( id )
);

create table workout_exercise (
   workout_id       integer
      references workout ( id )
         on delete cascade,
   exercise_id      integer
      references exercise ( id )
         on delete cascade,
   order_in_workout integer not null,
   sets             integer,
   reps             integer,
   primary key ( workout_id,
                 exercise_id )
);

CREATE TABLE IF NOT EXISTS user_workout (
    id          serial PRIMARY KEY,
    user_id     integer REFERENCES users(id) ON DELETE CASCADE,
    workout_id  integer REFERENCES workout(id) ON DELETE CASCADE,
    generated_at timestamp DEFAULT NOW()
);