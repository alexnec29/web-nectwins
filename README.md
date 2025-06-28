# Workout Web Generator - NecTwins

## VIDEO: https://youtu.be/NS9RUDW21MY

Before running the program, you should add on the root folder an .env file that has the fields necesary for connecting to the database:
```
DB_HOST=
DB_PORT=
DB_NAME=
DB_USER=
DB_PASSWORD=
```

To run, use the command
```bash
docker-compose up -d
```

If you are changing the DockerFile, use
```bash
docker-compose up -d --build
```

You can then access the website locally via http://localhost:8080

If you want to stop the containers:
```bash
docker-compose down
```

If you want to also erase databases:
```bash
docker-compose down -v
```
