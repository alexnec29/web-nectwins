<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = new PDO(
    "pgsql:host=db;port=5432;dbname=wow_db", 'root', 'root',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$trainingLevels = $pdo->query("SELECT id, name FROM training_level ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$locations      = $pdo->query("SELECT name FROM location ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
$splits         = $pdo->query("SELECT name FROM split_type ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
$muscleGroups   = $pdo->query("SELECT name FROM muscle_group ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

$find = fn(string $name) => current(array_filter($muscleGroups, fn($g) => strtolower($g) === strtolower($name))) ?? $name;
$muscleOptions = [
    "push-pull-legs" => [
        "push" => [$find('Piept'), $find('Umeri'), $find('Brațe')],
        "pull" => [$find('Spate'), $find('Brațe')],
        "legs" => [$find('Picioare')]
    ],
    "upper-lower" => [
        "upper" => [$find('Piept'), $find('Spate'), $find('Umeri'), $find('Brațe')],
        "lower" => [$find('Picioare')]
    ],
    "bro split" => [
        "chest"     => [$find('Piept')],
        "back"      => [$find('Spate')],
        "arms"      => [$find('Brațe')],
        "legs"      => [$find('Picioare')],
        "shoulders" => [$find('Umeri')]
    ],
    "arnold split" => [
        "chest-back"     => [$find('Piept'), $find('Spate')],
        "shoulders-arms" => [$find('Umeri'), $find('Brațe')],
        "legs"           => [$find('Picioare')]
    ]
];

// ================== Preluăm inputul ==================
$action        = $_POST['action']  ?? '';
$splitKey      = $_POST['tipAntrenament'] ?? 'push-pull-legs';
$partKey       = $_POST['muscleGroup']    ?? '';
$dur           = $_POST['duration']       ?? '';
$levelId       = $_POST['nivel']          ?? '';
$locKey        = $_POST['location']       ?? '';

// ================== Funcție pentru a extrage exerciții ==================
function fetchExercises(PDO $pdo, array $muscles, string $loc): array {
    $in  = implode(',', array_fill(0, count($muscles), '?'));
    $sql = "
        SELECT DISTINCT e.id, e.name, e.description, e.link
        FROM exercise e
        JOIN exercise_muscle_group emg ON e.id = emg.exercise_id
        JOIN muscle_subgroup msg ON msg.id = emg.muscle_subgroup_id
        JOIN muscle_group mg ON mg.id = msg.principal_group
        WHERE mg.name IN ($in)
          AND (
              (LOWER(?) = 'acasă' AND e.is_bodyweight)
           OR (LOWER(?) = 'aer liber' AND e.is_bodyweight)
           OR (LOWER(?) = 'sală')
          )
        LIMIT 6";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($muscles, [$loc, $loc, $loc]));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$exercises = [];
if ($action === 'generate' && isset($muscleOptions[$splitKey][$partKey])) {
    $exercises = fetchExercises($pdo, $muscleOptions[$splitKey][$partKey], $locKey);
}

// ================== Salvăm antrenamentul ==================
if ($action === 'save' && $exercises) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO workout
            (name, duration_minutes, type_id, level_id, split_id, location_id, user_id)
            VALUES (?, ?, 1, ?, 1, 1, ?) RETURNING id");
        $stmt->execute([
            'Custom ' . date('d.m H:i'),
            is_numeric($dur) ? (int)$dur : 60,
            $levelId ?: null,
            $_SESSION['user_id']
        ]);
        $wid = $stmt->fetchColumn();

        $ins = $pdo->prepare("INSERT INTO workout_exercise
            (workout_id, exercise_id, order_in_workout, sets, reps)
            VALUES (?, ?, ?, 3, 10)");
        $order = 1;
        foreach ($exercises as $ex) {
            $ins->execute([$wid, $ex['id'], $order++]);
        }

        $pdo->prepare("INSERT INTO user_workout (user_id, workout_id, completed)
                       VALUES (?, ?, FALSE)")->execute([$_SESSION['user_id'], $wid]);
        $pdo->commit();
        $msg = '✅ Antrenamentul a fost salvat!';
    } catch (Throwable $e) {
        $pdo->rollBack();
        $msg = '❌ ' . $e->getMessage();
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
    <h1>Generează antrenament (Gym)</h1>
    <a class="buton-inapoi" href="principal-gym.php">Înapoi</a>
</nav>

<form method="POST">
    <label>Split antrenament:</label>
    <select name="tipAntrenament">
        <?php foreach ($muscleOptions as $split => $gr): ?>
            <option value="<?= $split ?>" <?= $split === $splitKey ? 'selected':'' ?>>
                <?= ucwords($split) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Grupă mușchi:</label>
    <select name="muscleGroup">
        <?php foreach ($muscleOptions[$splitKey] as $key => $arr): ?>
            <option value="<?= $key ?>" <?= $key === $partKey ? 'selected':'' ?>>
                <?= implode(', ', $arr) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Durată (min):</label>
    <select name="duration">
        <?php foreach ([30,60,90,120,150] as $d): ?>
            <option value="<?= $d ?>" <?= $dur == $d ? 'selected':'' ?>><?= $d ?></option>
        <?php endforeach; ?>
    </select>

    <label>Nivel:</label>
    <select name="nivel">
        <option value="">-- selectează --</option>
        <?php foreach ($trainingLevels as $lvl): ?>
            <option value="<?= $lvl['id'] ?>" <?= $levelId == $lvl['id'] ? 'selected':'' ?>>
                <?= $lvl['name'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Locație:</label>
    <select name="location">
        <?php foreach ($locations as $loc): ?>
            <option value="<?= strtolower($loc) ?>" <?= strtolower($locKey) === strtolower($loc) ? 'selected':'' ?>>
                <?= ucfirst($loc) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button name="action" value="generate">Generează</button>
</form>

<?php if ($action === 'generate'): ?>
    <?php if ($exercises): ?>
        <section class="exercise-grid">
            <?php foreach ($exercises as $ex): ?>
                <div class="exercise-card">
                    <h4><?= htmlspecialchars($ex['name']) ?></h4>
                    <p><?= htmlspecialchars($ex['description'] ?? '-') ?></p>
                    <?php if ($ex['link']): ?>
                        <a href="<?= htmlspecialchars($ex['link']) ?>" target="_blank">Tutorial</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>
        <form method="POST">
            <?php foreach ($_POST as $k => $v): ?>
                <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
            <?php endforeach; ?>
            <button name="action" value="save">💾 Salvează antrenamentul</button>
        </form>
    <?php else: ?>
        <p>❌ Nu am găsit exerciții pentru opțiunile alese.</p>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($msg)) echo '<p>'.$msg.'</p>'; ?>
</body>
</html>