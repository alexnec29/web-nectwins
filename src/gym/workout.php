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

$wid = $_GET['wid'] ?? null;
$sid = $_GET['sid'] ?? null;
if (!$wid || !is_numeric($wid)) die("Link invalid.");

// Get workout basic data
$stmt = $pdo->prepare("SELECT * FROM workout WHERE id = ?");
$stmt->execute([$wid]);
$workout = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$workout) die("Workout inexistent.");

// Get latest active session if no sid is passed
if (!$sid || !is_numeric($sid)) {
    $stmt = $pdo->prepare("SELECT * FROM workout_session WHERE user_id = ? AND workout_id = ? AND completed_at IS NULL ORDER BY started_at DESC LIMIT 1");
    $stmt->execute([$uid, $wid]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($session) {
        header("Location: workout.php?wid=$wid&sid=" . $session['id']);
        exit;
    } else {
        $session = null;
    }
} else {
    $stmt = $pdo->prepare("SELECT * FROM workout_session WHERE id = ? AND user_id = ? AND workout_id = ?");
    $stmt->execute([$sid, $uid, $wid]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $session) {
    if (isset($_POST['complete'])) {
        $pdo->prepare("UPDATE workout_session SET completed_at = NOW() WHERE id = ? AND user_id = ?")
            ->execute([$session['id'], $uid]);
        $session['completed_at'] = date('Y-m-d H:i:s');
    } elseif (isset($_POST['cancel'])) {
        $pdo->prepare("DELETE FROM workout_session WHERE id = ? AND user_id = ?")
            ->execute([$session['id'], $uid]);
        header("Location: workouts-gym.php");
        exit;
    }
    header("Location: workout.php?wid=$wid&sid=" . $session['id']);
    exit;
}

// Fetch exercises
$ex = $pdo->prepare("SELECT e.name, e.description, e.link, we.sets, we.reps, we.order_in_workout
                    FROM workout_exercise we
                    JOIN exercise e ON e.id = we.exercise_id
                    WHERE we.workout_id = ?
                    ORDER BY we.order_in_workout");
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

  <?php if ($session): ?>
    <p class="info">⏱️ Început la: <?= date('H:i', strtotime($session['started_at'])) ?></p>
    <?php if ($session['completed_at']): ?>
      <p class="info" style="color:#5bff5b;font-weight:bold;">✔️ Finalizat la: <?= date('H:i', strtotime($session['completed_at'])) ?></p>
    <?php else: ?>
      <form method="POST" style="display:inline-block;margin-right:1rem;">
        <button type="submit" name="complete" class="back-btn">✅ Marchează ca efectuat</button>
      </form>
      <form method="POST" style="display:inline-block;">
        <button type="submit" name="cancel" class="back-btn" style="background:#cc4444;">⏹️ Anulează</button>
      </form>
    <?php endif; ?>
  <?php else: ?>
    <p style="color:orange">⚠️ Nu ai o sesiune activă. Pornește un antrenament din pagina anterioară.</p>
  <?php endif; ?>

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
