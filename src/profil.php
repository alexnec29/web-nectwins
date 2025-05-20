<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

// Mock Profile, dar în realitate e fetch din DB
$userProfile = [
    'nume' => 'Andrei Popescu',
    'varsta' => 25,
    'gen' => 'Masculin',
    'inaltime' => 180,
    'greutate' => 75,
    'conditie' => 'Niciuna'
];
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Profilul meu | FitFlow</title>
    <link rel="stylesheet" href="/css/profil.css">
</head>
<body>

    <nav>
        <h1>Profilul lui <?php echo htmlspecialchars($_SESSION["username"]); ?></h1>
        <div class="nav-user">
            <div class="dropdown">
                <button class="dropbtn">
                    <img src="/assets/user.png" alt="Profil" style="height: 24px; vertical-align: middle;">
                    <span>Profil ▼</span>
                </button>
                <div class="dropdown-content">
                    <a href="/gym/principal-gym.php">Înapoi</a>
                    <a href="/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div>
        <h2>Date personale</h2>
        <form method="POST" action="salveaza_profil.php">
            <label for="nume">Nume:</label>
            <input type="text" name="nume" value="<?= $userProfile['nume'] ?>" required>

            <label for="varsta">Vârstă:</label>
            <input type="number" name="varsta" value="<?= $userProfile['varsta'] ?>" required>

            <label for="gen">Gen:</label>
            <select name="gen" required>
                <option <?= $userProfile['gen'] == 'Masculin' ? 'selected' : '' ?>>Masculin</option>
                <option <?= $userProfile['gen'] == 'Feminin' ? 'selected' : '' ?>>Feminin</option>
                <option <?= $userProfile['gen'] == 'Altul' ? 'selected' : '' ?>>Altul</option>
            </select>

            <label for="inaltime">Înălțime (cm):</label>
            <input type="number" name="inaltime" value="<?= $userProfile['inaltime'] ?>" required>

            <label for="greutate">Greutate (kg):</label>
            <input type="number" name="greutate" value="<?= $userProfile['greutate'] ?>" required>

            <label for="conditie">Condiții medicale:</label>
            <input type="text" name="conditie" value="<?= $userProfile['conditie'] ?>">

            <button type="submit">Salvează modificările</button>
        </form>
    </div>

</body>
</html>