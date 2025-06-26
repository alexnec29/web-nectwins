<?php
session_start();
require './../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];
$section = $_GET['section'] ?? 'gym';

$sectionMap = [
    'gym'    => 'Gym',
    'kineto' => 'kinetoterapie',
    'fizio'  => 'fizioterapie',
];

$sectionKey = strtolower($section);
$typeName   = $sectionMap[$sectionKey] ?? 'Gym';

$typeStmt = $pdo->prepare("SELECT id FROM training_type WHERE LOWER(name) = LOWER(:name) LIMIT 1");
$typeStmt->execute(['name' => ucfirst($typeName)]);
$typeId = $typeStmt->fetchColumn() ?: 1;

$dbSection = $sectionMap[$sectionKey] ?? 'gym';

$trainingLevels = $pdo->query("SELECT id, name FROM training_level ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$trainingGoals  = $pdo->query("SELECT id, name FROM training_goal ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

$locationsStmt = $pdo->prepare("SELECT l.id, l.name FROM location l JOIN location_section ls ON ls.location_id = l.id WHERE LOWER(TRIM(ls.section)) = LOWER(:section) ORDER BY l.name");
$locationsStmt->execute(['section' => $sectionKey]);
$locations = $locationsStmt->fetchAll(PDO::FETCH_ASSOC);

$splitsStmt = $pdo->prepare("SELECT st.id, st.name FROM split_type st JOIN section_split ss ON ss.split_id = st.id WHERE LOWER(TRIM(ss.section)) = LOWER(:section) ORDER BY st.id");
$splitsStmt->execute(['section' => $dbSection]);
$splits = $splitsStmt->fetchAll(PDO::FETCH_ASSOC);

$slugify = fn($name) => strtolower(preg_replace('/[^a-z]+/i', '-', $name));
$slug2id = [];
foreach ($splits as $s) {
    $slug2id[$slugify($s['name'])] = $s['id'];
}

$act   = $_POST['action'] ?? '';
$split = $_POST['tipAntrenament'] ?? array_key_first($slug2id);
if (!isset($slug2id[$split])) {
    $split = array_key_first($slug2id);
}
$splitId = $slug2id[$split] ?? null;

$part   = ctype_digit($_POST['muscleGroup'] ?? '') ? (int)$_POST['muscleGroup'] : null;
$mins   = (int)($_POST['duration'] ?? 60);
$level  = ctype_digit($_POST['nivel'] ?? '') ? (int)$_POST['nivel'] : null;
$goal   = ctype_digit($_POST['goal'] ?? '') ? (int)$_POST['goal'] : null;
$locId  = ctype_digit($_POST['location'] ?? '') ? (int)$_POST['location'] : null;

$subtypes = [];
if ($splitId) {
    $stmt = $pdo->prepare("SELECT id, name FROM split_subtype WHERE split_id = :sid ORDER BY id");
    $stmt->execute(['sid' => $splitId]);
    $subtypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$opt = [];
foreach ($splits as $s) {
    $slug = $slugify($s['name']);
    $stmt = $pdo->prepare("SELECT ssm.split_subtype_id, mg.name FROM split_subtype_muscle_group ssm JOIN muscle_group mg ON mg.id = ssm.muscle_group_id JOIN split_subtype ss ON ss.id = ssm.split_subtype_id WHERE ss.split_id = :sid");
    $stmt->execute(['sid' => $s['id']]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $opt[$slug][$row['split_subtype_id']][] = $row['name'];
    }
}
if (!isset($opt[$split][$part])) {
    $part = array_key_first($opt[$split] ?? []);
}

function getFilteredExercises(PDO $pdo, int $userId, array $groups, ?int $levelId, int $duration, int $typeId, int $locationId): array {
    if (empty($groups)) return [];
    $stmt = $pdo->prepare("SELECT * FROM get_exercises_filtered(:user_id, :groups, :level_id, :duration, :type_id, :location_id)");
    $stmt->execute([
        'user_id' => $userId,
        'groups' => '{' . implode(',', array_map(fn($g) => '"' . $g . '"', $groups)) . '}',
        'level_id' => $levelId,
        'duration' => $duration,
        'type_id' => $typeId,
        'location_id' => $locationId,
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSetsAndRepsByGoal(?int $goalId): array {
    return match ($goalId) {
        1 => ['reps' => 3,  'sets' => 5],
        2 => ['reps' => 12, 'sets' => 4],
        3 => ['reps' => 18, 'sets' => 2],
        default => ['reps' => 10, 'sets' => 3],
    };
}

$sr  = getSetsAndRepsByGoal($goal);
$ex  = [];
$msg = '';
if (in_array($act, ['generate', 'save']) && isset($opt[$split][$part])) {
    try {
        $ex = getFilteredExercises($pdo, $userId, $opt[$split][$part], $level, $mins, $typeId, $locId);
    } catch (PDOException $e) {
        $msg = str_contains($e->getMessage(), 'P2001') ? 'Nu s-au găsit exerciții potrivite.' : 'Eroare: ' . $e->getMessage();
    }
}

if ($act === 'save' && $ex) {
    if (!$splitId || !$locId) {
        $msg = 'Split sau locație invalidă.';
    } else {
        try {
            $exerciseIds = array_column($ex, 'id');
            $stmt = $pdo->prepare("INSERT INTO workout(name, duration_minutes, type_id, level_id, split_id, location_id, user_id, section, goal_id) VALUES (:name, :duration, :type_id, :level_id, :split_id, :location_id, :user_id, :section, :goal_id) RETURNING id");
            $stmt->execute([
                'name' => 'Custom ' . date('d.m H:i'),
                'duration' => $mins,
                'type_id' => $typeId,
                'level_id' => $level,
                'split_id' => $splitId,
                'location_id' => $locId,
                'user_id' => $userId,
                'section' => $sectionKey,
                'goal_id' => $goal
            ]);
            $workoutId = $stmt->fetchColumn();

            $insertStmt = $pdo->prepare("INSERT INTO workout_exercise(workout_id, exercise_id, order_in_workout, sets, reps) VALUES (:wid, :eid, :ord, :sets, :reps)");
            $order = 1;
            foreach ($exerciseIds as $eid) {
                $insertStmt->execute([
                    'wid' => $workoutId,
                    'eid' => $eid,
                    'ord' => $order++,
                    'sets' => $sr['sets'],
                    'reps' => $sr['reps']
                ]);
            }
            $msg = 'Salvat! Vezi în lista de antrenamente.';
        } catch (Throwable $e) {
            $msg = 'Eroare la salvare: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8" />
    <title>Generare Antrenament | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css" />
    <link rel="stylesheet" href="/css/generate.css" />
</head>

<body>
    <nav>
        <h1>Generează antrenament</h1>
        <a class="buton-inapoi" href="../principal.php?section=<?= htmlspecialchars($section) ?>">Înapoi</a>
    </nav>

    <form method="POST" action="?section=<?= htmlspecialchars($section) ?>">
        <label>Split:</label>
        <select name="tipAntrenament" onchange="this.form.submit()">
            <?php foreach ($splits as $s):
                $slug = $slugify($s['name']);
            ?>
                <option value="<?= $slug ?>" <?= ($slug === $split) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Grupă:</label>
        <select name="muscleGroup">
            <?php foreach ($subtypes as $st): ?>
                <option value="<?= $st['id'] ?>" <?= ($st['id'] == $part) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($st['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Durată (min):</label>
        <select name="duration">
            <?php foreach ([30, 60, 90, 120, 150] as $d): ?>
                <option value="<?= $d ?>" <?= ($d == $mins) ? 'selected' : '' ?>>
                    <?= $d ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Nivel:</label>
        <select name="nivel">
            <option value="">--</option>
            <?php foreach ($trainingLevels as $l): ?>
                <option value="<?= $l['id'] ?>" <?= ($l['id'] == $level) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($l['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Obiectiv:</label>
        <select name="goal">
            <option value="">--</option>
            <?php foreach ($trainingGoals as $g): ?>
                <option value="<?= $g['id'] ?>" <?= ($g['id'] == $goal) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Locație:</label>
        <select name="location" required>
            <?php foreach ($locations as $l): ?>
                <option value="<?= $l['id'] ?>" <?= ($l['id'] == $locId) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($l['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button name="action" value="generate">Generează</button>
    </form>

    <?php if ($msg): ?>
        <p><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <?php if ($act === 'generate' && $ex): ?>
        <section class="exercise-grid">
            <?php foreach ($ex as $e): ?>
                <div class="exercise-card">
                    <h4><?= htmlspecialchars($e['name']) ?></h4>
                    <p><?= htmlspecialchars($e['description'] ?? '-') ?></p>
                    <p><strong><?= $sr['sets'] ?> seturi × <?= $sr['reps'] ?> repetări</strong></p>
                    <?php if ($e['link']): ?>
                        <a href="<?= htmlspecialchars($e['link']) ?>" target="_blank">Tutorial</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>

        <form method="POST" action="?section=<?= htmlspecialchars($section) ?>">
            <?php foreach ($_POST as $k => $v): ?>
                <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>" />
            <?php endforeach; ?>
            <button name="action" value="save">💾 Salvează</button>
        </form>
    <?php endif; ?>
</body>

</html>