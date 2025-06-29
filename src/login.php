<?php
session_start();
require_once 'db.php';

$error = null;

if (isset($_SESSION["username"])) {
  header("Location: index.php");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["username"]) && isset($_POST["password"])) {
  try {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $hashed = hash("sha256", $password);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND password = :password");
    $stmt->execute(['username' => $username, 'password' => $hashed]);

    if ($stmt->rowCount() === 1) {
      $user = $stmt->fetch();
      $_SESSION["username"] = $user["username"];
      $_SESSION["user_id"] = $user["id"];
      $_SESSION["role"] = $user["rol"];

      if (empty($user["nume"]) || empty($user["varsta"]) || empty($user["gen"])) {
        header("Location: profil.php");
      } else {
        header("Location: index.php");
      }
      exit();
    } else {
      $error = "Username sau parolă incorectă!";
    }
  } catch (PDOException $e) {
    $error = "Eroare de conexiune: " . $e->getMessage();
  }
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/login.css">
</head>

<body>
  <div class="login-card">
    <h2>Login</h2>
    <?php if (isset($error)): ?>
      <div class="error-message">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>
    <form method="POST">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <input type="submit" value="Login">
    </form>
    <p>Don't have an account? <a href="register.php">Register here</a>.</p>
  </div>
</body>

</html>