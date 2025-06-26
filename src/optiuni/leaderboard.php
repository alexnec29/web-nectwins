<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require './../db.php';

$allowedSections = ['gym', 'kineto', 'fizio'];
$section = $_GET['section'] ?? 'gym';
if (!in_array($section, $allowedSections)) {
    $section = 'gym';
}

$stmt = $pdo->prepare("SELECT * FROM get_leaderboard_data(:section)");
$stmt->execute(['section' => $section]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$levelStmt = $pdo->prepare("SELECT * FROM get_leaderboard_by_level(:section)");
$levelStmt->execute(['section' => $section]);
$rows_level = $levelStmt->fetchAll(PDO::FETCH_ASSOC);

$by_level = [];
foreach ($rows_level as $r) {
    $nivel = $r['nivel'] ?? 'Necunoscut';
    $by_level[$nivel][] = $r;
}

$by_age = [];
foreach ($rows as $r) {
    $grupa = match (true) {
        $r['varsta'] < 18      => "<18",
        $r['varsta'] <= 25     => "18–25",
        $r['varsta'] <= 35     => "26–35",
        $r['varsta'] <= 50     => "36–50",
        default                => ">50"
    };
    $by_age[$grupa][] = $r;
}
?>
<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <title>Clasamente <?= ucfirst($section) ?> | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/leaderboard.css">
</head>

<body>

    <nav>
        <h1>Clasamente <?= ucfirst($section) ?></h1>
        <a class="buton-inapoi" href="../principal.php?section=<?= $section ?>">Înapoi</a>
    </nav>

    <div class="leaderboard-container">

        <h2>Top General (după număr sesiuni)</h2>
        <?php if (!empty($rows)): ?>
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
                    usort($rows, fn($a, $b) => $b['sesiuni'] <=> $a['sesiuni']);
                    foreach ($rows as $i => $u): ?>
                        <tr>
                            <td>#<?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($u['nume']) ?></td>
                            <td><?= $u['sesiuni'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="empty-state">Niciun utilizator nu are sesiuni înregistrate pe această secțiune.</p>
        <?php endif; ?>


        <h2>Top pe Nivel</h2>
        <?php if (!empty($by_level)): ?>
            <?php foreach ($by_level as $nivel => $users): ?>
                <h3><?= htmlspecialchars($nivel) ?></h3>
                <ul>
                    <?php foreach ($users as $u): ?>
                        <li>
                            <span><?= htmlspecialchars($u['nume']) ?></span>
                            <span class="session-badge"><?= $u['sesiuni'] ?> sesiuni</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty-state">Nu există date pentru clasificarea pe nivel.</p>
        <?php endif; ?>


        <h2>Top pe Clasă de Vârstă</h2>
        <?php if (!empty($by_age)): ?>
            <?php foreach ($by_age as $grupa => $users): ?>
                <h3>Vârstă <?= $grupa ?></h3>
                <ul>
                    <?php foreach ($users as $u): ?>
                        <li>
                            <span><?= htmlspecialchars($u['nume']) ?></span>
                            <span class="session-badge"><?= $u['sesiuni'] ?> sesiuni</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty-state">Nu există date pentru clasificarea pe vârstă.</p>
        <?php endif; ?>


        <h2>Top după Durată Totală (minute)</h2>
        <?php if (!empty($rows)): ?>
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
        <?php else: ?>
            <p class="empty-state">Nicio durată înregistrată pentru această secțiune.</p>
        <?php endif; ?>

        <div class="export-links">
            <a href="leaderboard.json.php?section=<?= $section ?>" target="_blank">Export JSON</a>
            <a href="leaderboard.pdf.php?section=<?= $section ?>" target="_blank">Export PDF</a>
        </div>
    </div>

</body>

</html>