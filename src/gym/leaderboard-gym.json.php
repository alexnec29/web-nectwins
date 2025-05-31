<?php
header("Content-Type: application/json");

$pdo = new PDO("pgsql:host=db;port=5432;dbname=wow_db", 'root', 'root', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$sql = "
    SELECT u.username, u.nume, u.varsta, u.conditie,
           COUNT(*) AS sesiuni,
           COALESCE(SUM(EXTRACT(EPOCH FROM uw.completed_at - uw.started_at)/60), 0) AS durata
    FROM users u
    JOIN user_workout uw ON uw.user_id = u.id
    WHERE uw.completed = TRUE
    GROUP BY u.id
    ORDER BY sesiuni DESC
";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as &$u) {
    $u['durata'] = (int) round($u['durata']);
    $u['grupa_varsta'] = ($u['varsta'] < 26) ? '18–25' : '26–35';
}

echo json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);