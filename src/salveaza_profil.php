<?php
session_start();
require 'db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION["user_id"];

$nume     = $_POST["nume"];
$varsta   = $_POST["varsta"];
$gen      = $_POST["gen"];
$inaltime = $_POST["inaltime"];
$greutate = $_POST["greutate"];
$conditii = $_POST["conditii_sanatate"] ?? [];

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("
        UPDATE users 
        SET nume = ?, varsta = ?, gen = ?, inaltime = ?, greutate = ? 
        WHERE id = ?
    ");
    $stmt->execute([$nume, $varsta, $gen, $inaltime, $greutate, $userId]);

    $pdo->prepare("DELETE FROM user_health_condition WHERE user_id = ?")->execute([$userId]);

    $stmtCond = $pdo->prepare("INSERT INTO user_health_condition(user_id, condition_id) VALUES (?, ?)");
    foreach ($conditii as $condId) {
        $stmtCond->execute([$userId, $condId]);
    }

    $pdo->commit();

    header("Location: principal.php");
    exit();
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Eroare la salvare: " . $e->getMessage();
}
