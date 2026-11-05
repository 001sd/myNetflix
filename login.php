<?php
session_start();

if (
    isset($_SESSION['loggedin']) &&
    $_SESSION['loggedin'] === true
) {
    header('Location: home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="/sd.png">
    <title>Logowanie</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <a href="../index.php" class="btn btn-outline-secondary mb-3">
        &larr; Wróć do katalogu zadań
    </a>
    
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 text-center mb-4">Logowanie</h1>

                    <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger">
                        Nieprawidłowy login lub hasło.
                    </div>
                    <?php endif; ?>

                    <form method="post" action="login_check.php" id="loginForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">Login</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Hasło</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <!-- Ukryte pola do zbierania danych o przeglądarce -->
                        <input type="hidden" name="screen_res" id="screen_res">
                        <input type="hidden" name="window_res" id="window_res">
                        <input type="hidden" name="colors" id="colors">
                        <input type="hidden" name="cookies" id="cookies">
                        <input type="hidden" name="java" id="java">
                        <input type="hidden" name="language" id="language">

                        <button class="btn btn-primary w-100" type="submit">Zaloguj się</button>
                    </form>

                    <hr>
                    <p class="text-center mb-2">Nie masz konta?</p>
                    <a href="register.php" class="btn btn-success w-100">Zarejestruj się</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Skrypt automatycznie wypełniający ukryte pola danymi z przeglądarki
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("screen_res").value = window.screen.width + "x" + window.screen.height;
    document.getElementById("window_res").value = window.innerWidth + "x" + window.innerHeight;
    document.getElementById("colors").value = window.screen.colorDepth;
    document.getElementById("cookies").value = navigator.cookieEnabled ? 1 : 0;
    document.getElementById("java").value = navigator.javaEnabled ? (navigator.javaEnabled() ? 1 : 0) : 0;
    document.getElementById("language").value = navigator.language || navigator.userLanguage || '-';
});
</script>

</body>
</html>