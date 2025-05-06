<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Login | FitFlow</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/principal.css">
</head>

<body>
    <nav>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
        <a href="logout.php">Logout</a>
    </nav>

    <div class="grid">

        <div class="card">
            <button class="card-button" onclick="window.location.href='generate.php'">
                <p>Generare Antrenament</p>
                <img src="/assets/generare.png" alt="Generate Workout">
            </button>
        </div>

        <div class="card">
            <button class="card-button" onclick="window.location.href='workouts.php'">
                <p>Antrenamentele mele</p>
                <img src="/assets/workouts.png" alt="Generate Workout">
            </button>
        </div>

        <div class="card">
            <button class="card-button" onclick="window.location.href='statistics.php'">
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