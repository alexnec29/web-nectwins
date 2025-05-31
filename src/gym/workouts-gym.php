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

/* ===== buton „Start” – redirecţionează către workout.php ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'start'
    && isset($_POST['wid'])
) {
    header('Location: workout.php?wid=' . (int)$_POST['wid']);
    exit;
}

/* ===== toate workout-urile acestui user ===== */
$stmt = $pdo->prepare("
    SELECT id   AS wid,
           name,
           duration_minutes
    FROM   workout
    WHERE  user_id = ?
    ORDER  BY id DESC
");
$stmt->execute([ $_SESSION['user_id'] ]);
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

<?php if ($workouts): ?>
  <div class="workouts-list">
    <?php foreach ($workouts as $w): ?>
      <div class="workout-card">
        <h2><?= htmlspecialchars($w['name']) ?></h2>
        <p>Durată: <?= (int)$w['duration_minutes'] ?> min</p>

        <!-- buton START -->
        <form method="POST" style="margin-bottom:0.6rem">
          <input type="hidden" name="wid"    value="<?= $w['wid'] ?>">
          <input type="hidden" name="action" value="start">
          <button class="buton-inapoi" style="width:100%">▶️ Start</button>
        </form>

        <a class="buton-inapoi" href="workout.php?wid=<?= $w['wid'] ?>">
          Vezi detalii
        </a>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <p style="text-align:center;margin-top:2rem;color:#cdd6f4">
      Nu ai niciun antrenament salvat încă.
  </p>
<?php endif; ?>
</body>
</html>