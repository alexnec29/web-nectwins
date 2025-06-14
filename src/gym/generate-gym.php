<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];

$pdo = new PDO(
    "pgsql:host=db;port=5432;dbname=wow_db",
    'root',
    'root',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$trainingLevels = $pdo->query("SELECT id,name FROM training_level ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$locations      = $pdo->query("SELECT id,name FROM location WHERE Trim(section) = 'gym' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$splits         = $pdo->query("SELECT id,name FROM split_type where id <= 4 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$groups         = $pdo->query("SELECT name FROM muscle_group ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

$slugify = fn($name) => strtolower(preg_replace('/[^a-z]+/i', '-', $name));
$slug2id = [];
foreach ($splits as $s) {
    $slug2id[$slugify($s['name'])] = $s['id'];
}

$g = fn($n) => current(array_filter($groups, fn($x) => strtolower($x) == strtolower($n))) ?? $n;

$act   = $_POST['action']         ?? '';
$split = $_POST['tipAntrenament'] ?? 'push-pull-legs';
$part  = $_POST['muscleGroup']    ?? '';
$mins  = (int)($_POST['duration'] ?? 60);
$level = ctype_digit($_POST['nivel'] ?? '') ? (int)$_POST['nivel'] : null;
$locId = ctype_digit($_POST['location'] ?? '') ? (int)$_POST['location'] : null;
$splitId = $slug2id[$split] ?? null;
$subtypes = [];
if ($splitId) {
    $stmt = $pdo->prepare("
        SELECT id, name 
          FROM split_subtype 
         WHERE split_id = :sid 
         ORDER BY id
    ");
    $stmt->execute(['sid' => $splitId]);
    $subtypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFilteredExercises(PDO $pdo, int $userId, array $groups, ?int $levelId, int $duration, int $typeId, int $locationId): array
{
    if (empty($groups)) return [];

    $sql = "SELECT * FROM get_exercises_filtered(:user_id, :groups, :level_id, :duration, :type_id, :location_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'user_id'     => $userId,
        'groups'      => '{' . implode(',', array_map(fn($g) => '"' . $g . '"', $groups)) . '}',
        'level_id'    => $levelId,
        'duration'    => $duration,
        'type_id'     => $typeId,
        'location_id' => $locationId
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$ex = [];
$msg = '';
$opt = [];
foreach ($splits as $s) {
    $slug = $slugify($s['name']);
    $stmt = $pdo->prepare("
        SELECT ssm.split_subtype_id, mg.name
        FROM split_subtype_muscle_group ssm
        JOIN muscle_group mg ON mg.id = ssm.muscle_group_id
        JOIN split_subtype ss ON ss.id = ssm.split_subtype_id
        WHERE ss.split_id = :sid
    ");
    $stmt->execute(['sid' => $s['id']]);
    $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($raw as $row) {
        $opt[$slug][$row['split_subtype_id']][] = $row['name'];
    }
}
$part = ctype_digit($_POST['muscleGroup'] ?? '') ? (int)$_POST['muscleGroup'] : null;
if (in_array($act, ['generate', 'save']) && isset($opt[$split][$part])) {
    $groupsSelected = $opt[$split][$part] ?? [];
    try {
        $ex = getFilteredExercises($pdo, $userId, $groupsSelected, $level, $mins, 1, $locId);
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'P2001')) {
            $msg = '❌ Nu s-au găsit exerciții potrivite.';
        } else {
            $msg = '❌ Eroare: ' . $e->getMessage();
        }
        $ex = [];
    }    
}

if ($act === 'save' && $ex) {
    $splitId = $slug2id[$split] ?? null;
    if (!$splitId || !$locId) {
        $msg = '❌ Split sau locație invalidă. Split: ' . htmlspecialchars($split) . ' → ' . ($splitId ?? 'null');
    } else {
        try {
            $exerciseIds = array_column($ex, 'id');
            $exerciseArray = '{' . implode(',', $exerciseIds) . '}';
            $sql = "CALL save_generated_workout(
                :name, :duration, :type_id, :level_id, :split_id, :location_id, :user_id, :exercise_ids, :section
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
                'exercise_ids' => $exerciseArray,
                'section'      => 'gym'
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
    <nav>
        <h1>Generează antrenament</h1><a class="buton-inapoi" href="principal-gym.php">Înapoi</a>
    </nav>

    <form method="POST">
        <label>Split:</label>
        <select name="tipAntrenament" onchange="this.form.submit()">
            <?php foreach ($splits as $s):
                $slug = $slugify($s['name']);
            ?>
                <option
                    value="<?= htmlspecialchars($slug) ?>"
                    <?= ($slug === $split) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Grupă:</label>
        <select name="muscleGroup">
            <?php foreach ($subtypes as $st): ?>
                <option
                    value="<?= $st['id'] ?>"
                    <?= ($st['id'] === $part) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($st['name']) ?>
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
        <select name="nivel">
            <option value="">--</option>
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