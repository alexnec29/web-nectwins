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

// ----- Handle POST actions -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // START workout
    if (isset($_POST['start'])) {
        $pdo->prepare("
            UPDATE workout
            SET started_at = NOW(), completed_at = NULL
            WHERE id = ?
        ")->execute([$wid]);
        $workout['started_at'] = date('Y-m-d H:i:s');
        $workout['completed_at'] = null;
    }

    // CANCEL workout
    if (isset($_POST['cancel']) && $workout['started_at'] && !$workout['completed_at']) {
        $pdo->prepare("UPDATE workout SET started_at = NULL WHERE id = ?")
            ->execute([$wid]);
        $workout['started_at'] = null;
    }

    // COMPLETE workout
    if (isset($_POST['complete']) && $workout['started_at'] && !$workout['completed_at']) {
        $pdo->prepare("
            UPDATE workout
            SET completed_at = NOW(), completed_count = completed_count + 1
            WHERE id = ?
        ")->execute([$wid]);
        $workout['completed_at'] = date('Y-m-d H:i:s');
        $workout['completed_count']++;
    }

    // RENAME workout
    if (isset($_POST['rename']) && !$workout['started_at']) {
        $new = trim($_POST['newname']);
        if ($new !== '') {
            $pdo->prepare("UPDATE workout SET name = ? WHERE id = ?")
                ->execute([$new, $wid]);
            $msg = "✅ Numele a fost actualizat.";
            $workout['name'] = $new;
        }
    }

    header("Location: workout.php?wid=$wid");
    exit;
}

// ----- Fetch exercises -----
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

  <?php if ($workout['started_at']): ?>
    <p class="info">⏱️ Început la: <?= date('H:i', strtotime($workout['started_at'])) ?></p>
  <?php endif; ?>

  <?php if ($workout['completed_at']): ?>
    <p class="info" style="color:#5bff5b;font-weight:bold;">✔️ Finalizat la: <?= date('H:i', strtotime($workout['completed_at'])) ?></p>
    <p class="info">🔁 Completări totale: <?= $workout['completed_count'] ?></p>
  <?php endif; ?>

  <?php if (!$workout['started_at']): ?>
    <form method="POST" style="margin:1rem 0;">
      <button type="submit" name="start" class="back-btn">▶️ Pornește antrenamentul</button>
    </form>
  <?php endif; ?>

  <?php if ($workout['started_at'] && !$workout['completed_at']): ?>
    <form method="POST" style="display:inline-block;margin-right:1rem;">
      <button type="submit" name="complete" class="back-btn">✅ Marchează ca efectuat</button>
    </form>
    <form method="POST" style="display:inline-block;">
      <button type="submit" name="cancel" class="back-btn" style="background:#cc4444;">⏹️ Anulează</button>
    </form>
  <?php endif; ?>

  <?php if (!$workout['started_at'] && !$workout['completed_at']): ?>
    <form method="POST" style="margin:1.5rem 0;">
      <input type="text" name="newname" placeholder="Nume nou"
             style="padding:.4rem;border-radius:6px;border:1px solid #666;width:60%;max-width:260px">
      <button type="submit" name="rename" class="back-btn">✏️ Redenumește</button>
    </form>
  <?php endif; ?>

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