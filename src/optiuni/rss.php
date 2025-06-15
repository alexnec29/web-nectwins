<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

session_start();
if (!isset($_SESSION['user_id'])) {
    echo "Not authenticated";
    exit;
}

$pdo = new PDO("pgsql:host=db;port=5432;dbname=wow_db", 'root', 'root', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$userId = $_SESSION['user_id'];
$allowedSections = ['gym', 'kineto', 'fizio'];
$section = $_GET['section'] ?? 'gym';

if (!in_array($section, $allowedSections)) {
    $section = 'gym';
}

$stmt = $pdo->prepare("
    SELECT w.name, ws.completed_at
    FROM workout_session ws
    JOIN workout w ON w.id = ws.workout_id
    WHERE ws.user_id = :uid AND ws.completed_at IS NOT NULL AND w.section = :section
    ORDER BY ws.completed_at DESC
    LIMIT 10
");
$stmt->execute(['uid' => $userId, 'section' => $section]);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<rss version="2.0">
<channel>
    <title>FitFlow - Antrenamente <?= htmlspecialchars(ucfirst($section)) ?></title>
    <link>http://localhost:8080/rss.php?section=<?= htmlspecialchars($section) ?></link>
    <description>Ultimele antrenamente efectuate în secțiunea <?= htmlspecialchars($section) ?></description>
    <language>ro</language>

    <?php foreach ($entries as $entry): ?>
        <item>
            <title><?= htmlspecialchars($entry['name']) ?></title>
            <pubDate><?= date(DATE_RSS, strtotime($entry['completed_at'])) ?></pubDate>
            <description>Antrenament finalizat la <?= htmlspecialchars($entry['completed_at']) ?></description>
        </item>
    <?php endforeach; ?>
</channel>
</rss>