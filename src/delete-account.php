<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $userId = $_SESSION['user_id'];
        $current = $_POST['current_password'] ?? '';

        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $hash = $stmt->fetchColumn();

        if (hash('sha256', $current) !== $hash) {
            $error = 'Parola curentă nu este corectă.';
        } else {
            $del = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $del->execute(['id' => $userId]);
            session_unset();
            session_destroy();
            header('Location: login.php');
            exit();
        }
    } catch (PDOException $e) {
        $error = 'Eroare de conexiune: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <title>Stergere Cont | FitFlow</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <div class="login-card">
        <h2>Stergere Cont</h2>
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <p class="confirm-delete">Confirmă ștergerea contului introducând parola curentă. Această acțiune este ireversibilă!</p>
        <form method="POST">
            <input type="password" name="current_password" placeholder="Parola curentă" required><br>
            <input type="submit" value="Șterge contul" onclick="return confirm('Ești sigur că vrei să-ți ștergi contul?');">
            <p><a href="index.php">Înapoi</a></p>
        </form>

    </div>
</body>

</html>