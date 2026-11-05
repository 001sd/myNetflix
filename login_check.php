<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
require_once 'db.php';

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header('Location: login.php');
    exit;
}

// Odbieranie danych z ukrytych pól formularza
$screen_res = mysqli_real_escape_string($link, $_POST['screen_res'] ?? '-');
$window_res = mysqli_real_escape_string($link, $_POST['window_res'] ?? '-');
$colors = (int)($_POST['colors'] ?? 0);
$cookies = (int)($_POST['cookies'] ?? 0);
$java = (int)($_POST['java'] ?? 0);
$language = mysqli_real_escape_string($link, $_POST['language'] ?? '-');

$stmt = mysqli_prepare(
    $link,
    "SELECT id, username, password, avatar
     FROM users
     WHERE username = ?"
);

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Przygotowanie danych do logów wizyt
$ip = $_SERVER['REMOTE_ADDR'];
$data = date('Y-m-d H:i:s');

if ($user && $user['password'] === $password) {
    session_regenerate_id(true);

    $_SESSION['loggedin'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['avatar'] = $user['avatar'] ?? 'default-avatar.svg';
    
    // Zapisanie ścieżki do katalogu MyCloud w sesji (Zadanie 5)
    $_SESSION['user_dir'] = "users_cloud/" . $user['username'];
    // NOWOŚĆ: Nadanie uprawnień admina dla menu w Zadaniu 4
    if ($user['username'] === 'sd' || $user['username'] === 'admin') {
        $_SESSION['is_admin'] = 1;
    } else {
        $_SESSION['is_admin'] = 0;
    }

    // Rejestracja SUKCESU w bazie z nowymi zmiennymi ekranu
    $sql_log = "INSERT INTO goscieportalu (ipaddress, username, datetime, status, browser, screen_res, window_res, colors, cookies, java, language) 
                VALUES ('$ip', '$username', '$data', 'Sukces', 'Logowanie', '$screen_res', '$window_res', $colors, $cookies, $java, '$language')";
    mysqli_query($link, $sql_log);

    mysqli_stmt_close($stmt);
    mysqli_close($link);

    header('Location: home.php');
    exit;
}

// Rejestracja BŁĘDU w bazie z nowymi zmiennymi ekranu
$sql_log_err = "INSERT INTO goscieportalu (ipaddress, username, datetime, status, browser, screen_res, window_res, colors, cookies, java, language) 
                VALUES ('$ip', '$username', '$data', 'Błąd', 'Logowanie', '$screen_res', '$window_res', $colors, $cookies, $java, '$language')";
mysqli_query($link, $sql_log_err);

mysqli_stmt_close($stmt);
mysqli_close($link);

header('Location: login.php?error=1');
exit;