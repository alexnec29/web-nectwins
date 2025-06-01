<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$pdo = new PDO("pgsql:host=db;port=5432;dbname=wow_db", 'root', 'root', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// ───── Colectăm datele pentru leaderboard ─────
$rows = $pdo->query("SELECT * FROM get_leaderboard_data('kineto')")->fetchAll(PDO::FETCH_ASSOC);

// Grupăm pe nivel și pe grupe de vârstă
$by_level = [];
$by_age = [];
foreach ($rows as &$r) {
    $r['durata'] = round($r['durata']);
    $r['grupa_varsta'] = match (true) {
        $r['varsta'] < 18      => "<18",
        $r['varsta'] <= 25     => "18–25",
        $r['varsta'] <= 35     => "26–35",
        $r['varsta'] <= 50     => "36–50",
        default                => ">50"
    };
    $by_level[$r['nivel'] ?? 'Necunoscut'][] = $r;
    $by_age[$r['grupa_varsta']][] = $r;
}
unset($r);
?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <title>Clasamente Kineto | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/leaderboard.css">
</head>

<body>

    <nav>
        <h1>Clasamente Bodybuilding</h1>
        <a class="buton-inapoi" href="principal-kineto.php">Înapoi</a>
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
                <?php foreach ($rows as $i => $u): ?>
                    <tr>
                        <td>#<?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($u['nume']) ?></td>
                        <td><?= $u['sesiuni'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Top pe Nivel</h2>
        <?php foreach ($by_level as $nivel => $users): ?>
            <h3><?= htmlspecialchars($nivel) ?></h3>
            <ul>
                <?php foreach ($users as $u): ?>
                    <li><?= htmlspecialchars($u['nume']) ?> – <?= $u['sesiuni'] ?> sesiuni</li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>

        <h2>Top pe Clasă de Vârstă</h2>
        <?php foreach ($by_age as $grupa => $users): ?>
            <h3>Vârstă <?= $grupa ?></h3>
            <ul>
                <?php foreach ($users as $u): ?>
                    <li><?= htmlspecialchars($u['nume']) ?> – <?= $u['sesiuni'] ?> sesiuni</li>
                <?php endforeach; ?>
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
                usort($rows, fn($a, $b) => $b['durata'] <=> $a['durata']);
                foreach ($rows as $i => $u): ?>
                    <tr>
                        <td>#<?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($u['nume']) ?></td>
                        <td><?= $u['durata'] ?> min</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="export-links">
            <a href="leaderboard-kineto.json.php" target="_blank">Export JSON</a>
            <a href="leaderboard-kineto.pdf.php" target="_blank">Export PDF</a>
        </div>
    </div>

</body>

</html>