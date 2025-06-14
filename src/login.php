<?php
session_start();

if (isset($_SESSION["username"])) {
  header("Location: index.php");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["username"]) && isset($_POST["password"])) {
  try {
    $dsn = "pgsql:host=db;port=5432;dbname=wow_db";
    $pdo = new PDO($dsn, "root", "root", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

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
    
      // Verificăm dacă userul și-a completat profilul
      if (empty($user["nume"]) || empty($user["varsta"]) || empty($user["gen"])) {
        header("Location: profil.php");
      } else {
        header("Location: index.php");
      }
      exit();
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
  <title>Login</title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/login.css">
</head>

<body>
  <div class="login-card">
    <h2>Login</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <input type="submit" value="Login">
    </form>
    <p>Don't have an account? <a href="register.php">Register here</a>.</p>
  </div>
</body>

</html>