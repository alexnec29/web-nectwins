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
        <a href="principal.php" class="nav-link">Înapoi</a>
    </nav>

    <main class="admin-dashboard">
        <button onclick="location.href='admin_exercitii.php'">Manageriază exercițiile</button>
        <button onclick="location.href='admin_conditii.php'">Manageriază condițiile medicale</button>
        <button onclick="location.href='admin_grupe.php'">Manageriază grupele musculare</button>
        <button onclick="location.href='admin_subgrupe.php'">Manageriază subgrupele musculare</button>
        <button onclick="location.href='admin_niveluri.php'">Manageriază nivelurile</button>
        <button onclick="location.href='admin_tipuri.php'">Manageriază tipurile de antrenament</button>
        <button onclick="location.href='admin_splituri.php'">Manageriază split-urile</button>
        <button onclick="location.href='admin_subsplituri.php'">Manageriază sub-spliturile</button>
        <button onclick="location.href='admin_locatii.php'">Manageriază locațiile</button>
        <button onclick="location.href='admin_section_split.php'">Manageriază split-urile pe secțiune</button>
    </main>

</body>
</html>