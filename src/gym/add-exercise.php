<?php
session_start();

if (isset($_GET['ajax']) && $_GET['ajax'] === 'subgroups') {
    $pdo = new PDO(
        "pgsql:host=db;port=5432;dbname=wow_db",
        "root",
        "root",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $group_id = (int)($_GET['group_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id, name FROM muscle_subgroup WHERE principal_group = :gid ORDER BY name");
    $stmt->execute([':gid' => $group_id]);
    $subgroups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($subgroups);
    exit;
}

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$pdo = new PDO(
    "pgsql:host=db;port=5432;dbname=wow_db",
    "root",
    "root",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$levels = $pdo->query("SELECT id, name FROM training_level ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$groups = $pdo->query("SELECT id, name FROM muscle_group ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$locations = $pdo->query("SELECT id, name FROM location WHERE TRIM(section) = 'gym' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']);
    $description = trim($_POST['description']);
    $link        = trim($_POST['link']);
    $bodyweight  = isset($_POST['is_bodyweight']);
    $equipment   = isset($_POST['equipment_needed']);
    $difficulty  = (int)$_POST['difficulty'];
    $type_id     = 1;
    $location_id = (int)$_POST['location'];

    $stmt = $pdo->prepare("
        INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link, location_id)
        VALUES (:name, :description, :diff, :type, :bw, :eq, :link, :loc)
    ");
    $stmt->execute([
        ':name'        => $name,
        ':description' => $description,
        ':diff'        => $difficulty,
        ':type'        => $type_id,
        ':bw'          => $bodyweight,
        ':eq'          => $equipment,
        ':link'        => $link,
        ':loc'         => $location_id,
    ]);
    $exercise_id = $pdo->lastInsertId();

    if (!empty($_POST['subgroup'])) {
        $sub_id = (int)$_POST['subgroup'];
        $stmt2 = $pdo->prepare("
            INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id)
            VALUES (:ex, :sub)
        ");
        $stmt2->execute([
            ':ex'  => $exercise_id,
            ':sub' => $sub_id,
        ]);
    }

    header("Location: workouts-gym.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <title>Adaugă Exercițiu | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/add.css">
</head>

<body>
    <nav>
        <h1>Adaugă Exercițiu</h1>
        <a class="buton-inapoi" href="principal-gym.php">Înapoi</a>
    </nav>

    <form method="post">
        <label for="name">Nume exercițiu:</label>
        <input type="text" id="name" name="name" required>

        <label for="description">Descriere:</label>
        <textarea id="description" name="description" rows="4"></textarea>

        <label for="link">Link video:</label>
        <input type="url" id="link" name="link">

        <label for="difficulty">Dificultate:</label>
        <select id="difficulty" name="difficulty" required>
            <option value="">Alege nivel</option>
            <?php foreach ($levels as $lvl): ?>
                <option value="<?= $lvl['id'] ?>"><?= htmlspecialchars($lvl['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Caracteristici:</label>
        <div class="checkbox-group">
            <label><input type="checkbox" name="is_bodyweight"> Bodyweight</label>
            <label><input type="checkbox" name="equipment_needed"> Necesită echipament</label>
        </div>

        <label for="group">Grupă musculară:</label>
        <select id="group" name="group" required>
            <option value="">Alege grupă</option>
            <?php foreach ($groups as $grp): ?>
                <option value="<?= $grp['id'] ?>"><?= htmlspecialchars($grp['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="subgroup">Subgrupă musculară:</label>
        <select id="subgroup" name="subgroup" required>
            <option value="">Alege întâi grupa</option>
        </select>

        <label for="location">Locație:</label>
        <select id="location" name="location" required>
            <option value="">Alege locație</option>
            <?php foreach ($locations as $loc): ?>
                <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Salvează Exercițiu</button>
    </form>

    <script>
        document.getElementById('group').addEventListener('change', function() {
            const groupId = this.value;
            const subSelect = document.getElementById('subgroup');
            subSelect.innerHTML = '<option>Se încarcă…</option>';

            fetch('?ajax=subgroups&group_id=' + encodeURIComponent(groupId))
                .then(r => r.json())
                .then(data => {
                    subSelect.innerHTML = '<option value="">Alege subgrupă</option>';
                    data.forEach(sub => {
                        const option = document.createElement('option');
                        option.value = sub.id;
                        option.textContent = sub.name;
                        subSelect.appendChild(option);
                    });
                })
                .catch(() => {
                    subSelect.innerHTML = '<option>Eroare la încărcare</option>';
                });
        });
    </script>
</body>

</html>