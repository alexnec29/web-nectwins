<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Generare Program | KinetoFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/generate.css">
</head>
<?php
$kinetoOptions = [
    "recuperare" => [
        "genunchi" => "Genunchi",
        "umar" => "Umăr",
        "spate" => "Coloană vertebrală"
    ],
    "mobilitate" => [
        "general" => "Mobilitate generală",
        "membre" => "Membre superioare/inferioare"
    ],
    "intarire" => [
        "trunchi" => "Trunchi",
        "postura" => "Postură"
    ]
];

$selectedProgram = $_POST['tipProgram'] ?? 'recuperare';
$selectedZone = $_POST['zonaVizata'] ?? '';
$selectedDuration = $_POST['duration'] ?? '';
$selectedLocation = $_POST['location'] ?? '';
?>

<body>
    <nav>
        <h1>Generează program kinetoterapie</h1>
        <a class="buton-inapoi" href="principal-kineto.php">Înapoi</a>
    </nav>

    <form id="generateForm" method="POST">
        <label for="tipProgram">Tip program:</label>
        <select id="tipProgram" name="tipProgram" onchange="this.form.submit()">
            <option value="recuperare" <?= $selectedProgram == 'recuperare' ? 'selected' : '' ?>>Recuperare</option>
            <option value="mobilitate" <?= $selectedProgram == 'mobilitate' ? 'selected' : '' ?>>Mobilitate</option>
            <option value="intarire" <?= $selectedProgram == 'intarire' ? 'selected' : '' ?>>Întărire</option>
        </select>

        <label for="zonaVizata">Zonă vizată:</label>
        <select id="zonaVizata" name="zonaVizata">
            <?php foreach ($kinetoOptions[$selectedProgram] as $value => $label): ?>
                <option value="<?= htmlspecialchars($value) ?>" <?= $selectedZone == $value ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="duration">Durată (minute):</label>
        <select id="duration" name="duration">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="30">30</option>
            <option value="40">40</option>
            <option value="60">60</option>
        </select>

        <label for="nivel">Nivel:</label>
        <select id="nivel" name="nivel">
            <option value="incepator">Începator</option>
            <option value="intermediar">Intermediar</option>
            <option value="avansat">Avansat</option>
        </select>

        <label for="location">Locație:</label>
        <select id="location" name="location">
            <option value="outdoor">Aer liber</option>
            <option value="home">Acasă</option>
            <option value="clinic">Clinic</option>
        </select>

        <button type="submit">Generează</button>
    </form>

    <div id="result"></div>

    <script>
        document.getElementById('generateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const zona = document.getElementById('zonaVizata').value;
            const duration = document.getElementById('duration').value;
            const location = document.getElementById('location').value;
            document.getElementById('result').innerHTML = `<p>Program de kinetoterapie pentru zona ${zona} cu durata de ${duration} minute, la ${location}.</p>`;
        });
    </script>
</body>

</html>