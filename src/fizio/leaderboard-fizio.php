<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: ../login.php");
    exit();
}

// Mock: total sesiuni + nivel + vârstă + tip + durată totală
$users = [
    ["nume" => "Cristina Vasilescu", "sesiuni" => 21, "nivel" => "Avansat", "varsta" => "36–45", "tip" => "Fizioterapie", "durata" => 960],
    ["nume" => "Iulian Gheorghe", "sesiuni" => 18, "nivel" => "Mediu", "varsta" => "26–35", "tip" => "Fizioterapie", "durata" => 750],
    ["nume" => "Adina Popescu", "sesiuni" => 16, "nivel" => "Începător", "varsta" => "18–25", "tip" => "Fizioterapie", "durata" => 690],
    ["nume" => "Tudor Ene", "sesiuni" => 14, "nivel" => "Mediu", "varsta" => "26–35", "tip" => "Fizioterapie", "durata" => 630],
];
?>
<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <title>Clasamente Fizioterapie | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/leaderboard.css">
</head>

<body>

    <nav>
        <h1>Clasamente Fizioterapie</h1>
        <a class="buton-inapoi" href="principal-fizio.php">Înapoi</a>
    </nav>

    <div class="leaderboard-container">
        <h2>Top General (după număr sesiuni)</h2>
        <table>
            <thead>
                <tr>
                    <th>Loc</th>
                    <th>Nume</th>
                    <th>Sesiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php
                usort($users, fn($a, $b) => $b['sesiuni'] - $a['sesiuni']);
                foreach ($users as $i => $u): ?>
                    <tr>
                        <td>#<?= $i + 1 ?></td>
                        <td><?= $u['nume'] ?></td>
                        <td><?= $u['sesiuni'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Top pe Nivel</h2>
        <?php
        $niveluri = array_unique(array_column($users, 'nivel'));
        foreach ($niveluri as $nivel): ?>
            <h3><?= ucfirst($nivel) ?></h3>
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
                <tr>
                    <th>Loc</th>
                    <th>Nume</th>
                    <th>Durată totală</th>
                </tr>
            </thead>
            <tbody>
                <?php
                usort($users, fn($a, $b) => $b['durata'] - $a['durata']);
                foreach ($users as $i => $u): ?>
                    <tr>
                        <td>#<?= $i + 1 ?></td>
                        <td><?= $u['nume'] ?></td>
                        <td><?= $u['durata'] ?> min</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="export-links">
            <a href="leaderboard-fizio.json">Export JSON</a>
            <a href="leaderboard-fizio.pdf">Export PDF</a>
        </div>
    </div>

</body>

</html>