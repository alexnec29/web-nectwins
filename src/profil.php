<?php
session_start();
require 'db.php';

if (!isset($_SESSION["username"], $_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION["user_id"];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userProfile = $stmt->fetch();

$profilIncomplet = empty($userProfile['nume']) || empty($userProfile['varsta']) || empty($userProfile['gen']);

$conditions = $pdo->query("SELECT id, name FROM health_condition ORDER BY name")->fetchAll();

$userConditions = $pdo->prepare("SELECT condition_id FROM user_health_condition WHERE user_id = ?");
$userConditions->execute([$userId]);
$userConditionIds = array_column($userConditions->fetchAll(), 'condition_id');
?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profilul meu | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/profil.css">
</head>

<body>

    <nav>
        <h1>Profilul lui <?= htmlspecialchars($_SESSION["username"]) ?></h1>
        <?php if (!$profilIncomplet): ?>
            <div class="nav-user">
                <div class="dropdown">
                    <button class="dropbtn">
                        <img src="/assets/user.png" alt="Profil" style="height: 24px; vertical-align: middle;">
                        <span>Profil ▼</span>
                    </button>
                    <div class="dropdown-content">
                        <a href="/principal.php">Înapoi</a>
                        <a href="/logout.php">Logout</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </nav>

    <div>
        <h2>Date personale</h2>
        <form method="POST" action="salveaza_profil.php">
            <label for="nume">Nume:</label>
            <input type="text" name="nume" value="<?= htmlspecialchars($userProfile['nume'] ?? '') ?>" required pattern="[A-Za-zĂăÂâÎîȘșȚțăâîșț\s\-]+" title="Numele poate conține doar litere și spații">

            <label for="varsta">Vârstă:</label>
            <input type="number" name="varsta" value="<?= $userProfile['varsta'] ?? '' ?>" required>

            <label for="gen">Gen:</label>
            <select name="gen" required>
                <option <?= ($userProfile['gen'] ?? '') === 'Masculin' ? 'selected' : '' ?>>Masculin</option>
                <option <?= ($userProfile['gen'] ?? '') === 'Feminin' ? 'selected' : '' ?>>Feminin</option>
                <option <?= ($userProfile['gen'] ?? '') === 'Altul' ? 'selected' : '' ?>>Altul</option>
            </select>

            <label for="inaltime">Înălțime (cm):</label>
            <input type="number" name="inaltime" value="<?= $userProfile['inaltime'] ?? '' ?>" required>

            <label for="greutate">Greutate (kg):</label>
            <input type="number" name="greutate" value="<?= $userProfile['greutate'] ?? '' ?>" required>

            <label>Condiții medicale:</label>
            <div class="checkbox-group">
                <?php
                foreach ($conditions as $cond) {
                    $isChecked = in_array($cond['id'], $userConditionIds) ? 'checked' : '';
                    $name = htmlspecialchars($cond['name']);
                    echo "<label><input type='checkbox' name='conditii_sanatate[]' value='{$cond['id']}' $isChecked> {$name}</label><br>";
                }
                ?>
            </div>

            <button type="submit">Salvează modificările</button>
        </form>

        <div class="profile-action-btns-wrapper">
            <a href="change-password.php" class="profile-action-btn">
                <button type="button">Schimbă parola</button>
            </a>
            <a href="delete-account.php" class="profile-action-btn">
                <button type="button">Șterge contul</button>
            </a>
        </div>
    </div>

</body>

</html>