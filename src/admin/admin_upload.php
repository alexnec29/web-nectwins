<?php
session_start();
if (!isset($_SESSION["username"]) || !isset($_SESSION["role"]) || $_SESSION["role"] < 2) {
    header("Location: ../login.php");
    exit();
}

$allowedTables = [
    'muscle_group', 'muscle_subgroup',
    'training_type', 'training_level',
    'split_type', 'section_split', 'split_subtype', 'split_subtype_muscle_group',
    'location', 'location_section',
    'exercise', 'exercise_location', 'exercise_muscle_group', 'exercise_section',
    'health_condition', 'exercise_health_condition',
    'training_goal', 'workout', 'workout_exercise', 'workout_session'
];

$status = $_GET['status'] ?? null;
$message = $_GET['message'] ?? null;
if ($message !== null) {
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Importă date | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body>
<nav>
    <h1>Importă date CSV/JSON</h1>
    <a href="admin.php" class="nav-link">Înapoi la Panou</a>
</nav>

<main class="admin-dashboard">
    <?php if ($status && $message): ?>
        <div class="alert <?= $status === 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form action="import_handler.php" method="post" enctype="multipart/form-data">
        <label for="target_table">Alege tabelă:</label>
        <select name="target_table" id="target_table" required>
            <option value="" disabled selected>-- Selectează tabelă --</option>
            <?php foreach ($allowedTables as $table): ?>
                <option value="<?= htmlspecialchars($table) ?>"><?= htmlspecialchars($table) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="data_file">Încarcă fișier CSV sau JSON:</label>
        <input type="file" name="data_file" id="data_file" accept=".csv,.json" required>

        <button type="submit">Importă</button>
    </form>
</main>
</body>
</html>