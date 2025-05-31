<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = new PDO(
    "pgsql:host=db;port=5432;dbname=wow_db",
    'root', 'root',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wid = (int)($_POST['wid'] ?? 0);

    if (isset($_POST['start'])) {
        // pornire workout: setează started_at, anulează completed_at
        $pdo->prepare("UPDATE workout SET started_at = NOW(), completed_at = NULL WHERE id = ? AND user_id = ?")
            ->execute([$wid, $uid]);
    } elseif (isset($_POST['complete'])) {
        // finalizare workout: setează completed_at, incrementează counter
        $pdo->prepare("UPDATE workout SET completed_at = NOW(), completed_count = completed_count + 1 WHERE id = ? AND user_id = ? AND started_at IS NOT NULL AND completed_at IS NULL")
            ->execute([$wid, $uid]);
    } elseif (isset($_POST['cancel'])) {
        // anulare workout: şterge started_at
        $pdo->prepare("UPDATE workout SET started_at = NULL WHERE id = ? AND user_id = ?")
            ->execute([$wid, $uid]);
    }
    header("Location: workouts-gym.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM workout WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$uid]);
$workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title>Antrenamentele mele | FitFlow</title>
  <link rel="stylesheet" href="/css/styles.css">
  <link rel="stylesheet" href="/css/workouts.css">
</head>
<body>
<nav>
  <h1>Antrenamentele mele</h1>
  <a class="buton-inapoi" href="principal-gym.php">Înapoi</a>
</nav>

<div class="workouts-list">
  <?php foreach ($workouts as $w): ?>
    <div class="workout-card">
      <h2><?= htmlspecialchars($w['name']) ?></h2>
      <p>Durată: <?= $w['duration_minutes'] ?> min</p>

      <?php if ($w['completed_at']): ?>
        <p style="color:lightgreen">✔️ Completat la <?= date('d.m H:i', strtotime($w['completed_at'])) ?><br>(x<?= $w['completed_count'] ?>)</p>
      <?php elseif ($w['started_at']): ?>
        <form method="POST">
          <input type="hidden" name="wid" value="<?= $w['id'] ?>">
          <button name="complete" class="buton-inapoi">✅ Finalizează</button>
          <button name="cancel"   class="buton-inapoi" style="background:#c44">⏹️ Anulează</button>
        </form>
      <?php else: ?>
        <form method="POST">
          <input type="hidden" name="wid" value="<?= $w['id'] ?>">
          <button name="start" class="buton-inapoi">▶️ Start</button>
        </form>
      <?php endif; ?>

      <a class="buton-inapoi" href="workout.php?wid=<?= $w['id'] ?>">📄 Detalii</a>
    </div>
  <?php endforeach; ?>
</div>
</body>
</html>