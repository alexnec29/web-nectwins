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
        <label for="tipProgram">Tip program:</label>
        <select id="tipProgram" name="tipProgram" onchange="this.form.submit()">
            <option value="recuperare post-operatorie" <?= $selectedProgram == 'recuperare post-operatorie' ? 'selected' : '' ?>>Recuperare post-operatorie</option>
            <option value="reeducare neuromusculara" <?= $selectedProgram == 'reeducare neuromusculara' ? 'selected' : '' ?>>Reeducare neuromusculară</option>
            <option value="dureri cronice" <?= $selectedProgram == 'dureri cronice' ? 'selected' : '' ?>>Dureri cronice</option>
        </select>

        <label for="zonaVizata">Zonă vizată:</label>
        <select id="zonaVizata" name="zonaVizata">
            <?php foreach ($fizioOptions[$selectedProgram] as $value => $label): ?>
                <option value="<?= htmlspecialchars($value) ?>" <?= $selectedZone == $value ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="duration">Durată (minute):</label>
        <select id="duration" name="duration">
            <option value="15">15</option>
            <option value="30">30</option>
            <option value="45">45</option>
            <option value="60">60</option>
        </select>

        <label for="nivel">Nivel:</label>
        <select id="nivel" name="nivel">
            <option value="incepator">Începător</option>
            <option value="mediu">Mediu</option>
            <option value="avansat">Avansat</option>
        </select>

        <label for="location">Locație:</label>
        <select id="location" name="location">
            <option value="home">Acasă</option>
            <option value="clinica">Clinică</option>
            <option value="spital">Spital</option>
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
            document.getElementById('result').innerHTML = `<p>Program de fizioterapie pentru zona ${zona}, durata ${duration} minute, locație: ${location}.</p>`;
        });
    </script>
</body>

</html>