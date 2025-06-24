<?php
session_start();

if (!isset($_SESSION["username"]) || !isset($_SESSION["role"]) || $_SESSION["role"] < 2) {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <title>Panou Admin | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/admin.css">
</head>

<body>

    <nav>
        <h1>Panou Administrativ</h1>
        <a href="./../principal.php" class="nav-link">Înapoi</a>
    </nav>

    <main class="admin-dashboard">
        <button onclick="location.href='admin_exercitii.php'">Manageriază exercițiile</button>
        <button onclick="location.href='admin_conditii.php'">Manageriază condițiile medicale</button>
        <button onclick="location.href='admin_splituri.php'">Manageriază split-urile</button>
        <button onclick="location.href='admin_locatii.php'">Manageriază locațiile</button>
    </main>

</body>

</html>