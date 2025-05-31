<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pdo = new PDO(
    "pgsql:host=db;port=5432;dbname=wow_db",
    'root','root',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

/* === Dropdowns === */
$trainingLevels = $pdo->query("SELECT id,name FROM training_level ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$locations      = $pdo->query("SELECT id,name FROM location ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$splits         = $pdo->query("SELECT id,name FROM split_type ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$groups         = $pdo->query("SELECT name FROM muscle_group ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

/* === Mapping split_name_slug → id === */
$slugify = fn($name) => strtolower(preg_replace('/[^a-z]+/i', '-', $name));
$slug2id = [];
foreach ($splits as $s) {
    $slug2id[$slugify($s['name'])] = $s['id'];
}

/* === Helper pentru grupă exactă (case-insensitive) === */
$g = fn($n) => current(array_filter($groups, fn($x) => strtolower($x) == strtolower($n))) ?? $n;

/* === Definiția split-urilor === */
$opt = [
    "push-pull-legs" => [
        "push" => [$g('Piept'), $g('Umeri'), $g('Brațe')],
        "pull" => [$g('Spate'), $g('Brațe')],
        "legs" => [$g('Picioare')]
    ],
    "upper-lower" => [
        "upper" => [$g('Piept'), $g('Spate'), $g('Umeri'), $g('Brațe')],
        "lower" => [$g('Picioare')]
    ],
    "bro-split" => [
        "chest"     => [$g('Piept')],
        "back"      => [$g('Spate')],
        "arms"      => [$g('Brațe')],
        "legs"      => [$g('Picioare')],
        "shoulders" => [$g('Umeri')]
    ],
    "full-body" => [
        "full-body" => [$g('Piept'), $g('Umeri'), $g('Brațe'), $g('Picioare'), $g('Spate')]
    ]
];

/* === Inputuri === */
$act   = $_POST['action']         ?? '';
$split = $_POST['tipAntrenament'] ?? 'push-pull-legs';
$part  = $_POST['muscleGroup']    ?? '';
$mins  = (int)($_POST['duration'] ?? 60);
$level = ctype_digit($_POST['nivel'] ?? '') ? (int)$_POST['nivel'] : null;
$locId = ctype_digit($_POST['location'] ?? '') ? (int)$_POST['location'] : null;

/* === Funcție: extrage exerciții (fără filtru locație) === */
function getExercises(PDO $pdo, array $muscles): array {
    if (!$muscles) return [];
    $sql = "SELECT * FROM get_exercises_by_groups(:groups)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['groups' => '{' . implode(',', array_map(fn($x) => '"' . $x . '"', $muscles)) . '}']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* === Generate === */
$ex = []; $msg = '';
if (isset($opt[$split][$part])) {
    $ex = getExercises($pdo, $opt[$split][$part]);
}

/* === Save === */
if ($act === 'save' && $ex) {
    $splitId = $slug2id[$split] ?? null;
    if (!$splitId || !$locId) {
        $msg = '❌ Split sau locație invalidă. Split: ' . htmlspecialchars($split) . ' → ' . ($splitId ?? 'null');
    } else {
        try {
            $exerciseIds = array_column($ex, 'id');
            $exerciseArray = '{' . implode(',', $exerciseIds) . '}';
            $sql = "CALL save_generated_workout(
                :name, :duration, :type_id, :level_id, :split_id, :location_id, :user_id, :exercise_ids
            )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'name'         => 'Custom ' . date('d.m H:i'),
                'duration'     => $mins,
                'type_id'      => 1,
                'level_id'     => $level,
                'split_id'     => $splitId,
                'location_id'  => $locId,
                'user_id'      => $_SESSION['user_id'],
                'exercise_ids' => $exerciseArray
            ]);
            $msg = '✅ Salvat! Vezi în lista de antrenamente.';
        } catch (Throwable $e) {
            $msg = '❌ ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Generare Antrenament | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/generate.css">
</head>
<body>
<nav><h1>Generează antrenament</h1><a class="buton-inapoi" href="principal-gym.php">Înapoi</a></nav>

<form method="POST">
    <label>Split:</label>
    <select name="tipAntrenament">
        <?php foreach ($opt as $k => $_): ?>
            <option value="<?= $k ?>" <?= $k === $split ? 'selected' : '' ?>>
                <?= ucwords(str_replace('-', ' ', $k)) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Grupă:</label>
    <select name="muscleGroup">
        <?php foreach ($opt[$split] as $k => $_): ?>
            <option value="<?= $k ?>" <?= $k === $part ? 'selected' : '' ?>>
                <?= ucfirst($k) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Durată (min):</label>
    <select name="duration">
        <?php foreach ([30, 60, 90, 120, 150] as $d): ?>
            <option value="<?= $d ?>" <?= $d == $mins ? 'selected' : '' ?>><?= $d ?></option>
        <?php endforeach; ?>
    </select>

    <label>Nivel:</label>
    <select name="nivel"><option value="">--</option>
        <?php foreach ($trainingLevels as $l): ?>
            <option value="<?= $l['id'] ?>" <?= $l['id'] == $level ? 'selected' : '' ?>><?= $l['name'] ?></option>
        <?php endforeach; ?>
    </select>

    <label>Locație:</label>
    <select name="location" required>
        <?php foreach ($locations as $l): ?>
            <option value="<?= $l['id'] ?>" <?= $l['id'] == $locId ? 'selected' : '' ?>><?= ucfirst($l['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <button name="action" value="generate">Generează</button>
</form>

<?php if ($act === 'generate'): ?>
    <?php if ($ex): ?>
        <section class="exercise-grid">
            <?php foreach ($ex as $e): ?>
                <div class="exercise-card">
                    <h4><?= htmlspecialchars($e['name']) ?></h4>
                    <p><?= htmlspecialchars($e['description'] ?? '-') ?></p>
                    <?php if ($e['link']): ?>
                        <a href="<?= htmlspecialchars($e['link']) ?>" target="_blank">Tutorial</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>
        <form method="POST">
            <?php foreach ($_POST as $k => $v): ?>
                <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
            <?php endforeach; ?>
            <button name="action" value="save">💾 Salvează</button>
        </form>
    <?php else: ?>
        <p>❌ Nicio potrivire la exerciții.</p>
    <?php endif; ?>
<?php endif; ?>

<?php if ($msg) echo "<p>$msg</p>"; ?>
</body>
</html>