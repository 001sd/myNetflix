
<?php
session_start();
require_once 'db.php';

if (
    isset($_SESSION['loggedin']) &&
    $_SESSION['loggedin'] === true
) {
    header('Location: home.php');
    exit;
}

header('Location: login.php');
exit;