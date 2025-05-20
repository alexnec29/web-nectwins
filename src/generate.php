<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Generare Antrenament | FitFlow</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/generate.css"> 
</head>
<body>
    <nav>
        <h1>Generează antrenament</h1>
        <a href="principal.php">Înapoi</a>
    </nav>

    <form id="generateForm">
        <label for="muscleGroup">Grupă mușchi:</label>
        <select id="muscleGroup" name="muscleGroup">
            <option value="arms">Brațe</option>
            <option value="legs">Picioare</option>
            <option value="abs">Abdomen</option>
            <option value="back">Spate</option>
        </select>

        <label for="duration">Durată (minute):</label>
        <select id="duration" name="duration">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="30">30</option>
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