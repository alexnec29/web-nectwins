<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$pdo = new PDO("pgsql:host=db;port=5432;dbname=wow_db",'root','root',
               [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='start') {
    $uwid = intval($_POST['uwid']);
    // dacă nu e deja pornit
    $pdo->prepare("UPDATE user_workout SET started_at = NOW()
                   WHERE id = ? AND user_id = ? AND started_at IS NULL")
        ->execute([$uwid, $_SESSION['user_id']]);
    header("Location: workout.php?uwid=$uwid");
    exit;
}

$sql = "
  SELECT DISTINCT ON (w.id)
         uw.id              AS uwid,          -- cel mai nou rând pt acel workout
         w.id               AS wid,
         w.name,
         w.duration_minutes,
         uw.generated_at,
         (SELECT COUNT(*) FROM user_workout
          WHERE user_id = uw.user_id
            AND workout_id = uw.workout_id
            AND completed = TRUE) AS times_done
  FROM user_workout uw
  JOIN workout w ON w.id = uw.workout_id
  WHERE uw.user_id = ?
  ORDER BY w.id, uw.generated_at DESC
";
$stmt=$pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
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

<?php if($workouts):?>
  <div class="workouts-list">
    <?php foreach($workouts as $w):?>
      <div class="workout-card">
        <h2><?=htmlspecialchars($w['name'])?></h2>
        <p>Durată: <?=$w['duration_minutes']?> min</p>
        <p>Generat: <?=date('d.m.Y H:i',strtotime($w['generated_at']))?></p>
        <p><strong>Completări: <?=$w['times_done']?></strong></p>

        <form method="POST" style="margin-bottom:.6rem">
          <input type="hidden" name="uwid"   value="<?=$w['uwid']?>">
          <input type="hidden" name="action" value="start">
          <button class="buton-inapoi" style="width:100%">▶️ Start</button>
        </form>

        <a href="workout.php?uwid=<?=$w['uwid']?>" class="buton-inapoi">Vezi detalii</a>
      </div>
    <?php endforeach;?>
  </div>
<?php else:?>
  <p style="text-align:center;margin-top:2rem;color:#cdd6f4">
      Nu ai niciun antrenament salvat încă.
  </p>
<?php endif;?>
</body>
</html>