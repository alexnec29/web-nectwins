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
    $pdo->prepare("DELETE FROM user_health_condition WHERE condition_id = :id")->execute([':id' => $delete_id]);
    $pdo->prepare("DELETE FROM exercise_health_condition WHERE condition_id = :id")->execute([':id' => $delete_id]);
    $pdo->prepare("DELETE FROM health_condition WHERE id = :id")->execute([':id' => $delete_id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    $name = trim($_POST['name']);
    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT INTO health_condition (name) VALUES (:name)");
        $stmt->execute([':name' => $name]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

$conditions = $pdo->query("SELECT id, name FROM health_condition ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <title>Condiții Medicale | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/add.css">
</head>

<body>
    <nav>
        <h1>Gestionare Condiții Medicale</h1>
        <a class="buton-inapoi" href="admin.php">Înapoi</a>
    </nav>

    <form method="post">
        <label for="name">Adaugă o nouă condiție medicală:</label>
        <input type="text" id="name" name="name" required>
        <button type="submit">Salvează</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Denumire</th>
                <th>Acțiuni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($conditions as $cond): ?>
                <tr>
                    <td><?= htmlspecialchars($cond['id']) ?></td>
                    <td><?= htmlspecialchars($cond['name']) ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Ești sigur că vrei să ștergi această condiție medicală?');">
                            <input type="hidden" name="delete_id" value="<?= $cond['id'] ?>">
                            <button type="submit" class="delete-button">Șterge</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>