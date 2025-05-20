<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Generare Antrenament | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/generate.css">
</head>
<?php
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
$selectedLocation = $_POST['location'] ?? '';
?>

<body>
    <nav>
        <h1>Generează antrenament</h1>
        <a href="principal-gym.php">Înapoi</a>
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
            <option value="10"> 30</option>
            <option value="20"> 60</option>
            <option value="60"> 90</option>
            <option value="120">120</option>
            <option value="150">150</option>
            <option value="Rich Piana">😈Rich Piana😈</option>
        </select>

        <label for="nivel">Nivel:</label>
        <select id="nivel" name="nivel">
            <option value="incepator">Începator</option>
            <option value="intermediar">Intermediar</option>
            <option value="avansat">Avansat</option>
            <option value="tren twin">💪Tren Twins🧨</option>
        </select>

        <label for="location">Locație:</label>
        <select id="location" name="location">
            <option value="outdoor">Aer liber</option>
            <option value="home">Acasă</option>
        </select>

        <button type="submit">Generează</button>
    </form>

    <div id="result"></div>

    <script>
        document.getElementById('generateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const muscleGroup = document.getElementById('muscleGroup').value;
            const duration = document.getElementById('duration').value;
            const location = document.getElementById('location').value;
            document.getElementById('result').innerHTML = `<p>Rutina generată pentru ${muscleGroup} de ${duration} minute, la ${location}.</p>`;
        });
    </script>
</body>

</html>