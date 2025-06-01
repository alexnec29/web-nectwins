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
$section = 'kineto';

$totalWorkouts = (int) $pdo->query("SELECT get_total_completed_workouts($uid, '$section')")->fetchColumn();
$totalMinutes  = (int) $pdo->query("SELECT get_total_workout_duration($uid, '$section')")->fetchColumn();
$subgroupRows  = $pdo->query("SELECT * FROM get_muscle_subgroup_stats($uid)")->fetchAll(PDO::FETCH_ASSOC);
$exerciseRows = $pdo->query("SELECT * FROM get_top_exercises($uid, 'kineto', 5)")->fetchAll(PDO::FETCH_ASSOC);
$typeRows      = $pdo->query("SELECT * FROM get_training_type_stats($uid)")->fetchAll(PDO::FETCH_ASSOC);
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
        <a class="buton-inapoi" href="principal-kineto.php">Înapoi</a>
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
        <a href="rss-kineto.php" class="rss-link" target="_blank">📥 Vezi fluxul RSS</a>
    </div>
</body>

</html>