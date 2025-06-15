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
    $pdo->prepare("DELETE FROM exercise WHERE dificulty = :id")->execute([':id' => $delete_id]);
    $pdo->prepare("DELETE FROM workout WHERE level_id = :id")->execute([':id' => $delete_id]);
    $pdo->prepare("DELETE FROM training_level WHERE id = :id")->execute([':id' => $delete_id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    $name = trim($_POST['name']);
    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT INTO training_level (name) VALUES (:name)");
        $stmt->execute([':name' => $name]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

$levels = $pdo->query("SELECT id, name FROM training_level ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <title>Niveluri de Antrenament | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/add.css">
</head>

<body>
    <nav>
        <h1>Gestionare Niveluri de Antrenament</h1>
        <a class="buton-inapoi" href="principal.php">Înapoi</a>
    </nav>

    <form method="post">
        <label for="name">Adaugă un nou nivel de antrenament:</label>
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
            <?php foreach ($levels as $level): ?>
                <tr>
                    <td><?= htmlspecialchars($level['id']) ?></td>
                    <td><?= htmlspecialchars($level['name']) ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Ești sigur că vrei să ștergi acest nivel de antrenament?');">
                            <input type="hidden" name="delete_id" value="<?= $level['id'] ?>">
                            <button type="submit" class="delete-button">Șterge</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>