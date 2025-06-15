<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dsn = "pgsql:host=db;port=5432;dbname=wow_db";
        $pdo = new PDO($dsn, "root", "root", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $userId = $_SESSION['user_id'];
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $hash = $stmt->fetchColumn();

        if (hash('sha256', $current) !== $hash) {
            $error = 'Parola curentă nu este corectă.';
        } elseif ($new !== $confirm) {
            $error = 'Parola nouă și confirmarea nu coincid.';
        } elseif (strlen($new) < 8) {
            $error = 'Parola nouă trebuie să aibă cel puțin 8 caractere.';
        } elseif (!preg_match('/[A-Z]/', $new) || !preg_match('/[a-z]/', $new) || !preg_match('/[0-9]/', $new)) {
            $error = 'Parola trebuie să conțină literă mare, literă mică și cifră.';
        } else {
            $newHash = hash('sha256', $new);
            $upd = $pdo->prepare('UPDATE users SET password = :pass WHERE id = :id');
            $upd->execute(['pass' => $newHash, 'id' => $userId]);
            $success = 'Parola a fost schimbată cu succes.';
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
    <title>Schimbare Parolă | FitFlow</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <div class="login-card">
        <h2>Schimbare Parolă</h2>
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="password" name="current_password" placeholder="Parola curentă" required><br>
            <input type="password" name="new_password" placeholder="Parola nouă" required><br>
            <input type="password" name="confirm_password" placeholder="Confirmă parola nouă" required><br>
            <input type="submit" value="Schimbă parola">
        </form>
        <p><a href="index.php">Înapoi</a></p>
    </div>
</body>

</html>