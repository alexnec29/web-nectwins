<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pdo = new PDO(
    "pgsql:host=db;port=5432;dbname=wow_db",
    'root',
    'root',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

/* === Dropdowns (aceleași tabele ca la gym) === */
$trainingLevels = $pdo->query("SELECT id,name FROM training_level ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$locations      = $pdo->query("SELECT id,name FROM location ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$splits         = $pdo->query("SELECT id,name FROM split_type ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$groups         = $pdo->query("SELECT name FROM muscle_group ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

/* === Mapping split_name_slug → id (folosim doar pentru a păstra consistența,
       chiar dacă la kineto nu vom folosi dropdown-ul split de la tabelă) === */
$slugify = fn($name) => strtolower(preg_replace('/[^a-z]+/i', '-', $name));
$slug2id = [];
foreach ($splits as $s) {
    $slug2id[$slugify($s['name'])] = $s['id'];
}

/* === Helper pentru grupă exactă (case-insensitive) === */
$g = fn($n) => current(array_filter($groups, fn($x) => strtolower($x) == strtolower($n))) ?? $n;

/* === Definiție opțiuni Kinetoterapie (hardcodate) === */
$kinetoOptions = [
    "recuperare" => [
        "genunchi" => [$g('Picioare')],                  // Considerăm grupă “Picioare” pentru exerciții de genunchi
        "umar"     => [$g('Umeri')],                     // “Umeri” pentru exerciții de umăr
        "spate"    => [$g('Spate')]                      // “Spate” pentru coloana vertebrală
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

/* === Inputuri === */
$act              = $_POST['action']      ?? '';
$selectedProgram  = $_POST['tipProgram']  ?? 'recuperare';
$selectedZone     = $_POST['zonaVizata']  ?? '';
$selectedDuration = (int)($_POST['duration'] ?? 60);
$selectedNivel    = ctype_digit($_POST['nivel'] ?? '') ? (int)$_POST['nivel'] : null;
$selectedLocation = ctype_digit($_POST['location'] ?? '') ? (int)$_POST['location'] : null;

$msg = '';
$exercises = []; // vom popula fie la generate, fie și la save

/* === Funcție: extrage exerciții după grupă (folosim aceeași procedură ca la gym) === */
function getExercises(PDO $pdo, array $muscles): array
{
    if (empty($muscles)) {
        return [];
    }
    $sql = "SELECT * FROM get_exercises_by_groups(:groups)";
    $stmt = $pdo->prepare($sql);
    // Construim array-ul Postgres cu stringurile de grupă
    $escaped = array_map(fn($x) => '"' . $x . '"', $muscles);
    $stmt->execute(['groups' => '{' . implode(',', $escaped) . '}']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* === Dacă s-a apăsat “Generează” sau “Salvează”, trebuie să recalculăm exercițiile === */
if ($act === 'generate' || $act === 'save') {
    // Verificăm că parametrii necesari sunt validați înainte de a apela getExercises
    if (
        isset($kinetoOptions[$selectedProgram])
        && $selectedZone !== ''
        && isset($kinetoOptions[$selectedProgram][$selectedZone])
    ) {
        $muscleGroups = $kinetoOptions[$selectedProgram][$selectedZone];
        $exercises = getExercises($pdo, $muscleGroups);
    }
}

/* === Generate === */
if ($act === 'generate') {
    // Verificăm că toate câmpurile necesare sunt corecte
    if (
        !isset($kinetoOptions[$selectedProgram]) ||
        $selectedZone === '' ||
        !isset($kinetoOptions[$selectedProgram][$selectedZone]) ||
        !$selectedNivel ||
        !$selectedLocation
    ) {
        $msg = '❌ Te rog completează toate câmpurile corect.';
    } else {
        if (empty($exercises)) {
            $msg = '❌ Nicio potrivire la exerciții pentru selecția dată.';
        }
        // Dacă $exercises nu e gol, vom afișa lista mai jos
    }
}

/* === Save === */
if ($act === 'save') {
    // Pentru a salva, trebuie din nou să avem exercițiile (le-am recalculat mai sus)
    if (
        !isset($kinetoOptions[$selectedProgram]) ||
        $selectedZone === '' ||
        !isset($kinetoOptions[$selectedProgram][$selectedZone]) ||
        empty($exercises) ||
        !$selectedNivel ||
        !$selectedLocation
    ) {
        $msg = '❌ Pentru salvare, toate câmpurile și exercițiile trebuie să fie valide.';
    } else {
        // Determinăm split_id (folosim slug2id pentru consistență cu gym)
        $splitSlug = $selectedProgram;
        $splitId = $slug2id[$splitSlug] ?? null;
        if (!$splitId) {
            $msg = '❌ Split invalid pentru salvare.';
        } else {
            try {
                $exerciseIds   = array_column($exercises, 'id');
                $exerciseArray = '{' . implode(',', $exerciseIds) . '}';

                $sql = "CALL save_generated_workout(
                    :name,
                    :duration,
                    :type_id,
                    :level_id,
                    :split_id,
                    :location_id,
                    :user_id,
                    :exercise_ids,
                    :section
                )";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'name'         => 'Kineto ' . ucfirst($selectedProgram) . ' ' . date('d.m H:i'),
                    'duration'     => $selectedDuration,
                    'type_id'      => 2,               // Presupunem type_id = 2 pentru kineto
                    'level_id'     => $selectedNivel,
                    'split_id'     => $splitId,
                    'location_id'  => $selectedLocation,
                    'user_id'      => $_SESSION['user_id'],
                    'exercise_ids' => $exerciseArray,
                    'section'      => 'kineto'
                ]);
                $msg = '✅ Salvat! Vezi în lista de programe.';
                // După salvare, poți șterge $exercises pentru a nu mai afișa
                $exercises = [];
            } catch (Throwable $e) {
                $msg = '❌ Eroare la salvare: ' . $e->getMessage();
            }
        }
    }
}
?>

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
        <!-- 1) Tip program -->
        <label>Program:</label>
        <select name="tipProgram" onchange="this.form.submit()">
            <?php foreach ($kinetoOptions as $progKey => $zones): ?>
                <option value="<?= htmlspecialchars($progKey) ?>"
                    <?= $progKey === $selectedProgram ? 'selected' : '' ?>>
                    <?= ucfirst($progKey) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- 2) Zonă vizată (se compilează pe server la fiecare POST) -->
        <label>Zonă vizată:</label>
        <select name="zonaVizata">
            <?php foreach ($kinetoOptions[$selectedProgram] as $zoneKey => $zoneMuscles): ?>
                <option value="<?= htmlspecialchars($zoneKey) ?>"
                    <?= $zoneKey === $selectedZone ? 'selected' : '' ?>>
                    <?= ucfirst($zoneKey) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- 3) Durată -->
        <label>Durată (min):</label>
        <select name="duration">
            <?php foreach ([30, 60, 90, 120, 150] as $d): ?>
                <option value="<?= $d ?>" <?= $d === $selectedDuration ? 'selected' : '' ?>><?= $d ?></option>
            <?php endforeach; ?>
        </select>

        <!-- 4) Nivel (din tabela training_level) -->
        <label>Nivel:</label>
        <select name="nivel">
            <option value="">--</option>
            <?php foreach ($trainingLevels as $l): ?>
                <option value="<?= $l['id'] ?>" <?= $l['id'] === $selectedNivel ? 'selected' : '' ?>>
                    <?= htmlspecialchars($l['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- 5) Locație (din tabela location) -->
        <label>Locație:</label>
        <select name="location" required>
            <option value="">--</option>
            <?php foreach ($locations as $l): ?>
                <option value="<?= $l['id'] ?>" <?= $l['id'] === $selectedLocation ? 'selected' : '' ?>>
                    <?= ucfirst($l['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Butonul de “Generează” -->
        <button name="action" value="generate">Generează</button>
    </form>

    <!-- Mesaj de eroare / succes pentru generare sau salvare -->
    <?php if ($msg): ?>
        <p style="margin: 1rem 0;"><?= $msg ?></p>
    <?php endif; ?>

    <!-- Dacă am apăsat “Generează” și avem exerciții, le afișăm -->
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

        <!-- Formularul de salvare, cu toate câmpurile actuale în hidden inputs -->
        <form method="POST">
            <?php foreach ($_POST as $k => $v): ?>
                <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
            <?php endforeach; ?>
            <!-- Butonul “Salvează” -->
            <button name="action" value="save">💾 Salvează</button>
        </form>
    <?php endif; ?>
</body>

</html>