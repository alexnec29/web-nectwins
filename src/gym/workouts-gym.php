<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = new PDO(
    "pgsql:host=db;port=5432;dbname=wow_db",
    'root',
    'root',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$sql = "
    SELECT w.id,
           w.name,
           w.duration_minutes,
           uw.generated_at
    FROM user_workout uw
    JOIN workout w ON w.id = uw.workout_id
    WHERE uw.user_id = ?
    ORDER BY uw.generated_at DESC
";
$workouts = $pdo->prepare($sql);
$workouts->execute([$_SESSION['user_id']]);
$workouts = $workouts->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Antrenamentele Mele | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/workouts.css">
</head>
<body>
<nav>
    <h1>Antrenamentele mele</h1>
    <a class="buton-inapoi" href="principal-gym.php">Înapoi</a>
</nav>

<?php if ($workouts): ?>
    <div class="workouts-list">
        <?php foreach ($workouts as $w): ?>
            <div class="workout-card">
                <h2><?= htmlspecialchars($w['name']) ?></h2>
                <p>Durată: <?= intval($w['duration_minutes']) ?> min</p>
                <p>Generat: <?= date('d.m.Y H:i', strtotime($w['generated_at'])) ?></p>
                <a href="workout-gym.php?id=<?= $w['id'] ?>" class="buton-inapoi">Vezi detalii</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p style="text-align:center;color:#cdd6f4;margin-top:2rem;">
        Nu ai niciun antrenament salvat încă.
    </p>
<?php endif; ?>
</body>
</html>