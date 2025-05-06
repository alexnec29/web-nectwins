# Workout Web Generator - NecTwins
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
