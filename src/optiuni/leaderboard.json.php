<?php
session_start();
require './../db.php';

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Not authenticated"]);
    exit;
}

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
        $r['varsta'] <= 25     => "18-25",
        $r['varsta'] <= 35     => "26-35",
        $r['varsta'] <= 50     => "36-50",
        default                => ">50"
    };
    $by_age[$grupa][] = $r;
}

header('Content-Type: application/json; charset=utf-8');
header("Content-Disposition: attachment; filename=\"leaderboard-{$section}.json\"");

echo json_encode([
    'section' => $section,
    'total_users' => count($rows),
    'general' => $rows,
    'by_level' => $by_level,
    'by_age_group' => $by_age
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);