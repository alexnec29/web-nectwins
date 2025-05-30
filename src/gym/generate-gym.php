<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Generare Antrenament | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/generate.css">
</head>

<?php
$host = 'db';
$port = '5432';
$dbname = 'wow_db';
$user = 'root';
$pass = 'root';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("❌ Conexiune eșuată: " . $e->getMessage());
}
$stmt = $pdo->query("SELECT id, name FROM training_level ORDER BY id");
$trainingLevels = $stmt->fetchAll(PDO::FETCH_ASSOC);

$muscleOptions = [
    "push-pull-legs" => [
        "push" => "Piept, Triceps, Umeri",
        "pull" => "Spate, Biceps",
        "legs" => "Picioare"
    ],
    "upper-lower" => [
        "upper" => "Partea superioară",
        "lower" => "Partea inferioară"
    ],
    "bro split" => [
        "chest" => "Piept",
        "back" => "Spate",
        "arms" => "Brațe",
        "legs" => "Picioare",
        "shoulders" => "Umeri"
    ],
    "arnold split" => [
        "chest-back" => "Piept & Spate",
        "shoulders-arms" => "Umeri & Brațe",
        "legs" => "Picioare"
    ]
];

$selectedSplit = $_POST['tipAntrenament'] ?? 'push-pull-legs';
$selectedMuscle = $_POST['muscleGroup'] ?? '';
$selectedDuration = $_POST['duration'] ?? '';
$selectedNivel = $_POST['nivel'] ?? '';
$selectedLocation = $_POST['location'] ?? '';
?>

<body>
    <nav>
        <h1>Generează antrenament</h1>
        <a class="buton-inapoi" href="principal-gym.php">Înapoi</a>
    </nav>

    <form id="generateForm" method="POST">
        <label for="tipAntrenament">Split antrenament:</label>
        <select id="tipAntrenament" name="tipAntrenament" onchange="this.form.submit()">
            <option value="push-pull-legs" <?= $selectedSplit == 'push-pull-legs' ? 'selected' : '' ?>>Push Pull Legs</option>
            <option value="upper-lower" <?= $selectedSplit == 'upper-lower' ? 'selected' : '' ?>>Upper Lower</option>
            <option value="bro split" <?= $selectedSplit == 'bro split' ? 'selected' : '' ?>>Bro Split</option>
            <option value="arnold split" <?= $selectedSplit == 'arnold split' ? 'selected' : '' ?>>Arnold Split</option>
        </select>

        <label for="muscleGroup">Grupă mușchi:</label>
        <select id="muscleGroup" name="muscleGroup">
            <?php foreach ($muscleOptions[$selectedSplit] as $value => $label): ?>
                <option value="<?= htmlspecialchars($value) ?>" <?= $selectedMuscle == $value ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="duration">Durată (minute):</label>
        <select id="duration" name="duration">
            <option value="30" <?= $selectedDuration == '30' ? 'selected' : '' ?>>30</option>
            <option value="60" <?= $selectedDuration == '60' ? 'selected' : '' ?>>60</option>
            <option value="90" <?= $selectedDuration == '90' ? 'selected' : '' ?>>90</option>
            <option value="120" <?= $selectedDuration == '120' ? 'selected' : '' ?>>120</option>
            <option value="150" <?= $selectedDuration == '150' ? 'selected' : '' ?>>150</option>
            <option value="Rich Piana" <?= $selectedDuration == 'Rich Piana' ? 'selected' : '' ?>>😈Rich Piana😈</option>
        </select>

        <label for="nivel">Nivel:</label>
        <select id="nivel" name="nivel">
            <option value="">-- Selectează nivel --</option>
            <?php foreach ($trainingLevels as $level): ?>
                <option value="<?= htmlspecialchars($level['id']) ?>" <?= ($selectedNivel == $level['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($level['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="location">Locație:</label>
        <select id="location" name="location">
            <option value="outdoor" <?= $selectedLocation == 'outdoor' ? 'selected' : '' ?>>Aer liber</option>
            <option value="home" <?= $selectedLocation == 'home' ? 'selected' : '' ?>>Acasă</option>
        </select>

        <button type="submit">Generează</button>
    </form>

    <?php if ($_SERVER["REQUEST_METHOD"] === "POST"): ?>
        <div id="result">
            <h2>Rezultat:</h2>
            <p>
                Rutina generată pentru <strong><?= htmlspecialchars($selectedMuscle) ?></strong>,
                timp de <strong><?= htmlspecialchars($selectedDuration) ?></strong> minute,
                nivel <strong><?= htmlspecialchars($selectedNivel) ?></strong>,
                la <strong><?= htmlspecialchars($selectedLocation) ?></strong>.
            </p>
        </div>
    <?php endif; ?>
</body>

</html>