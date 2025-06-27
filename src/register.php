<?php
session_start();
require_once 'db.php';

if (isset($_SESSION["username"])) {
    header("Location: index.php");
    exit();
}

$success = null;
$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);
        $confirm_password = trim($_POST["confirm_password"]);
        $email = trim($_POST["email"]);

        if (empty($username) || empty($password) || empty($email)) {
            $error = "Please fill in all fields.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->execute(['username' => $username, 'email' => $email]);

            if ($stmt->rowCount() > 0) {
                $error = "Username or email already exists.";
            } elseif ($password !== $confirm_password) {
                $error = "Passwords do not match.";
            } elseif (strlen($password) < 8) {
                $error = "Password must be at least 8 characters long.";
            } elseif (!preg_match("/[A-Z]/", $password)) {
                $error = "Password must contain at least one uppercase letter.";
            } elseif (!preg_match("/[a-z]/", $password)) {
                $error = "Password must contain at least one lowercase letter.";
            } elseif (!preg_match("/[0-9]/", $password)) {
                $error = "Password must contain at least one number.";
            } else {
                $hashed = hash("sha256", $password);
                $insert = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (:username, :password, :email)");
                if ($insert->execute(['username' => $username, 'password' => $hashed, 'email' => $email])) {
                    $success = "Registration successful! You can now log in.";
                } else {
                    $error = "Something went wrong. Try again.";
                }
            }
        }
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/register.css">
</head>

<body>
    <div class="register-card">
        <h2>Register</h2>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required><br>
            <input type="email" name="email" placeholder="Email" required><br>
            <input type="submit" value="Register">
        </form>

        <p>Already have an account? <a href="login.php">Log in here</a>.</p>
    </div>
</body>

</html>