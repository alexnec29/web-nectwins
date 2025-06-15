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
    $pdo->prepare("DELETE FROM exercise_location WHERE location_id = :id")->execute([':id' => $delete_id]);
    $pdo->prepare("DELETE FROM workout WHERE location_id = :id")->execute([':id' => $delete_id]);
    $pdo->prepare("DELETE FROM location WHERE id = :id")->execute([':id' => $delete_id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    $name = trim($_POST['name']);
    $section = trim($_POST['section']);
    if ($name !== '' && $section !== '') {
        $stmt = $pdo->prepare("INSERT INTO location (name, section) VALUES (:name, :section)");
        $stmt->execute([':name' => $name, ':section' => $section]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

$locations = $pdo->query("SELECT id, name, section FROM location ORDER BY section, name")->fetchAll(PDO::FETCH_ASSOC);
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

        <label for="section">Secțiune:</label>
        <select id="section" name="section" required>
            <option value="">Alege secțiunea</option>
            <?php foreach ($sections as $sec): ?>
                <option value="<?= $sec ?>"><?= ucfirst($sec) ?></option>
            <?php endforeach; ?>
        </select>

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
                    <td><?= htmlspecialchars(ucfirst($loc['section'])) ?></td>
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