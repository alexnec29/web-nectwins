<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

require './../db.php';

$sections = ['gym', 'kineto', 'fizio'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $pdo->prepare("DELETE FROM location WHERE id = :id")->execute([':id' => $delete_id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && !isset($_POST['edit_id'])) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $editId = (int)$_POST['edit_id'];
    $selectedSections = $_POST['sections'] ?? [];

    $pdo->prepare("DELETE FROM location_section WHERE location_id = :id")->execute([':id' => $editId]);

    $stmtInsert = $pdo->prepare("INSERT INTO location_section (location_id, section) VALUES (:loc_id, :section)");
    foreach ($selectedSections as $section) {
        $stmtInsert->execute([
            ':loc_id' => $editId,
            ':section' => $section
        ]);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$locations = $pdo->query("
    SELECT l.id, l.name,
           COALESCE(STRING_AGG('[' || ls.section || ']', ''), '') AS sections
    FROM location l
    LEFT JOIN location_section ls ON l.id = ls.location_id
    GROUP BY l.id, l.name
    ORDER BY l.name
")->fetchAll(PDO::FETCH_ASSOC);
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
                <th>Secțiuni</th>
                <th>Acțiuni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($locations as $loc): ?>
                <tr>
                    <td><?= htmlspecialchars($loc['id']) ?></td>
                    <td><?= htmlspecialchars($loc['name']) ?></td>
                    <td>
                        <div class="section-edit">
                            <form method="post">
                                <input type="hidden" name="edit_id" value="<?= $loc['id'] ?>">
                                <?php foreach ($sections as $sec): ?>
                                    <label style="margin-right: 10px;">
                                        <input type="checkbox" name="sections[]" value="<?= $sec ?>"
                                            <?= (strpos($loc['sections'], "[$sec]") !== false) ? 'checked' : '' ?>>
                                        <?= ucfirst($sec) ?>
                                    </label>
                                <?php endforeach; ?>
                                <button type="submit" class="save-button">Salvează</button>
                            </form>
                        </div>
                    </td>
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