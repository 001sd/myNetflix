<?php
session_start();
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
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
    <title>Rejestracja</title>
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
                    <h1 class="h3 text-center mb-4">Rejestracja</h1>

                    <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                            if($_GET['error'] == 'userexists') echo "Taki użytkownik już istnieje!";
                            else echo "Wystąpił błąd podczas rejestracji.";
                        ?>
                    </div>
                    <?php endif; ?>

                    <!-- Formularz wysyła dane do register_save.php -->
                    <form method="post" action="register_save.php" id="registerForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">Login</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Hasło</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Powtórz hasło</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                            <!-- Miejsce na komunikat o hasłach -->
                            <div id="passwordMessage" style="font-size: 0.85em;" class="mt-1 fw-bold"></div>
                        </div>

                        <!-- Przycisk zablokowany domyślnie (disabled) -->
                        <button class="btn btn-success w-100" type="submit" id="submitBtn" disabled>
                            Zarejestruj się
                        </button>
                    </form>

                    <hr>
                    <p class="text-center mb-2">Masz już konto?</p>
                    <a href="login.php" class="btn btn-primary w-100">Zaloguj się</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const pass1 = document.getElementById("password");
    const pass2 = document.getElementById("password_confirm");
    const submitBtn = document.getElementById("submitBtn");
    const message = document.getElementById("passwordMessage");

    function validatePasswords() {
        // Jeśli oba pola są puste, czyścimy style i blokujemy przycisk
        if (pass1.value === "" && pass2.value === "") {
            pass1.classList.remove("is-valid", "is-invalid");
            pass2.classList.remove("is-valid", "is-invalid");
            message.textContent = "";
            submitBtn.disabled = true;
            return;
        }

        // Sprawdzanie czy hasła są identyczne
        if (pass1.value === pass2.value && pass1.value !== "") {
            pass1.classList.remove("is-invalid");
            pass1.classList.add("is-valid"); // Zielona ramka
            pass2.classList.remove("is-invalid");
            pass2.classList.add("is-valid"); // Zielona ramka
            
            message.textContent = "Hasła są identyczne.";
            message.className = "text-success mt-1 fw-bold";
            submitBtn.disabled = false; // Odblokowanie przycisku
        } else {
            pass1.classList.remove("is-valid");
            pass1.classList.add("is-invalid"); // Czerwona ramka
            pass2.classList.remove("is-valid");
            pass2.classList.add("is-invalid"); // Czerwona ramka
            
            message.textContent = "Hasła nie zgadzają się!";
            message.className = "text-danger mt-1 fw-bold";
            submitBtn.disabled = true; // Zablokowanie przycisku
        }
    }

    // Wywołuj funkcję przy każdym wpisanym znaku
    pass1.addEventListener("input", validatePasswords);
    pass2.addEventListener("input", validatePasswords);
});
</script>

</body>
</html>