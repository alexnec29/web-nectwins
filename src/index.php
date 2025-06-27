<?php
session_start();

if (isset($_SESSION['username'])) {
  header('Location: principal.php');
  exit();
}

header('Location: login.php');
exit();
