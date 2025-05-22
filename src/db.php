<?php
$host = 'db';
$port = '5432';
$dbname = 'wow_db';
$user = 'root';
$pass = 'root';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("❌ Conexiune eșuată: " . $e->getMessage());
}
?>