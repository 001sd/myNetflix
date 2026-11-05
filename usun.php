<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$base_dir = "users_cloud/" . $_SESSION['username'];
$item = basename($_GET['item'] ?? '');
$type = $_GET['type'] ?? '';
$subdir = basename($_GET['subdir'] ?? '');

if ($item === '') {
    header("Location: cloud.php"); // Zmienione z home.php
    exit;
}

// Określenie ścieżki docelowej
$target_path = $base_dir;
if ($subdir !== '' && is_dir($base_dir . '/' . $subdir)) {
    $target_path .= '/' . $subdir;
}
$target_path .= '/' . $item;

// Zabezpieczenie przed usuwaniem poza katalogiem użytkownika
if (strpos(realpath($target_path), realpath($base_dir)) !== 0) {
    header("Location: cloud.php"); // Zmienione z home.php
    exit;
}

if (file_exists($target_path)) {
    if ($type === 'file' && !is_dir($target_path)) {
        unlink($target_path); // Usunięcie pliku
    } elseif ($type === 'dir' && is_dir($target_path)) {
        // Zgodnie z instrukcją, usuwamy zawartość przed usunięciem folderu
        $files = array_diff(scandir($target_path), array('.', '..'));
        foreach ($files as $file) {
            unlink($target_path . '/' . $file); 
        }
        rmdir($target_path); // Usunięcie pustego folderu
    }
}

$redirect = "cloud.php" . ($subdir !== '' ? "?dir=" . $subdir : ""); // Zmienione z home.php
header("Location: " . $redirect);
exit;