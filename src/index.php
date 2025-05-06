<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Login | FitFlow</title>
    <link rel="stylesheet" href="css/styles.css">
  </head>
  <body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
    <p>You are logged in.</p>
    <a href="logout.php">Logout</a>
  </body>
</html>