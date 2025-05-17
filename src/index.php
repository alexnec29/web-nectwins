<?php
session_start();

// Dacă utilizatorul este logat (are username în sesiune) → principal.php
if (isset($_SESSION['username'])) {
  header('Location: gym/principal-gym.php');
  exit();
}

// Dacă nu e logat → login.php
header('Location: login.php');
exit();
