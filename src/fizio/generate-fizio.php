<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Generare Program | FizioFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/generate.css">
</head>
<?php
$fizioOptions = [
    "recuperare post-operatorie" => [
        "genunchi" => "Recuperare genunchi",
        "sold" => "Recuperare șold",
        "umar" => "Recuperare umăr"
    ],
    "reeducare neuromusculara" => [
        "membre-superioare" => "Membre superioare",
        "membre-inferioare" => "Membre inferioare"
    ],
    "dureri cronice" => [
        "lombar" => "Durere lombară",
        "cervical" => "Durere cervicală",
        "genunchi-cronic" => "Genunchi cronic"
    ]
];

$selectedProgram = $_POST['tipProgram'] ?? 'recuperare post-operatorie';
$selectedZone = $_POST['zonaVizata'] ?? '';
$selectedDuration = $_POST['duration'] ?? '';
$selectedLocation = $_POST['location'] ?? '';
?>

<body>
    <nav>
        <h1>Generează program fizioterapie</h1>
        <a class="buton-inapoi" href="principal-fizio.php">Înapoi</a>
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
            <option value="incepator" <?= $selectedNivel == 'incepator' ? 'selected' : '' ?>>Începător</option>
            <option value="intermediar" <?= $selectedNivel == 'intermediar' ? 'selected' : '' ?>>Intermediar</option>
            <option value="avansat" <?= $selectedNivel == 'avansat' ? 'selected' : '' ?>>Avansat</option>
            <option value="tren twin" <?= $selectedNivel == 'tren twin' ? 'selected' : '' ?>>💪Tren Twins🧨</option>
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