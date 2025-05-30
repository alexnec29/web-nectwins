<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$pdo = new PDO(
    "pgsql:host=db;port=5432;dbname=wow_db",'root','root',
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
);

if ($_SERVER['REQUEST_METHOD']==='POST' && $_POST['action']==='start' && isset($_POST['wid'])) {
    $wid = (int)$_POST['wid'];

    $active = $pdo->prepare("
        SELECT id FROM user_workout
         WHERE user_id=? AND workout_id=? AND completed=FALSE
           AND started_at IS NOT NULL
         LIMIT 1");
    $active->execute([$_SESSION['user_id'],$wid]);
    $actId = $active->fetchColumn();

    if ($actId) {
        header("Location: workout.php?uwid=$actId");
        exit;
    }

    $new = $pdo->prepare("
        INSERT INTO user_workout (user_id, workout_id, started_at)
        VALUES (?, ?, NOW())
        RETURNING id");
    $new->execute([$_SESSION['user_id'],$wid]);
    $uwid = $new->fetchColumn();

    header("Location: workout.php?uwid=$uwid");
    exit;
}

$sql = "
 SELECT DISTINCT ON (w.id)
        uw.id              AS uwid,
        w.id               AS wid,
        w.name,
        w.duration_minutes,
        uw.generated_at,
        uw.started_at,
        uw.completed,
        ( SELECT COUNT(*) FROM user_workout
          WHERE user_id = uw.user_id
            AND workout_id = uw.workout_id
            AND completed = TRUE ) AS times_done
 FROM user_workout uw
 JOIN workout w ON w.id = uw.workout_id
 WHERE uw.user_id = ?
 ORDER BY w.id, uw.generated_at DESC
";
$stmt=$pdo->prepare($sql); $stmt->execute([$_SESSION['user_id']]);
$workouts=$stmt->fetchAll(PDO::FETCH_ASSOC);
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
      <p>Durată: <?= $w['duration_minutes'] ?> min</p>
      <p>Generat: <?= date('d.m.Y H:i', strtotime($w['generated_at'])) ?></p>
      <p><strong>Completări: <?= $w['times_done'] ?></strong></p>

      <?php if ($w['completed']): ?>
          <p style="color:#5bff5b;font-weight:bold;">✔️ Ultima sesiune completată</p>
      <?php elseif ($w['started_at']): ?>
          <p style="color:#ffd95b;">⏳ În desfășurare</p>
      <?php endif; ?>

      <form method="POST" style="margin-bottom:.6rem">
          <input type="hidden" name="wid"    value="<?= $w['wid'] ?>">
          <input type="hidden" name="action" value="start">
          <button class="buton-inapoi" style="width:100%">▶️ Start</button>
      </form>

      <a href="workout.php?uwid=<?= $w['uwid'] ?>" class="buton-inapoi">Vezi detalii</a>
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