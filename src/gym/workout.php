<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$pdo = new PDO(
    "pgsql:host=db;port=5432;dbname=wow_db",
    'root',
    'root',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$workoutId = $_GET['id'] ?? null;
if (!$workoutId || !is_numeric($workoutId)) {
    die("Workout invalid.");
}

$stmt = $pdo->prepare("
    SELECT w.name, w.duration_minutes, uw.generated_at
    FROM workout w
    JOIN user_workout uw ON uw.workout_id = w.id
    WHERE uw.user_id = ? AND w.id = ?
");
$stmt->execute([$_SESSION['user_id'], $workoutId]);
$workout = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$workout) {
    die("Acest antrenament nu există sau nu îți aparține.");
}

$exStmt = $pdo->prepare("
    SELECT e.name, e.description, e.link, we.sets, we.reps, we.order_in_workout
    FROM workout_exercise we
    JOIN exercise e ON e.id = we.exercise_id
    WHERE we.workout_id = ?
    ORDER BY we.order_in_workout ASC
");
$exStmt->execute([$workoutId]);
$exercises = $exStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($workout['name']) ?> | FitFlow</title>
  <link rel="stylesheet" href="/css/styles.css">
  <link rel="stylesheet" href="/css/workout.css">
</head>
<body>
<nav>
    <h1><?= htmlspecialchars($workout['name']) ?></h1>
    <a href="workouts-gym.php" class="back-btn">Înapoi</a>
</nav>

<div class="container">
    <p class="info">Durată: <?= $workout['duration_minutes'] ?> minute</p>
    <p class="info">Generat: <?= date('d.m.Y H:i', strtotime($workout['generated_at'])) ?></p>

    <h2>Exerciții:</h2>

    <?php if ($exercises): ?>
        <?php foreach ($exercises as $ex): ?>
            <div class="exercise">
                <h3><?= htmlspecialchars($ex['name']) ?></h3>
                <p><?= htmlspecialchars($ex['description']) ?></p>
                <p class="info">Seturi: <?= $ex['sets'] ?> &nbsp; | &nbsp; Repetări: <?= $ex['reps'] ?></p>
                <?php if (!empty($ex['link'])): ?>
                    <a href="<?= htmlspecialchars($ex['link']) ?>" target="_blank">🔗 Tutorial video</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Nu sunt exerciții înregistrate pentru acest antrenament.</p>
    <?php endif; ?>
</div>
</body>
</html>
