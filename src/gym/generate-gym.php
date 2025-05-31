<?php
/*-----------------------------------------
  generate-workout-gym.php – versiune minimă
------------------------------------------*/
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$pdo = new PDO(
    "pgsql:host=db;port=5432;dbname=wow_db",
    'root','root',
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
);

/* === dropdowns === */
$trainingLevels = $pdo->query("SELECT id,name FROM training_level ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$locations      = $pdo->query("SELECT id,name FROM location ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$splits         = $pdo->query("SELECT id,name FROM split_type")->fetchAll(PDO::FETCH_ASSOC);
$groups         = $pdo->query("SELECT name FROM muscle_group ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

/* --- map split slug → id --- */
$slug2id = [];
foreach ($splits as $s) {
    $slug2id[strtolower(preg_replace('/[^a-z]+/i','-', $s['name']))] = $s['id'];
}

/* --- helper pentru “nume exact” --- */
$g = fn($n)=>current(array_filter($groups,fn($x)=>strtolower($x)==strtolower($n)))??$n;

/* --- definiţia split-urilor --- */
$opt = [
  "push-pull-legs"=>[
      "push"=>[$g('Piept'),$g('Umeri'),$g('Brațe')],
      "pull"=>[$g('Spate'),$g('Brațe')],
      "legs"=>[$g('Picioare')]
  ],
  "upper-lower"=>[
      "upper"=>[$g('Piept'),$g('Spate'),$g('Umeri'),$g('Brațe')],
      "lower"=>[$g('Picioare')]
  ],
  "bro split"=>[
      "chest"=>[$g('Piept')],
      "back"=>$g('Spate'),
      "arms"=>$g('Brațe'),
      "legs"=>$g('Picioare'),
      "shoulders"=>$g('Umeri')
  ],
  "arnold split"=>[
      "chest-back"=>[$g('Piept'),$g('Spate')],
      "shoulders-arms"=>[$g('Umeri'),$g('Brațe')],
      "legs"=>$g('Picioare')
  ]
];

/* --- input --- */
$act   = $_POST['action']          ?? '';
$split = $_POST['tipAntrenament']  ?? 'push-pull-legs';
$part  = $_POST['muscleGroup']     ?? '';
$mins  = (int)($_POST['duration']  ?? 60);
$level = ctype_digit($_POST['nivel']??'') ? (int)$_POST['nivel'] : null;
$locId = ctype_digit($_POST['location']??'') ? (int)$_POST['location'] : null;

/* --- funcţie: extrage exerciţii (fără filtru locaţie) --- */
function getExercises(PDO $pdo,array $muscles):array{
    if(!$muscles) return [];
    $ph=implode(',',array_fill(0,count($muscles),'?'));
    $sql="SELECT DISTINCT e.id,e.name,e.description,e.link
          FROM exercise e
          JOIN exercise_muscle_group emg ON e.id=emg.exercise_id
          JOIN muscle_subgroup ms ON ms.id=emg.muscle_subgroup_id
          JOIN muscle_group mg ON mg.id=ms.principal_group
          WHERE mg.name IN ($ph) LIMIT 6";
    $st=$pdo->prepare($sql); $st->execute($muscles);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

/* --- generate --- */
$ex=[]; $msg='';

if(isset($opt[$split][$part])) {
    $ex = getExercises($pdo, $opt[$split][$part]);
}

/* --- save --- */
if($act==='save' && $ex){
  $splitId=$slug2id[$split] ?? null;
  if(!$splitId||!$locId){ $msg='❌ Split/Locaţie invalidă.'; }
  else{
    $pdo->beginTransaction();
    try{
      $w=$pdo->prepare("INSERT INTO workout
          (name,duration_minutes,type_id,level_id,split_id,location_id,user_id)
       VALUES (?,?,?,?,?,?,?) RETURNING id");
      $w->execute([
        'Custom '.date('d.m H:i'), $mins, 1, $level, $splitId, $locId, $_SESSION['user_id']
      ]);
      $wid=$w->fetchColumn();

      $ins=$pdo->prepare("INSERT INTO workout_exercise
             (workout_id,exercise_id,order_in_workout,sets,reps)
             VALUES (?,?,?,3,10)");
      $o=1; foreach($ex as $e){ $ins->execute([$wid,$e['id'],$o++]); }

      $pdo->commit();
      $msg='✅ Salvat! Vezi în lista de antrenamente.';
      // header('Location: workouts-gym.php'); exit;   // ← decomentează dacă vrei redirect
    }catch(Throwable $e){
      $pdo->rollBack(); $msg='❌ '.$e->getMessage();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ro"><head>
<meta charset="UTF-8">
<title>Generare Antrenament | FitFlow</title>
<link rel="stylesheet" href="/css/styles.css">
<link rel="stylesheet" href="/css/generate.css">
</head><body>
<nav><h1>Generează antrenament</h1><a class="buton-inapoi" href="principal-gym.php">Înapoi</a></nav>

<form method="POST">
  <label>Split:</label>
  <select name="tipAntrenament"><?php foreach($opt as $k=>$_):?>
      <option value="<?=$k?>" <?=$k===$split?'selected':''?>><?=ucwords(str_replace('-',' ',$k))?></option>
  <?php endforeach;?></select>

  <label>Grupă:</label>
  <select name="muscleGroup"><?php foreach($opt[$split] as $k=>$arr):?>
      <option value="<?=$k?>" <?=$k===$part?'selected':''?>><?=ucfirst($k)?></option>
  <?php endforeach;?></select>

  <label>Durată (min):</label>
  <select name="duration"><?php foreach([30,60,90,120,150] as $d):?>
      <option value="<?=$d?>" <?=$d==$mins?'selected':''?>><?=$d?></option>
  <?php endforeach;?></select>

  <label>Nivel:</label>
  <select name="nivel"><option value="">--</option>
    <?php foreach($trainingLevels as $l):?>
      <option value="<?=$l['id']?>" <?=$l['id']==$level?'selected':''?>><?=$l['name']?></option>
    <?php endforeach;?></select>

  <label>Locaţie:</label>
  <select name="location" required>
    <?php foreach($locations as $l):?>
      <option value="<?=$l['id']?>" <?=$l['id']==$locId?'selected':''?>><?=ucfirst($l['name'])?></option>
    <?php endforeach;?></select>

  <button name="action" value="generate">Generează</button>
</form>

<?php if($act==='generate'):?>
  <?php if($ex):?>
    <section class="exercise-grid"><?php foreach($ex as $e):?>
      <div class="exercise-card">
         <h4><?=htmlspecialchars($e['name'])?></h4>
         <p><?=htmlspecialchars($e['description']??'-')?></p>
         <?php if($e['link']):?><a href="<?=htmlspecialchars($e['link'])?>" target="_blank">Tutorial</a><?php endif;?>
      </div><?php endforeach;?>
    </section>
    <form method="POST"><?php foreach($_POST as $k=>$v):?>
        <input type="hidden" name="<?=htmlspecialchars($k)?>" value="<?=htmlspecialchars($v)?>">
    <?php endforeach;?><button name="action" value="save">💾 Salvează</button></form>
  <?php else:?>
    <p>❌ Nicio potrivire la exerciţii.</p>
  <?php endif;?>
<?php endif;?>

<?php if($msg) echo "<p>$msg</p>";?>
</body></html>
