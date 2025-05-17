<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: ./../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Login | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/principal.css">
</head>

<body>
    <nav>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
        <form method="post">
            <input type="hidden" name="active" value="gym">
            <button type="button" class="gym" onclick="window.location.href='/gym/principal-gym.php'">
                <img src="/assets/gym.png" alt="gym">
                <span class="nav-tooltip">Bodybuilding</span>
            </button>
        </form>
        <form method="post">
            <input type="hidden" name="active" value="kineto">
            <button type="button" class="kineto buton-apasat">
                <img src="/assets/kineto.png" alt="kineto">
                <span class="nav-tooltip">Kinetoterapie</span>
            </button>
        </form>
        <form method="post">
            <input type="hidden" name="active" value="fizioterapie">
            <button type="button" class="fizioterapie" onclick="window.location.href='/fizio/principal-fizio.php'">
                <img src="/assets/fizio.png" alt="fizioterapie">
                <span class="nav-tooltip">Fizioterapie</span>
            </button>
        </form>
        <a href="./../logout.php">Logout</a>
    </nav>

    <div class="grid">

        <div class="card">
            <button class="card-button" onclick="window.location.href='generate-gym.php'">
                <p>Generare Antrenament</p>
                <img src="/assets/generare.png" alt="Generate Workout">
            </button>
        </div>

        <div class="card">
            <button class="card-button" onclick="window.location.href='workouts-gym.php'">
                <p>Antrenamentele mele</p>
                <img src="/assets/workouts.png" alt="Generate Workout">
            </button>
        </div>

        <div class="card">
            <button class="card-button" onclick="window.location.href='statistics-gym.php'">
                <p>Statistici</p>
                <img src="/assets/statistics.png" alt="Generate Workout">
            </button>
        </div>

    </div>
    <footer>
        <p>Contactează-ne: <a href="mailto:support@workoutgen.app">support@workoutgen.app</a> | Telefon: +40 735 123 456</p>
        <p>&copy; <?php echo date('Y'); ?> Workout Generator. Toate drepturile rezervate.</p>
    </footer>
</body>

</html>