<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: ./../login.php");
    exit();
}

$allowed_sections = ["gym", "kineto", "fizio"];
$section = (isset($_GET['section']) && in_array($_GET['section'], $allowed_sections)) ? $_GET['section'] : "gym";

// Linkuri către paginile respective
$generate_url = "/{$section}/generate-{$section}.php";
$workouts_url = "/{$section}/workouts-{$section}.php";
$statistics_url = "statistics.php?section={$section}";
$leaderboard_url = "leaderboard.php?section={$section}";
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Principal | FitFlow</title>
    <title>Principal | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/principal.css?v=<?php echo time(); ?>">
</head>

<body>
    <nav>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>

        <button type="button" class="gym<?php echo $section === 'gym' ? ' buton-apasat' : ''; ?>" onclick="window.location.href='?section=gym'">
            <img src="/assets/gym.png" alt="gym">
            <span class="nav-tooltip">Bodybuilding</span>
        </button>
        <button type="button" class="kineto<?php echo $section === 'kineto' ? ' buton-apasat' : ''; ?>" onclick="window.location.href='?section=kineto'">
            <img src="/assets/kineto.png" alt="kineto">
            <span class="nav-tooltip">Kinetoterapie</span>
        </button>
        <button type="button" class="fizioterapie<?php echo $section === 'fizio' ? ' buton-apasat' : ''; ?>" onclick="window.location.href='?section=fizio'">
            <img src="/assets/fizio.png" alt="fizioterapie">
            <span class="nav-tooltip">Fizioterapie</span>
        </button>

        <button type="button" class="dropbtn" onclick="window.location.href='add-exercise.php'">
            <img src="/assets/plus.png" alt="Adaugă exercițiu" style="height: 24px; vertical-align: middle;">
            <span class="nav-tooltip">Adaugă Exercițiu</span>
        </button>

        <div class="nav-user">
            <div class="dropdown">
                <button class="dropbtn">
                    <img src="/assets/user.png" alt="Profil" style="height: 24px; vertical-align: middle;">
                </button>
                <div class="dropdown-content">
                    <a href="/profil.php">Vezi/Editează profil</a>
                    <a href="/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="grid">
        <div class="card">
            <button class="card-button" onclick="window.location.href='<?php echo $generate_url; ?>'">
                <p>Generare Antrenament</p>
                <img src="/assets/generare-<?php echo $section; ?>.png" alt="Generate Workout">
            </button>
        </div>

        <div class="card">
            <button class="card-button" onclick="window.location.href='<?php echo $workouts_url; ?>'">
                <p>Antrenamentele mele</p>
                <img src="/assets/workouts-<?php echo $section; ?>.png" alt="Workouts">
            </button>
        </div>

        <div class="card">
            <button class="card-button" onclick="window.location.href='<?php echo $statistics_url; ?>'">
                <p>Statistici</p>
                <img src="/assets/statistics-<?php echo $section; ?>.png" alt="Statistics">
            </button>
        </div>

        <div class="card">
            <button class="card-button" onclick="window.location.href='<?php echo $leaderboard_url; ?>'">
                <p>Clasament</p>
                <img src="/assets/leaderboard-<?php echo $section; ?>.png" alt="Leaderboard">
            </button>
        </div>
    </div>

    <footer>
        <p>Contactează-ne: <a href="mailto:support@workoutgen.app">support@workoutgen.app</a> | Telefon: +40 735 123 456</p>
        <p>&copy; <?php echo date('Y'); ?> Workout Generator. Toate drepturile rezervate.</p>
    </footer>
</body>

</html>