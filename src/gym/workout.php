<?php
session_start();
if(!isset($_SESSION['user_id'])){header("Location:login.php");exit;}

$pdo=new PDO("pgsql:host=db;port=5432;dbname=wow_db",'root','root',
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

$uwid = $_GET['uwid'] ?? null;
if(!$uwid || !is_numeric($uwid)) die("Link invalid.");

# ───── ia datele user_workout + workout
$q=$pdo->prepare("
  SELECT uw.*, w.*
  FROM user_workout uw
  JOIN workout w ON w.id = uw.workout_id
  WHERE uw.id = ? AND uw.user_id = ?
");
$q->execute([$uwid,$_SESSION['user_id']]);
$row=$q->fetch(PDO::FETCH_ASSOC);
if(!$row) die("Nu există.");

# ───── procesează acțiuni POST
if($_SERVER['REQUEST_METHOD']==='POST'){
   /* redenumire */
   if (isset($_POST['rename']) && !$row['started_at'] && !$row['completed']) {
        $new = trim($_POST['newname']);
        if ($new !== '') {
            $pdo->prepare("UPDATE workout SET name=? WHERE id=?")
                ->execute([$new, $row['workout_id']]);
            header("Location: workout.php?uwid=$uwid"); exit;
        }
    }
   /* marcare completat */
   if(isset($_POST['complete'])){
        # trebuie să existe started_at și să fi trecut ≥5 min
        $start=$row['started_at'];
        if(!$start){ $msg="Pornește întâi antrenamentul!"; }
        elseif(time()-strtotime($start)<300){ $msg="Trebuie să treacă minim 5 minute."; }
        else{
            $pdo->prepare("
              UPDATE user_workout
              SET completed = TRUE, completed_at = NOW()
              WHERE id = ?")->execute([$uwid]);
            header("Location: workout.php?uwid=".$uwid); exit;
        }
   }
}

# re-încarcă rândul după eventualele update-uri
$q->execute([$uwid,$_SESSION['user_id']]);
$row=$q->fetch(PDO::FETCH_ASSOC);

# exerciții
$ex=$pdo->prepare("
    SELECT e.name,e.description,e.link,we.sets,we.reps,we.order_in_workout
    FROM workout_exercise we
    JOIN exercise e ON e.id = we.exercise_id
    WHERE we.workout_id = ?
    ORDER BY we.order_in_workout");
$ex->execute([$row['workout_id']]);
$exercises=$ex->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title><?=htmlspecialchars($row['name'])?> | FitFlow</title>
  <link rel="stylesheet" href="/css/styles.css">
  <link rel="stylesheet" href="/css/workout.css">
</head>
<body>
<nav>
  <h1><?=htmlspecialchars($row['name'])?></h1>
  <a href="workouts-gym.php" class="back-btn">Înapoi</a>
</nav>

<div class="container">
  <p class="info">Generat: <?=date('d.m.Y H:i',strtotime($row['generated_at']))?></p>
  <p class="info">Durată programată: <?=$row['duration_minutes']?> min</p>

  <?php if($row['started_at']): ?>
      <p class="info">Pornit: <?=date('H:i',strtotime($row['started_at']))?></p>
  <?php endif; ?>

  <?php if($row['completed']): ?>
      <p class="info" style="color:#5bff5b;font-weight:bold;">✔️ Completat la
          <?=date('H:i',strtotime($row['completed_at']))?></p>
  <?php endif; ?>

  <!-- buton complete -->
  <?php if (!$row['started_at'] && !$row['completed']): ?>
    <form method="POST" style="margin-bottom:1.5rem;">
        <input type="text" name="newname" placeholder="Nume nou"
                style="padding:.4rem;border-radius:6px;border:1px solid #666;width:60%;max-width:260px">
        <button type="submit" name="rename" class="back-btn">✏️ Redenumește</button>
    </form>
    <?php endif; ?>

  <!-- buton redenumire -->
  <form method="POST" style="margin-bottom:1.5rem;">
     <input type="text" name="newname" placeholder="Nume nou"
            style="padding:.4rem;border-radius:6px;border:1px solid #666;width:60%;max-width:260px">
     <button type="submit" name="rename" class="back-btn">✏️ Redenumește</button>
  </form>

  <?php if(isset($msg)) echo "<p style='color:#ff6060;'>$msg</p>"; ?>

  <h2>Exerciții:</h2>
  <?php if($exercises): foreach($exercises as $e): ?>
      <div class="exercise">
        <h3><?=htmlspecialchars($e['name'])?></h3>
        <p><?=htmlspecialchars($e['description'])?></p>
        <p class="info">Seturi: <?=$e['sets']?> &nbsp; | &nbsp; Rep: <?=$e['reps']?></p>
        <?php if($e['link']):?>
          <a href="<?=$e['link']?>" target="_blank">🔗 Tutorial</a>
        <?php endif;?>
      </div>
  <?php endforeach; else: ?>
      <p>Nu sunt exerciții înregistrate.</p>
  <?php endif; ?>
</div>
</body>
</html>