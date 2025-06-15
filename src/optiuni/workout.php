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
$section = $_GET['section'] ?? 'gym';

if (!$wid || !is_numeric($wid)) die("Link invalid.");

$stmt = $pdo->prepare("SELECT * FROM workout WHERE id = ? AND section = ? AND user_id = ?");
$stmt->execute([$wid, $section, $uid]);
$workout = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$workout) die("Workout inexistent.");

if (!$sid || !is_numeric($sid)) {
    $stmt = $pdo->prepare("SELECT * FROM get_latest_session(:uid, :wid)");
    $stmt->execute(['uid' => $uid, 'wid' => $wid]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($session) {
        header("Location: workout.php?section=$section&wid=$wid&sid=" . $session['id']);
        exit;
    } else {
        $session = null;
    }
} else {
    $stmt = $pdo->prepare("SELECT * FROM workout_session WHERE id = ? AND user_id = ? AND workout_id = ?");
    $stmt->execute([$sid, $uid, $wid]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $session) {
    if (isset($_POST['complete'])) {
        $stmt = $pdo->prepare("CALL complete_workout_session(:sid, :uid)");
        $stmt->execute(['sid' => $session['id'], 'uid' => $uid]);
        $session['completed_at'] = date('Y-m-d H:i:s');
    } elseif (isset($_POST['cancel'])) {
        $stmt = $pdo->prepare("CALL cancel_workout_session(:sid, :uid)");
        $stmt->execute(['sid' => $session['id'], 'uid' => $uid]);
        header("Location: workouts.php?section=$section");
        exit;
    }
    header("Location: workout.php?section=$section&wid=$wid&sid=" . $session['id']);
    exit;
}

$ex = $pdo->prepare("SELECT * FROM get_exercises_for_workout(:wid)");
$ex->execute(['wid' => $wid]);
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
  <a href="workouts.php?section=<?= $section ?>" class="back-btn">Înapoi</a>
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