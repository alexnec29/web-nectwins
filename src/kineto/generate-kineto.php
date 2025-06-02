<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pdo = new PDO("pgsql:host=db;port=5432;dbname=wow_db", 'root', 'root', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$trainingLevels = $pdo->query("SELECT id,name FROM training_level ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$locations      = $pdo->query("SELECT id,name FROM location ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$splits         = $pdo->query("SELECT id,name FROM split_type ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$groups         = $pdo->query("SELECT name FROM muscle_group ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

$slugify = fn($name) => strtolower(preg_replace('/[^a-z]+/i', '-', $name));
$slug2id = [];
foreach ($splits as $s) {
    $slug2id[$slugify($s['name'])] = $s['id'];
}
$g = fn($n) => current(array_filter($groups, fn($x) => strtolower($x) == strtolower($n))) ?? $n;

$kinetoOptions = [
    "recuperare" => [
        "genunchi" => [$g('Picioare')],
        "umar"     => [$g('Umeri')],
        "spate"    => [$g('Spate')]
    ],
    "mobilitate" => [
        "general" => [$g('Piept'), $g('Spate'), $g('Umeri'), $g('Brațe'), $g('Picioare')],
        "membre"  => [$g('Brațe'), $g('Picioare')]
    ],
    "intarire" => [
        "trunchi" => [$g('Piept'), $g('Spate'), $g('Umeri')],
        "postura" => [$g('Spate'), $g('Umeri'), $g('Piept')]
    ]
];

$act              = $_POST['action'] ?? '';
$selectedProgram  = $_POST['tipProgram'] ?? 'recuperare';
$selectedZone     = $_POST['zonaVizata'] ?? '';
$selectedDuration = (int)($_POST['duration'] ?? 60);
$selectedNivel    = ctype_digit($_POST['nivel'] ?? '') ? (int)$_POST['nivel'] : null;
$selectedLocation = ctype_digit($_POST['location'] ?? '') ? (int)$_POST['location'] : null;

$msg = '';
$exercises = [];

function getExercises(PDO $pdo, array $muscles): array
{
    if (empty($muscles)) return [];
    $stmt = $pdo->prepare("SELECT * FROM get_exercises_by_groups(:groups)");
    $stmt->execute(['groups' => '{' . implode(',', array_map(fn($x) => "\"$x\"", $muscles)) . '}']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function isValidForm($program, $zone, $nivel, $location, $opts): bool
{
    return isset($opts[$program], $opts[$program][$zone]) && $nivel && $location;
}

// Generare / salvare
if ($act === 'generate' || $act === 'save') {
    if (isValidForm($selectedProgram, $selectedZone, $selectedNivel, $selectedLocation, $kinetoOptions)) {
        $muscleGroups = $kinetoOptions[$selectedProgram][$selectedZone];
        $exercises = getExercises($pdo, $muscleGroups);
    }
}

if ($act === 'save' && isValidForm($selectedProgram, $selectedZone, $selectedNivel, $selectedLocation, $kinetoOptions)) {
    $splitId = $slug2id[$selectedProgram] ?? null;
    if (!$splitId || empty($exercises)) {
        $msg = '❌ Date incomplete pentru salvare.';
    } else {
        try {
            $stmt = $pdo->prepare("CALL save_generated_workout(
                :name, :duration, :type_id, :level_id, :split_id, :location_id, :user_id, :exercise_ids, :section
            )");
            $stmt->execute([
                'name'         => 'Kineto ' . ucfirst($selectedProgram) . ' ' . date('d.m H:i'),
                'duration'     => $selectedDuration,
                'type_id'      => 2,
                'level_id'     => $selectedNivel,
                'split_id'     => $splitId,
                'location_id'  => $selectedLocation,
                'user_id'      => $_SESSION['user_id'],
                'exercise_ids' => '{' . implode(',', array_column($exercises, 'id')) . '}',
                'section'      => 'kineto'
            ]);
            $msg = '✅ Salvat! Vezi în lista de programe.';
            $exercises = [];
        } catch (Throwable $e) {
            $msg = '❌ Eroare la salvare: ' . $e->getMessage();
        }
    }
} elseif ($act === 'generate' && empty($exercises)) {
    $msg = '❌ Nicio potrivire la exerciții pentru selecția dată.';
}
?>

<!-- HTML -->

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <title>Generare Program | KinetoFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/generate.css">
</head>

<body>
    <nav>
        <h1>Generează program kinetoterapie</h1>
        <a class="buton-inapoi" href="principal-kineto.php">Înapoi</a>
    </nav>

    <form method="POST">
        <label>Program:</label>
        <select name="tipProgram" onchange="this.form.submit()">
            <?php foreach ($kinetoOptions as $k => $v): ?>
                <option value="<?= $k ?>" <?= $k === $selectedProgram ? 'selected' : '' ?>><?= ucfirst($k) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Zonă vizată:</label>
        <select name="zonaVizata">
            <?php foreach ($kinetoOptions[$selectedProgram] as $k => $_): ?>
                <option value="<?= $k ?>" <?= $k === $selectedZone ? 'selected' : '' ?>><?= ucfirst($k) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Durată (min):</label>
        <select name="duration">
            <?php foreach ([30, 60, 90, 120, 150] as $d): ?>
                <option value="<?= $d ?>" <?= $d == $selectedDuration ? 'selected' : '' ?>><?= $d ?></option>
            <?php endforeach; ?>
        </select>

        <label>Nivel:</label>
        <select name="nivel">
            <option value="">--</option>
            <?php foreach ($trainingLevels as $l): ?>
                <option value="<?= $l['id'] ?>" <?= $l['id'] == $selectedNivel ? 'selected' : '' ?>><?= $l['name'] ?></option>
            <?php endforeach; ?>
        </select>

        <label>Locație:</label>
        <select name="location" required>
            <option value="">--</option>
            <?php foreach ($locations as $l): ?>
                <option value="<?= $l['id'] ?>" <?= $l['id'] == $selectedLocation ? 'selected' : '' ?>><?= ucfirst($l['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <button name="action" value="generate">Generează</button>
    </form>

    <?php if ($msg): ?>
        <p style="margin: 1rem 0;"><?= $msg ?></p>
    <?php endif; ?>

    <?php if ($act === 'generate' && !empty($exercises)): ?>
        <section class="exercise-grid">
            <?php foreach ($exercises as $e): ?>
                <div class="exercise-card">
                    <h4><?= htmlspecialchars($e['name']) ?></h4>
                    <p><?= htmlspecialchars($e['description'] ?? '-') ?></p>
                    <?php if (!empty($e['link'])): ?>
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
    <?php endif; ?>
</body>

</html>