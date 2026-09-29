<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$saved_lang = $_SESSION['lang'] ?? 'en';
session_unset();
session_destroy();

session_start();
$_SESSION['lang'] = $saved_lang;

header("Location: /Gym Manager/index.php");
exit();
?>
