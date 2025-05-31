<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$pdo = new PDO(
    "pgsql:host=db;port=5432;dbname=wow_db",
    'root',
    'root',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// GET workout ID directly
$wid = $_GET['wid'] ?? null;
if (!$wid || !is_numeric($wid)) die("Link invalid.");

$stmt = $pdo->prepare("
    SELECT *
    FROM workout
    WHERE id = ? AND user_id = ?
");
$stmt->execute([$wid, $_SESSION['user_id']]);
$workout = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$workout) die("Nu există antrenamentul.");

$msg = null;

// Simple POST rename logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename'])) {
    $new = trim($_POST['newname']);
    if ($new !== '') {
        $pdo->prepare("UPDATE workout SET name = ? WHERE id = ?")
            ->execute([$new, $wid]);
        $msg = "✅ Numele a fost actualizat.";
        $workout['name'] = $new;
    }
}

// Fetch exercises
$ex = $pdo->prepare("
    SELECT e.name, e.description, e.link, we.sets, we.reps, we.order_in_workout
    FROM workout_exercise we
    JOIN exercise e ON e.id = we.exercise_id
    WHERE we.workout_id = ?
    ORDER BY we.order_in_workout
");
$ex->execute([$wid]);
$exercises = $ex->fetchAll(PDO::FETCH_ASSOC);
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
  <p class="info">Durată programată: <?= $workout['duration_minutes'] ?> min</p>

  <!-- Rename workout -->
  <form method="POST" style="margin:1.5rem 0;">
    <input type="text" name="newname" placeholder="Nume nou"
           style="padding:.4rem;border-radius:6px;border:1px solid #666;width:60%;max-width:260px">
    <button type="submit" name="rename" class="back-btn">✏️ Redenumește</button>
  </form>

  <?php if ($msg) echo "<p style='color:#5bf'>$msg</p>"; ?>

  <h2>Exerciții:</h2>
  <?php if ($exercises): foreach ($exercises as $e): ?>
    <div class="exercise">
      <h3><?= htmlspecialchars($e['name']) ?></h3>
      <p><?= htmlspecialchars($e['description'] ?? '-') ?></p>
      <p class="info">Seturi: <?= $e['sets'] ?> &nbsp; | &nbsp; Rep: <?= $e['reps'] ?></p>
      <?php if ($e['link']): ?>
        <a href="<?= htmlspecialchars($e['link']) ?>" target="_blank">🔗 Tutorial</a>
      <?php endif; ?>
    </div>
  <?php endforeach; else: ?>
    <p>Nu sunt exerciții înregistrate.</p>
  <?php endif; ?>
</div>
</body>
</html>
