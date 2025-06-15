<?php
session_start();
require './../db.php';

if (!isset($_SESSION["user_id"], $_SESSION["role"]) || $_SESSION["role"] != 3) {
    header("Location: login.php");
    exit();
}

$currentUserId = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["user_id"], $_POST["action"])) {
    $targetUserId = (int)$_POST["user_id"];
    $action = $_POST["action"];

    if ($targetUserId !== $currentUserId) {
        if ($action === "promote") {
            $stmt = $pdo->prepare("UPDATE users SET rol = 2 WHERE id = ?");
            $stmt->execute([$targetUserId]);
        } elseif ($action === "demote") {
            $stmt = $pdo->prepare("UPDATE users SET rol = 1 WHERE id = ?");
            $stmt->execute([$targetUserId]);
        }
    }

    header("Location: superadmin.php");
    exit();
}

$stmt = $pdo->prepare("SELECT id, username, email, rol FROM users WHERE id != ?");
$stmt->execute([$currentUserId]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <title>Administrare Admini | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/superadmin.css">
</head>

<body>

    <nav>
        <h1>👑 Administrare Admini</h1>
        <a href="./../principal.php" class="back-btn">Înapoi</a>
    </nav>

    <div class="admin-panel">
        <h2>Utilizatori</h2>

        <?php foreach ($users as $user): ?>
            <div class="user-row">
                <span><?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['email']) ?>)</span>
                <span>Rol: <?= $user['rol'] == 2 ? 'Admin' : 'User' ?></span>

                <?php if ($user['rol'] == 1): ?>
                    <form method="POST">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <input type="hidden" name="action" value="promote">
                        <button class="promote-btn">Promovează</button>
                    </form>
                <?php elseif ($user['rol'] == 2): ?>
                    <form method="POST">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <input type="hidden" name="action" value="demote">
                        <button class="demote-btn">Retrogradează</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

</body>

</html>