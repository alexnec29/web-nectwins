<?php
session_start();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $pdo->prepare("DELETE FROM location WHERE id = :id")->execute([':id' => $delete_id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    $name = trim($_POST['name']);
    $selectedSections = $_POST['sections'] ?? [];

    if ($name !== '' && !empty($selectedSections)) {
        $stmt = $pdo->prepare("INSERT INTO location (name) VALUES (:name) RETURNING id");
        $stmt->execute([':name' => $name]);
        $locationId = $stmt->fetchColumn();

        $stmtSection = $pdo->prepare("INSERT INTO location_section (location_id, section) VALUES (:loc_id, :section)");
        foreach ($selectedSections as $section) {
            $stmtSection->execute([
                ':loc_id' => $locationId,
                ':section' => $section
            ]);
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

$locations = $pdo->query("
    SELECT l.id, l.name, 
           STRING_AGG(ls.section, ', ' ORDER BY ls.section) as sections
    FROM location l
    LEFT JOIN location_section ls ON l.id = ls.location_id
    GROUP BY l.id, l.name
    ORDER BY l.name
")->fetchAll(PDO::FETCH_ASSOC);
$sections = ['gym', 'kineto', 'fizio'];
?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <title>Locații | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/add.css">
</head>

<body>
    <nav>
        <h1>Gestionare Locații</h1>
        <a class="buton-inapoi" href="admin.php">Înapoi</a>
    </nav>

    <form method="post">
        <label for="name">Nume locație:</label>
        <input type="text" id="name" name="name" required>

        <label>Secțiuni:</label>
        <?php foreach ($sections as $sec): ?>
            <label>
                <input type="checkbox" name="sections[]" value="<?= $sec ?>">
                <?= ucfirst($sec) ?>
            </label>
        <?php endforeach; ?>

        <button type="submit">Salvează</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nume</th>
                <th>Secțiune</th>
                <th>Acțiuni</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($locations as $loc): ?>
            <tr>
                <td><?= htmlspecialchars($loc['id']) ?></td>
                <td><?= htmlspecialchars($loc['name']) ?></td>
                <td><?= htmlspecialchars($loc['sections'] ?? '') ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('Ești sigur că vrei să ștergi această locație?');">
                        <input type="hidden" name="delete_id" value="<?= $loc['id'] ?>">
                        <button type="submit" class="delete-button">Șterge</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>