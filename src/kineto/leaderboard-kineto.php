<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: ../login.php");
    exit();
}

// Mock: total sesiuni + nivel + vârstă + tip + durată totală
$users = [
    ["nume" => "Elena Radu", "sesiuni" => 19, "nivel" => "Avansat", "varsta" => "26–35", "tip" => "Kinetoterapie", "durata" => 720],
    ["nume" => "Radu Ionescu", "sesiuni" => 17, "nivel" => "Intermediar", "varsta" => "18–25", "tip" => "Kinetoterapie", "durata" => 680],
    ["nume" => "Ana Pop", "sesiuni" => 15, "nivel" => "Începator", "varsta" => "36–45", "tip" => "Kinetoterapie", "durata" => 600],
    ["nume" => "Mihai Dragomir", "sesiuni" => 13, "nivel" => "Avansat", "varsta" => "26–35", "tip" => "Kinetoterapie", "durata" => 590],
];
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Clasamente Kinetoterapie | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/leaderboard.css">
</head>
<body>

<nav>
    <h1>Clasamente Kinetoterapie</h1>
    <a href="principal-kineto.php">Înapoi</a>
</nav>

<div class="leaderboard-container">
    <h2>Top General (după număr sesiuni)</h2>
    <table>
        <thead>
            <tr><th>Loc</th><th>Nume</th><th>Sesiuni</th></tr>
        </thead>
        <tbody>
            <?php
            usort($users, fn($a, $b) => $b['sesiuni'] - $a['sesiuni']);
            foreach ($users as $i => $u): ?>
                <tr><td>#<?= $i + 1 ?></td><td><?= $u['nume'] ?></td><td><?= $u['sesiuni'] ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Top pe Nivel</h2>
    <?php
    $niveluri = array_unique(array_column($users, 'nivel'));
    foreach ($niveluri as $nivel): ?>
        <h3><?= $nivel ?></h3>
        <ul>
            <?php foreach ($users as $u):
                if ($u['nivel'] === $nivel): ?>
                    <li><?= $u['nume'] ?> – <?= $u['sesiuni'] ?> sesiuni</li>
                <?php endif;
            endforeach; ?>
        </ul>
    <?php endforeach; ?>

    <h2>Top pe Clasă de Vârstă</h2>
    <?php
    $varste = array_unique(array_column($users, 'varsta'));
    foreach ($varste as $grupa): ?>
        <h3>Vârstă <?= $grupa ?></h3>
        <ul>
            <?php foreach ($users as $u):
                if ($u['varsta'] === $grupa): ?>
                    <li><?= $u['nume'] ?> – <?= $u['sesiuni'] ?> sesiuni</li>
                <?php endif;
            endforeach; ?>
        </ul>
    <?php endforeach; ?>

    <h2>Top după Durată Totală (minute)</h2>
    <table>
        <thead>
            <tr><th>Loc</th><th>Nume</th><th>Durată totală</th></tr>
        </thead>
        <tbody>
            <?php
            usort($users, fn($a, $b) => $b['durata'] - $a['durata']);
            foreach ($users as $i => $u): ?>
                <tr><td>#<?= $i + 1 ?></td><td><?= $u['nume'] ?></td><td><?= $u['durata'] ?> min</td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="export-links">
        <a href="leaderboard-kineto.json">Export JSON</a>
        <a href="leaderboard-kineto.pdf">Export PDF</a>
    </div>
</div>

</body>
</html>
