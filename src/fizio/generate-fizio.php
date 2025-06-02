<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pdo = new PDO("pgsql:host=db;port=5432;dbname=wow_db", 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$trainingLevels = $pdo->query("SELECT id,name FROM training_level ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$locations      = $pdo->query("SELECT id,name FROM location ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$splits         = $pdo->query("SELECT id,name FROM split_type ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$groups         = $pdo->query("SELECT name FROM muscle_group ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

$slugify = fn($name) => strtolower(preg_replace('/[^a-z]+/i', '-', $name));
$slug2id = [];
foreach ($splits as $s) {
    $slug2id[$slugify($s['name'])] = $s['id'];
}

$g = fn($n) => current(array_filter($groups, fn($x) => strtolower($x) === strtolower($n))) ?? $n;

$fizioOptions = [
    "recuperare"  => [
        "genunchi" => [$g('Picioare')],
        "umar"     => [$g('Umeri')],
        "spate"    => [$g('Spate')]
    ],
    "mobilitate"  => [
        "general" => [$g('Piept'), $g('Spate'), $g('Umeri'), $g('Brațe'), $g('Picioare')],
        "membre"  => [$g('Brațe'), $g('Picioare')]
    ],
    "intarire"    => [
        "trunchi" => [$g('Piept'), $g('Spate'), $g('Umeri')],
        "postura" => [$g('Spate'), $g('Umeri'), $g('Piept')]
    ]
];

$act             = $_POST['action'] ?? '';
$selectedProgram = $_POST['tipProgram'] ?? 'recuperare';
$selectedZone    = $_POST['zonaVizata'] ?? '';
$selectedDuration = (int)($_POST['duration'] ?? 60);
$selectedNivel   = ctype_digit($_POST['nivel'] ?? '') ? (int)$_POST['nivel'] : null;
$selectedLocation = ctype_digit($_POST['location'] ?? '') ? (int)$_POST['location'] : null;

$msg        = '';
$exercises  = [];

function getFilteredExercises(PDO $pdo, array $groups, ?int $levelId, int $duration): array
{
    if (empty($groups)) {
        return [];
    }

    $stmt = $pdo->prepare("SELECT * FROM get_exercises_filtered(:groups, :level_id, :duration, :type_id)");
    $stmt->execute([
        'groups'   => '{' . implode(',', array_map(fn($g) => '"' . $g . '"', $groups)) . '}',
        'level_id' => $levelId,
        'duration' => $duration,
        'type_id'  => 3 // tipul de antrenament - fizio
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($act === 'generate' && isset($fizioOptions[$selectedProgram][$selectedZone])) {
    $exercises = getFilteredExercises(
        $pdo,
        $fizioOptions[$selectedProgram][$selectedZone],
        $selectedNivel,
        $selectedDuration
    );
}

if ($act === 'save') {
    $splitId = $slug2id[$selectedProgram] ?? null;

    if (!$splitId || !$selectedLocation) {
        $msg = '❌ Split sau locație invalidă. Split: '
            . htmlspecialchars($selectedProgram)
            . ' → ' . ($splitId ?? 'null');
    } elseif (!isset($_POST['exerciseIds']) || !is_array($_POST['exerciseIds']) || count($_POST['exerciseIds']) === 0) {
        $msg = '❌ Nu există exerciții de salvat.';
    } else {
        try {
            $exerciseIdsArray = array_map('intval', $_POST['exerciseIds']);
            $exerciseArray    = '{' . implode(',', $exerciseIdsArray) . '}';

            $sql = "CALL save_generated_workout(
                :name, :duration, :type_id, :level_id, :split_id, :location_id, :user_id, :exercise_ids, :section
            )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'name'         => 'Custom ' . date('d.m H:i'),
                'duration'     => $selectedDuration,
                'type_id'      => 3,
                'level_id'     => $selectedNivel,
                'split_id'     => $splitId,
                'location_id'  => $selectedLocation,
                'user_id'      => $_SESSION['user_id'],
                'exercise_ids' => $exerciseArray,
                'section'      => 'fizio'
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
    <title>Generare Program | FizioFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/generate.css">
</head>

<body>
    <nav>
        <h1>Generează program fizioterapie</h1>
        <a class="buton-inapoi" href="principal-fizio.php">Înapoi</a>
    </nav>

    <!-- Formularul principal de alegere parametri -->
    <form method="POST">
        <label>Program:</label>
        <select name="tipProgram" onchange="this.form.submit()">
            <?php foreach ($fizioOptions as $progKey => $zones): ?>
                <option value="<?= htmlspecialchars($progKey) ?>"
                    <?= $progKey === $selectedProgram ? 'selected' : '' ?>>
                    <?= ucfirst($progKey) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Zonă vizată:</label>
        <select name="zonaVizata">
            <?php foreach ($fizioOptions[$selectedProgram] as $zoneKey => $zoneMuscles): ?>
                <option value="<?= htmlspecialchars($zoneKey) ?>"
                    <?= $zoneKey === $selectedZone ? 'selected' : '' ?>>
                    <?= ucfirst($zoneKey) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Durată (min):</label>
        <select name="duration">
            <?php foreach ([30, 60, 90, 120, 150] as $d): ?>
                <option value="<?= $d ?>" <?= $d === $selectedDuration ? 'selected' : '' ?>><?= $d ?></option>
            <?php endforeach; ?>
        </select>

        <label>Nivel:</label>
        <select name="nivel">
            <option value="">--</option>
            <?php foreach ($trainingLevels as $l): ?>
                <option value="<?= $l['id'] ?>" <?= $l['id'] === $selectedNivel ? 'selected' : '' ?>>
                    <?= htmlspecialchars($l['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Locație:</label>
        <select name="location" required>
            <option value="">--</option>
            <?php foreach ($locations as $l): ?>
                <option value="<?= $l['id'] ?>" <?= $l['id'] === $selectedLocation ? 'selected' : '' ?>>
                    <?= ucfirst($l['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button name="action" value="generate">Generează</button>
    </form>

    <?php if ($msg): ?>
        <p style="margin:1rem 0;"><?= $msg ?></p>
    <?php endif; ?>

    <!-- Afișarea exercițiilor generate și formularul de salvare -->
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

        <!-- Formularul de Save, cu toate ID-urile exercițiilor ascunse -->
        <form method="POST">
            <!-- Transmitem în continuare toți parametrii originali din $_POST -->
            <?php foreach ($_POST as $k => $v): ?>
                <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
            <?php endforeach; ?>

            <!-- Aici adăugăm câte un hidden input pentru fiecare exercițiu -->
            <?php foreach ($exercises as $e): ?>
                <input type="hidden" name="exerciseIds[]" value="<?= $e['id'] ?>">
            <?php endforeach; ?>

            <button name="action" value="save">💾 Salvează</button>
        </form>
    <?php endif; ?>
</body>

</html>