<?php
session_start();
require 'db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION["user_id"];

// Preluăm datele din form
$nume = $_POST["nume"];
$varsta = $_POST["varsta"];
$gen = $_POST["gen"];
$inaltime = $_POST["inaltime"];
$greutate = $_POST["greutate"];
$conditie = $_POST["conditie"];

$stmt = $pdo->prepare("UPDATE users SET nume = ?, varsta = ?, gen = ?, inaltime = ?, greutate = ?, conditie = ?
                       WHERE id = ?");
$stmt->execute([$nume, $varsta, $gen, $inaltime, $greutate, $conditie, $userId]);

// Redirect spre pagina principală după salvare
header("Location: /gym/principal-gym.php");
exit();