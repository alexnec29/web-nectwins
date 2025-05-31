<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$pdo = new PDO("pgsql:host=db;port=5432;dbname=wow_db", 'root', 'root', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$uid = $_SESSION['user_id'];

// Total workout completions
$totalWorkouts = $pdo->prepare("SELECT COUNT(*) FROM workout_session WHERE user_id = ? AND completed_at IS NOT NULL");
$totalWorkouts->execute([$uid]);
$totalWorkouts = (int)$totalWorkouts->fetchColumn();

// Total workout duration
$totalMinutes = $pdo->prepare("SELECT COALESCE(SUM(EXTRACT(EPOCH FROM completed_at - started_at) / 60), 0) FROM workout_session WHERE user_id = ? AND completed_at IS NOT NULL");
$totalMinutes->execute([$uid]);
$totalMinutes = (int)round($totalMinutes->fetchColumn());

// Muscle subgroup distribution
$subgroupDist = $pdo->prepare("
    SELECT msg.name, COUNT(DISTINCT ws.id) AS cnt
    FROM workout_session ws
    JOIN workout_exercise we ON we.workout_id = ws.workout_id
    JOIN exercise_muscle_group emg ON emg.exercise_id = we.exercise_id
    JOIN muscle_subgroup msg ON msg.id = emg.muscle_subgroup_id
    WHERE ws.user_id = ? AND ws.completed_at IS NOT NULL
    GROUP BY msg.name
    ORDER BY cnt DESC
");
$subgroupDist->execute([$uid]);
$subgroupRows = $subgroupDist->fetchAll(PDO::FETCH_ASSOC);

// Most used exercises
$exerciseDist = $pdo->prepare("
    SELECT e.name, COUNT(*) AS uses
    FROM workout_session ws
    JOIN workout_exercise we ON we.workout_id = ws.workout_id
    JOIN exercise e ON e.id = we.exercise_id
    WHERE ws.user_id = ? AND ws.completed_at IS NOT NULL
    GROUP BY e.name
    ORDER BY uses DESC
    LIMIT 5
");
$exerciseDist->execute([$uid]);
$exerciseRows = $exerciseDist->fetchAll(PDO::FETCH_ASSOC);

// Training type distribution
$typeDist = $pdo->prepare("
    SELECT tt.name, COUNT(DISTINCT ws.id) AS cnt
    FROM workout_session ws
    JOIN workout w ON ws.workout_id = w.id
    JOIN training_type tt ON tt.id = w.type_id
    WHERE ws.user_id = ? AND ws.completed_at IS NOT NULL
    GROUP BY tt.name
    ORDER BY cnt DESC
");
$typeDist->execute([$uid]);
$typeRows = $typeDist->fetchAll(PDO::FETCH_ASSOC);
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

    <h3>🔸 Distribuția pe subgrupe musculare</h3>
    <?php if ($subgroupRows): ?>
        <ul>
            <?php foreach ($subgroupRows as $r): ?>
                <li><?= htmlspecialchars($r['name']) ?>: <?= $r['cnt'] ?> sesiuni</li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Nicio distribuție disponibilă (niciun antrenament finalizat).</p>
    <?php endif; ?>

    <h3>🔹 Cele mai folosite exerciții</h3>
    <?php if ($exerciseRows): ?>
        <ol>
            <?php foreach ($exerciseRows as $r): ?>
                <li><?= htmlspecialchars($r['name']) ?> — <?= $r['uses'] ?> apariții</li>
            <?php endforeach; ?>
        </ol>
    <?php else: ?>
        <p>Fără exerciții înregistrate în antrenamente finalizate.</p>
    <?php endif; ?>

    <h3>🔸 Distribuție pe tipuri de antrenament</h3>
    <?php if ($typeRows): ?>
        <ul>
            <?php foreach ($typeRows as $r): ?>
                <li><?= htmlspecialchars($r['name']) ?>: <?= $r['cnt'] ?> sesiuni</li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Nu există date pentru tipuri de antrenamente.</p>
    <?php endif; ?>
</div>

<div class="rss-section">
    <h3>Fluxul tău RSS personal</h3>
    <p>Pentru a primi actualizări automate cu progresul tău, poți folosi acest link RSS în orice cititor de feed-uri:</p>
    <a href="rss-gym.php" class="rss-link" target="_blank">📥 Vezi fluxul RSS</a>
</div>
</body>
</html>
