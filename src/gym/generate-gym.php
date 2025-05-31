<?php
session_start();

$pdo = new PDO(
    "pgsql:host=db;port=5432;dbname=wow_db",
    'root',
    'root',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$trainingLevels = $pdo->query("SELECT id, name FROM training_level ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

$muscleOptions = [
    "push-pull-legs" => [
        "push" => ["Chest", "Shoulders", "Arms"],
        "pull" => ["Back", "Arms"],
        "legs" => ["Legs"]
    ],
    "upper-lower" => [
        "upper" => ["Chest", "Back", "Shoulders", "Arms"],
        "lower" => ["Legs"]
    ],
    "bro split" => [
        "chest" => ["Chest"],
        "back"  => ["Back"],
        "arms"  => ["Arms"],
        "legs"  => ["Legs"],
        "shoulders" => ["Shoulders"]
    ],
    "arnold split" => [
        "chest-back"     => ["Chest", "Back"],
        "shoulders-arms" => ["Shoulders", "Arms"],
        "legs"           => ["Legs"]
    ]
];

$action          = $_POST['action']         ?? '';
$selectedSplit   = $_POST['tipAntrenament'] ?? 'push-pull-legs';
$selectedMuscle  = $_POST['muscleGroup']    ?? '';
$selectedDur     = $_POST['duration']       ?? '';
$selectedNivel   = $_POST['nivel']          ?? '';
$selectedLoc     = $_POST['location']       ?? '';

function fetchExercises(PDO $pdo, array $muscles, string $loc): array {
    $in  = implode(',', array_fill(0, count($muscles), '?'));
    $sql = "
        SELECT e.id, e.name, e.description, e.link
        FROM exercise e
        JOIN exercise_muscle_group emg ON e.id = emg.exercise_id
        JOIN muscle_group mg          ON mg.id = emg.muscle_group_id
        WHERE mg.name IN ($in)
          AND (
                (LOWER(?)='home'    AND e.is_bodyweight = TRUE)
             OR (LOWER(?)='outdoor' AND e.is_bodyweight = TRUE)
          )
        LIMIT 6";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($muscles, [$loc, $loc]));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$exercises = [];

if ($action && isset($muscleOptions[$selectedSplit][$selectedMuscle])) {
    $exercises = fetchExercises($pdo, $muscleOptions[$selectedSplit][$selectedMuscle], $selectedLoc);
}

if ($action === 'save' && $exercises && isset($_SESSION['user_id'])) {
    $pdo->beginTransaction();
    try {
        $w = $pdo->prepare("
            INSERT INTO workout (name, duration_minutes, type_id, level_id)
            VALUES (?, ?, 1, ?) RETURNING id
        ");
        $w->execute([
            'Custom ' . date('d.m H:i'),
            is_numeric($selectedDur) ? intval($selectedDur) : 60,
            $selectedNivel ?: null
        ]);
        $workoutId = $w->fetchColumn();

        $pdo->prepare("
            INSERT INTO user_workout (user_id, workout_id, completed)
            VALUES (?, ?, FALSE)
        ")->execute([$_SESSION['user_id'], $workoutId]);

        $wx = $pdo->prepare("
            INSERT INTO workout_exercise (workout_id, exercise_id, order_in_workout, sets, reps)
            VALUES (?, ?, ?, 3, 10)
        ");
        $order = 1;
        foreach ($exercises as $ex) {
            $wx->execute([$workoutId, $ex['id'], $order++]);
        }

        $pdo->prepare("
            INSERT INTO user_workout (user_id, workout_id)
            VALUES (?, ?)
        ")->execute([$_SESSION['user_id'], $workoutId]);

        $pdo->commit();
        $savedMessage = "✅ Antrenamentul a fost salvat!";
    } catch (Throwable $e) {
        $pdo->rollBack();
        $savedMessage = "❌ Eroare la salvare: " . $e->getMessage();
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
    <h1>Generează antrenament</h1>
    <a class="buton-inapoi" href="principal-gym.php">Înapoi</a>
</nav>

<form method="POST">
    <label for="tipAntrenament">Split antrenament:</label>
    <select id="tipAntrenament" name="tipAntrenament">
        <?php foreach ($muscleOptions as $split => $g): ?>
            <option value="<?= $split ?>" <?= $selectedSplit === $split ? 'selected' : '' ?>><?= ucwords($split) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="muscleGroup">Grupă mușchi:</label>
    <select id="muscleGroup" name="muscleGroup">
        <?php foreach ($muscleOptions[$selectedSplit] as $key => $m): ?>
            <option value="<?= $key ?>" <?= $selectedMuscle === $key ? 'selected' : '' ?>><?= implode(', ', $m) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="duration">Durată (minute):</label>
    <select id="duration" name="duration">
        <?php foreach (["30","60","90","120","150","Rich Piana"] as $d): ?>
            <option value="<?= $d ?>" <?= $selectedDur === $d ? 'selected' : '' ?>><?= $d === "Rich Piana" ? "😈Rich Piana😈" : $d ?></option>
        <?php endforeach; ?>
    </select>

    <label for="nivel">Nivel:</label>
    <select id="nivel" name="nivel">
        <option value="">-- Selectează nivel --</option>
        <?php foreach ($trainingLevels as $lvl): ?>
            <option value="<?= $lvl['id'] ?>" <?= $selectedNivel == $lvl['id'] ? 'selected' : '' ?>><?= $lvl['name'] ?></option>
        <?php endforeach; ?>
    </select>

    <label for="location">Locație:</label>
    <select id="location" name="location">
        <option value="home"    <?= $selectedLoc === 'home'    ? 'selected' : '' ?>>Acasă</option>
        <option value="outdoor" <?= $selectedLoc === 'outdoor' ? 'selected' : '' ?>>Aer liber</option>
    </select>

    <button type="submit" name="action" value="generate">Generează</button>
</form>

<?php if ($action === 'generate' && $exercises): ?>
    <section class="exercise-grid">
        <?php foreach ($exercises as $ex): ?>
            <div class="exercise-card">
                <h4><?= htmlspecialchars($ex['name']) ?></h4>
                <p><?= htmlspecialchars($ex['description']) ?></p>
                <a class="exercise-link" href="<?= $ex['link'] ?>" target="_blank">Tutorial</a>
            </div>
        <?php endforeach; ?>
    </section>

    <form method="POST">
        <?php foreach ($_POST as $k => $v): ?>
            <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
        <?php endforeach; ?>
        <button type="submit" name="action" value="save">💾 Salvează antrenamentul</button>
    </form>
<?php endif; ?>

<?php if (isset($savedMessage)) echo "<p>$savedMessage</p>"; ?>
</body>
</html>
