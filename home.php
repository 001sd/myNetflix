<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$avatar = $_SESSION['avatar'] ?? 'default-avatar.svg';
$username = $_SESSION['username'] ?? 'Użytkownik';
?>
<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="/sd.png">
    <title>Strona główna</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="twoj_css.css?v=2">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php require_once 'header.php'; ?>

<main class="container mt-5 pt-4">
    <div class="card mt-4 mb-4 shadow border-secondary bg-dark text-light" style="min-height: 60vh;">
        <div class="card-body p-5">
            <!-- Lewy górny róg -->
            <div class="text-start mb-5">
                <h1>Strona główna</h1>
                <h2 class="text-danger h2">Zadanie 6b - myNetflix</h2>
            </div>

            <!-- Wyśrodkowana zawartość -->
            <div class="text-center mt-5">
                <img src="media/avatars/<?php echo htmlspecialchars(basename($avatar)); ?>"
                     alt="Avatar" class="avatar mb-4 rounded-circle shadow" style="width: 120px; height: 120px; object-fit: cover;">
                
                <h3 class="fw-bold">Witaj, <?php echo htmlspecialchars($username); ?>!</h3>
                <p class="text-secondary mb-4">Jesteś zalogowany. Możesz teraz korzystać z menu.</p>
                
                <a href="netflix.php" class="btn btn-danger px-4 py-2">Przejdź do myNetflix</a>
            </div>
        </div>
    </div>
</main>

<?php require_once 'footer.php'; ?>

</body>
</html>