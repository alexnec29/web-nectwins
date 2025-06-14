<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo "Neautentificat.";
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
// 🔴 Linia de mai jos NU trebuie
// use TCPDF;

$pdo = new PDO("pgsql:host=db;port=5432;dbname=wow_db", 'root', 'root', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

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

// HEADERE PDF
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=\"leaderboard-$section.pdf\"");

// === CREARE PDF ===
$pdf = new \TCPDF(); // ✅ Asta e forma corectă fără `use`
$pdf->SetCreator('FitFlow');
$pdf->SetAuthor('FitFlow');
$pdf->SetTitle("Leaderboard - $section");
$pdf->SetSubject("Export clasament $section");
$pdf->SetKeywords("FitFlow, leaderboard, $section, PDF");

$pdf->SetMargins(15, 20, 15);
$pdf->AddPage();

// Font compatibil UTF-8 + diacritice
$pdf->SetFont('dejavusans', 'B', 16);
$pdf->Cell(0, 10, 'Clasament FitFlow - ' . ucfirst($section), 0, 1, 'C');
$pdf->Ln(5);

// Top General
$pdf->SetFont('dejavusans', 'B', 12);
$pdf->Cell(0, 10, 'Top General (sesiuni):', 0, 1);
$pdf->SetFont('dejavusans', '', 10);

if (!empty($rows)) {
    foreach ($rows as $i => $r) {
        $line = sprintf("#%d: %s — %d sesiuni (%d min)", $i + 1, $r['nume'], $r['sesiuni'], $r['durata']);
        $pdf->Cell(0, 8, $line, 0, 1);
    }
} else {
    $pdf->Cell(0, 8, "Niciun utilizator.", 0, 1);
}

$pdf->Ln(6);

// Top pe Nivel
$pdf->SetFont('dejavusans', 'B', 12);
$pdf->Cell(0, 10, 'Top pe Nivel:', 0, 1);
$pdf->SetFont('dejavusans', '', 10);

if (!empty($by_level)) {
    foreach ($by_level as $nivel => $users) {
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(0, 8, $nivel, 0, 1);
        $pdf->SetFont('dejavusans', '', 10);
        foreach ($users as $u) {
            $pdf->Cell(0, 7, "• {$u['nume']} — {$u['sesiuni']} sesiuni", 0, 1);
        }
        $pdf->Ln(3);
    }
} else {
    $pdf->Cell(0, 8, "Fără date.", 0, 1);
}

$pdf->Ln(4);

// Top pe Clasă de Vârstă
$pdf->SetFont('dejavusans', 'B', 12);
$pdf->Cell(0, 10, 'Top pe Clasă de Vârstă:', 0, 1);
$pdf->SetFont('dejavusans', '', 10);

if (!empty($by_age)) {
    foreach ($by_age as $grupa => $users) {
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(0, 8, "Vârstă: $grupa", 0, 1);
        $pdf->SetFont('dejavusans', '', 10);
        foreach ($users as $u) {
            $pdf->Cell(0, 7, "• {$u['nume']} — {$u['sesiuni']} sesiuni", 0, 1);
        }
        $pdf->Ln(3);
    }
} else {
    $pdf->Cell(0, 8, "Fără date pentru vârstă.", 0, 1);
}

$pdf->Output("leaderboard-$section.pdf", 'I');