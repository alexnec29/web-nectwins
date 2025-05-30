drop table if exists users;

create table users (
   id       serial primary key,
   username varchar(50) not null unique,
   password varchar(255) not null,
   email    varchar(100) not null unique,
   nume     varchar(100),
   varsta   int,
   gen      varchar(20),
   inaltime int,
   greutate int,
   conditie text
);