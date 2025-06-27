<?php
session_start();
$role = $_SESSION['role'] ?? 1;

if (!isset($_SESSION["username"])) {
    header("Location: ./../login.php");
    exit();
}

$allowed_sections = ["gym", "kineto", "fizio"];
$section = (isset($_GET['section']) && in_array($_GET['section'], $allowed_sections)) ? $_GET['section'] : "gym";

$generate_url = "./optiuni/generate.php?section={$section}";
$workouts_url = "./optiuni/workouts.php?section={$section}";
$statistics_url = "./optiuni/statistics.php?section={$section}";
$leaderboard_url = "./optiuni/leaderboard.php?section={$section}";
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Principal | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/principal.css">
</head>

<body>
    <nav>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>

        <a href="?section=gym" class="gym<?php echo $section === 'gym' ? ' buton-apasat' : ''; ?>" role="button">
            <img src="/assets/gym.png" alt="gym">
            <span class="nav-tooltip">Bodybuilding</span>
        </a>
        <a href="?section=kineto" class="kineto<?php echo $section === 'kineto' ? ' buton-apasat' : ''; ?>" role="button">
            <img src="/assets/kineto.png" alt="kineto">
            <span class="nav-tooltip">Kinetoterapie</span>
        </a>
        <a href="?section=fizio" class="fizioterapie<?php echo $section === 'fizio' ? ' buton-apasat' : ''; ?>" role="button">
            <img src="/assets/fizio.png" alt="fizioterapie">
            <span class="nav-tooltip">Fizioterapie</span>
        </a>

        <?php if ($role == 3): ?>
            <a href="./admin/superadmin.php" class="admin-button">👑 Superadmin</a>
        <?php endif; ?>

        <?php if ($role >= 2): ?>
            <a href="./admin/admin.php" class="admin-button">🛠️ Admin</a>
        <?php endif; ?>

        <div class="nav-user">
            <div class="dropdown">
                <button class="dropbtn" type="button">
                    <img src="/assets/user.png" alt="Profil" style="height: 24px; vertical-align: middle;">
                </button>
                <div class="dropdown-content">
                    <a href="profil.php">Vezi/Editează profil</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="grid">
        <div class="card">
            <a href="<?php echo htmlspecialchars($generate_url); ?>" class="card-button" role="button">
                <p>Generare Antrenament</p>
                <img src="/assets/generare-<?php echo $section; ?>.png" alt="Generate Workout">
            </a>
        </div>

        <div class="card">
            <a href="<?php echo htmlspecialchars($workouts_url); ?>" class="card-button" role="button">
                <p>Antrenamentele mele</p>
                <img src="/assets/workouts-<?php echo $section; ?>.png" alt="Workouts">
            </a>
        </div>

        <div class="card">
            <a href="<?php echo htmlspecialchars($statistics_url); ?>" class="card-button" role="button">
                <p>Statistici</p>
                <img src="/assets/statistics-<?php echo $section; ?>.png" alt="Statistics">
            </a>
        </div>

        <div class="card">
            <a href="<?php echo htmlspecialchars($leaderboard_url); ?>" class="card-button" role="button">
                <p>Clasament</p>
                <img src="/assets/clasament-<?php echo $section; ?>.png" alt="Leaderboard">
            </a>
        </div>
    </div>

    <footer>
        <p>Contactează-ne: <a href="https://www.youtube.com/watch?v=40ybhROL9xM&t=200s">Nec@Twins.app</a> | Telefon: +40 735 123 456</p>
        <p>&copy; <?php echo date('Y'); ?> NecTwins Feral Workout Generator. Toate drepturile rezervate.</p>
    </footer>
</body>

</html>