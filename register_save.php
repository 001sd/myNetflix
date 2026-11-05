<?php
session_start();
require_once 'db.php';

// Odbieranie danych z naszego nowego formularza
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// Sprawdzenie, czy pola nie są puste
if (empty($username) || empty($password)) {
    header('Location: register.php?error=1');
    exit;
}

// Zabezpieczenie po stronie serwera (PHP sprawdza to niezależnie od JavaScriptu)
if ($password !== $password_confirm) {
    header('Location: register.php?error=mismatch');
    exit;
}

// Sprawdzenie, czy użytkownik o takim loginie już przypadkiem nie istnieje
$stmt = mysqli_prepare($link, "SELECT id FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    // Użytkownik istnieje - zwracamy błąd
    mysqli_stmt_close($stmt);
    header('Location: register.php?error=userexists');
    exit;
}
mysqli_stmt_close($stmt);

// Przygotowanie domyślnych danych konta (zgodnie ze strukturą Twojej bazy)
$avatar = 'default-avatar.svg';
$worker = 0;
$is_admin = 0;

// Rejestracja nowego użytkownika w bazie danych
$stmt_insert = mysqli_prepare($link, "INSERT INTO users (username, password, avatar, worker, is_admin) VALUES (?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt_insert, "sssii", $username, $password, $avatar, $worker, $is_admin);

if (mysqli_stmt_execute($stmt_insert)) {
    mysqli_stmt_close($stmt_insert);
    
    // Sukces! Przekierowujemy do logowania
    header('Location: login.php');
    exit;
} else {
    mysqli_stmt_close($stmt_insert);
    // Błąd bazy danych
    header('Location: register.php?error=1');
    exit;
}