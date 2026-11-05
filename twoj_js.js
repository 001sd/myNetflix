function myLoadHeader() {
    $(document).ready(function() {
        // Ładowanie menu
        $('#myHeader').load("header.php");
        
        // --- Kod rejestrujący wizytę (Zadanie 3) ---
        // session_storage zapobiega spamowaniu bazy przy każdym odświeżeniu strony F5
        if (!sessionStorage.getItem('visitLogged')) {
            let visitorData = {
                screen_res: window.screen.width + "x" + window.screen.height,
                window_res: window.innerWidth + "x" + window.innerHeight,
                colors: window.screen.colorDepth,
                cookies: navigator.cookieEnabled ? 1 : 0,
                java: (typeof navigator.javaEnabled === 'function' && navigator.javaEnabled()) ? 1 : 0,
                language: navigator.language || navigator.userLanguage || 'Nieznany'
            };

            // Wysłanie paczki danych do pliku log_visitor.php
            fetch('log_visitor.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(visitorData)
            }).then(() => {
                sessionStorage.setItem('visitLogged', 'true');
            });
        }
    });
}