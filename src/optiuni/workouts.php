<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

require './../db.php';

$uid = $_SESSION['user_id'];
$section = $_GET['section'] ?? 'gym';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wid'])) {
  $wid = (int)$_POST['wid'];

  if (isset($_POST['start'])) {
    $stmt = $pdo->prepare("CALL start_workout_session(:wid, :uid, :sid)");
    $stmt->bindParam(':wid', $wid, PDO::PARAM_INT);
    $stmt->bindParam(':uid', $uid, PDO::PARAM_INT);
    $stmt->bindParam(':sid', $sid, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT);
    $sid = 0;
    $stmt->execute();

    header("Location: workout.php?section=$section&wid=$wid&sid=$sid");
    exit;
  }
}

$workouts = $pdo->prepare("
    SELECT w.id, w.name, w.duration_minutes,
           s.id AS session_id, s.started_at, s.completed_at
    FROM workout w
    LEFT JOIN LATERAL (
        SELECT *
        FROM workout_session s
        WHERE s.workout_id = w.id AND s.user_id = ? AND s.completed_at IS NULL
        ORDER BY s.started_at DESC
        LIMIT 1
    ) s ON true
    WHERE w.user_id = ? AND w.section = ?
    ORDER BY w.id DESC
");
$workouts->execute([$uid, $uid, $section]);
$rows = $workouts->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ro">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Antrenamentele mele | FitFlow</title>
  <link rel="stylesheet" href="/css/styles.css">
  <link rel="stylesheet" href="/css/workouts.css">
</head>

<body>
  <nav>
    <h1>Antrenamentele mele</h1>
    <a class="buton-inapoi" href="../principal.php?section=<?= $section ?>">Înapoi</a>
  </nav>

  <div class="workouts-list">
    <?php foreach ($rows as $w): ?>
      <div class="workout-card">
        <h2><?= htmlspecialchars($w['name']) ?></h2>
        <p>Durată: <?= (int)$w['duration_minutes'] ?> min</p>

        <?php if ($w['started_at']): ?>
          <p class="in-progress-badge">🕒 În curs de desfășurare...</p>
        <?php else: ?>
          <form method="POST">
            <input type="hidden" name="wid" value="<?= $w['id'] ?>">
            <button name="start" class="buton-inapoi">▶️ Start</button>
          </form>
        <?php endif; ?>

        <a class="buton-inapoi" href="workout.php?section=<?= $section ?>&wid=<?= $w['id'] ?>">📄 Detalii</a>
      </div>
    <?php endforeach; ?>
  </div>
</body>

</html>