<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$pdo = new PDO(
    "pgsql:host=db;port=5432;dbname=wow_db",
    'root',
    'root',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$uid = $_SESSION['user_id'];

$totalWorkouts = $pdo->prepare("
    SELECT COUNT(*) FROM user_workout
    WHERE user_id = ? AND completed = TRUE
");
$totalWorkouts->execute([$uid]);
$totalWorkouts = (int)$totalWorkouts->fetchColumn();

$totalMinutes = $pdo->prepare("
    SELECT COALESCE(SUM(
        EXTRACT(EPOCH FROM completed_at - started_at)
    )/60, 0)
    FROM user_workout
    WHERE user_id = ? AND completed = TRUE
");
$totalMinutes->execute([$uid]);
$totalMinutes = (int)round($totalMinutes->fetchColumn());

$muscleDist = $pdo->prepare("
    SELECT mg.name, COUNT(DISTINCT uw.id) AS cnt
    FROM user_workout uw
    JOIN workout_exercise we   ON we.workout_id = uw.workout_id
    JOIN exercise_muscle_group emg ON emg.exercise_id = we.exercise_id
    JOIN muscle_group mg      ON mg.id = emg.muscle_group_id
    WHERE uw.user_id = ? AND uw.completed = TRUE
    GROUP BY mg.name
    ORDER BY cnt DESC
");
$muscleDist->execute([$uid]);
$distRows = $muscleDist->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Statistici | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/statistics.css">
</head>

<body>
<nav>
    <h1>Statistici</h1>
    <a class="buton-inapoi" href="principal-gym.php">Înapoi</a>
</nav>

<div class="stats-container">
    <h2>Total antrenamente efectuate: <?= $totalWorkouts ?></h2>
    <h2>Durata totală: <?= $totalMinutes ?> minute</h2>

    <h3>Distribuția pe grupe de mușchi</h3>
    <?php if ($distRows): ?>
        <ul>
            <?php foreach ($distRows as $r): ?>
                <li><?= htmlspecialchars($r['name']) ?>: <?= $r['cnt'] ?> antrenamente</li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Nu există date pentru distribuție (niciun antrenament finalizat).</p>
    <?php endif; ?>
</div>

<div class="rss-section">
    <h3>Fluxul tău RSS personal</h3>
    <p>Pentru a primi actualizări automate cu progresul tău, poți folosi acest link RSS în orice cititor de feed-uri:</p>
    <a href="rss-gym.php" class="rss-link" target="_blank">📥 Vezi fluxul RSS</a>
</div>
</body>
</html>