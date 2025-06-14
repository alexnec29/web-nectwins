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
$allowedSections = ['gym', 'kineto', 'fizio'];
$section = $_GET['section'] ?? 'gym';
if (!in_array($section, $allowedSections)) {
    $section = 'gym';
}

$totalWorkouts = (int) $pdo->query("SELECT get_total_completed_workouts($uid, '$section')")->fetchColumn();
$totalMinutes  = (int) $pdo->query("SELECT get_total_workout_duration($uid, '$section')")->fetchColumn();
$subgroupRows  = $pdo->query("SELECT * FROM get_muscle_subgroup_stats($uid, '$section')")->fetchAll(PDO::FETCH_ASSOC);
$exerciseRows  = $pdo->query("SELECT * FROM get_top_exercises($uid, '$section', 5)")->fetchAll(PDO::FETCH_ASSOC);
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
    <h1>Statistici - <?= ucfirst($section) ?></h1>
    <a class="buton-inapoi" href="principal.php?section=<?= $section ?>">Înapoi</a>
</nav>

<div class="stats-container">
    <h2>Total antrenamente efectuate: <?= $totalWorkouts ?></h2>
    <h2>Durata totală: <?= $totalMinutes ?> minute</h2>

    <h3>🔸 Distribuția pe subgrupe musculare</h3>
    <?php if ($subgroupRows): ?>
        <div class="top-exercises">
            <?php foreach ($subgroupRows as $r): ?>
                <div class="exercise-card">
                    <div class="exercise-name"><?= htmlspecialchars($r['name']) ?></div>
                    <div class="exercise-count"><?= $r['cnt'] ?>x</div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="empty-state">Nicio distribuție disponibilă.</p>
    <?php endif; ?>

    <h3>🔹 Cele mai folosite exerciții</h3>
    <?php if ($exerciseRows): ?>
        <div class="top-exercises">
            <?php foreach ($exerciseRows as $r): ?>
                <div class="exercise-card">
                    <div class="exercise-name"><?= htmlspecialchars($r['name']) ?></div>
                    <div class="exercise-count"><?= $r['uses'] ?>x</div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="empty-state">Fără exerciții înregistrate.</p>
    <?php endif; ?>
</div>

<div class="rss-section">
    <h3>Fluxul tău RSS</h3>
    <a href="rss.php?section=<?= $section ?>" class="rss-link" target="_blank">📥 RSS <?= ucfirst($section) ?></a>
</div>
</body>
</html>