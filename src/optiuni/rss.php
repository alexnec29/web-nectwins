<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

session_start();

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo 'Not authenticated';
    exit;
}

$sectiuniValide = ['gym', 'kineto', 'fizio'];
$sectiune = strtolower($_GET['section'] ?? 'gym');
if (!in_array($sectiune, $sectiuniValide, true)) {
    $sectiune = 'gym';
}

require './../db.php';

$sql = "
    SELECT w.id AS wid,
        w.name,
        ws.completed_at
    FROM workout_session ws
    JOIN workout w ON w.id = ws.workout_id
    WHERE ws.user_id = :uid
    AND ws.completed_at IS NOT NULL
    AND LOWER(w.section) = :section
    ORDER BY ws.completed_at DESC
    LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'uid' => $userId,
    'section' => $sectiune
]);
$antrenamente = $stmt->fetchAll(PDO::FETCH_ASSOC);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<rss version="2.0">
<channel>
    <title>FitFlow – antrenamente <?= htmlspecialchars($sectiune) ?></title>
    <link><?= htmlspecialchars("$baseUrl/optiuni/statistics.php?section=$sectiune") ?></link>
    <description>Ultimele antrenamente finalizate în secțiunea <?= htmlspecialchars($sectiune) ?></description>
    <language>ro</language>

<?php if ($antrenamente): ?>
    <?php foreach ($antrenamente as $a): ?>
    <item>
        <title><?= htmlspecialchars($a['name']) ?></title>
        <link><?= htmlspecialchars("$baseUrl/optiuni/workout.php?section=$sectiune&wid=" . $a['wid']) ?></link>
        <guid isPermaLink="false"><?= 'fitflow-' . $a['wid'] ?></guid>
        <pubDate><?= gmdate(DATE_RSS, strtotime($a['completed_at'])) ?></pubDate>
        <description>Antrenament finalizat la <?= htmlspecialchars($a['completed_at']) ?></description>
    </item>
    <?php endforeach; ?>
<?php else: ?>
    <item>
        <title>Nicio intrare recentă</title>
        <description>Nu au fost găsite antrenamente finalizate pentru această secțiune.</description>
        <pubDate><?= gmdate(DATE_RSS) ?></pubDate>
        <guid isPermaLink="false"><?= 'fitflow-empty-' . time() ?></guid>
    </item>
<?php endif; ?>
</channel>
</rss>