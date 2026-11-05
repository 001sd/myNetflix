<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="/sd.png">
    <title>Rejestr wizyt i logowań</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="twoj_css.css?v=2">
    
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
</head>
<body>

<?php require_once 'header.php'; ?>

<main class="container-fluid mt-5 pt-5">
    <section class="sekcja1">
        <div class="card shadow-sm border-0 mx-auto" style="max-width: 1400px;">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
                <h5 class="mb-0">Rejestr wizyt i prób logowania</h5>
                <a href="home.php" class="btn btn-sm btn-light py-0">Powrót</a>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <!-- ZMIANA: Zamiast display:none użyliśmy opacity: 0 -->
                    <table id="rejestrTabela" class="table table-sm table-striped table-hover table-bordered align-middle mb-0" style="font-size: 0.8rem; width:100%; opacity: 0;">
                        <thead class="table-dark">
                            <tr>
                                <th>Data</th>
                                <th>IP / Host</th>
                                <th>Użytkownik</th>
                                <th>Status logowania</th>
                                <th>Geolokalizacja</th>
                                <th>Ekran</th>
                                <th>Okno</th>
                                <th>Kolory</th>
                                <th>Ciastka</th>
                                <th>Java</th>
                                <th>Język</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $link->query("SELECT * FROM goscieportalu ORDER BY datetime DESC");

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $db_ip = $row['ipaddress'];
                                    
                                    // Geolokalizacja
                                    $json = @file_get_contents("http://ipinfo.io/{$db_ip}/geo");
                                    $details = json_decode($json);
                                    $loc = $details->loc ?? '';
                                    $city = $details->city ?? 'Nieznane';
                                    $map_btn = $loc ? "<a href='https://www.google.pl/maps/place/$loc' target='_blank' class='btn btn-sm btn-outline-primary py-0' style='font-size:0.75rem;'>Mapa</a>" : "<span class='text-muted'>-</span>";
                                    
                                    // Status logowania (Sukces / Błąd)
                                    $status_badge = '<span class="text-muted">-</span>';
                                    if ($row['status'] === 'Sukces') {
                                        $status_badge = '<span class="badge bg-success">Sukces</span>';
                                    } elseif ($row['status'] === 'Błąd') {
                                        $status_badge = '<span class="badge bg-danger">Błąd</span>';
                                    }

                                    // Nazwa użytkownika
                                    $user_display = !empty($row['username']) ? "<strong>{$row['username']}</strong>" : '<span class="text-muted">N/A</span>';

                                    $cookies_str = $row['cookies'] ? "<span class='text-success fw-bold'>Tak</span>" : "<span class='text-danger fw-bold'>Nie</span>";
                                    $java_str = $row['java'] ? "<span class='text-success fw-bold'>Tak</span>" : "<span class='text-danger fw-bold'>Nie</span>";
                                    
                                    // Zapobieganie błędom
                                    $screen = !empty($row['screen_res']) ? $row['screen_res'] : '-';
                                    $window = !empty($row['window_res']) ? $row['window_res'] : '-';
                                    $colors = !empty($row['colors']) ? $row['colors'].' bit' : '-';
                                    
                                    echo "<tr>";
                                    echo "<td>{$row['datetime']}</td>";
                                    echo "<td><strong>{$row['ipaddress']}</strong></td>";
                                    echo "<td>{$user_display}</td>";
                                    echo "<td>{$status_badge}</td>";
                                    echo "<td>{$map_btn}</td>";
                                    echo "<td>{$screen}</td>";
                                    echo "<td>{$window}</td>";
                                    echo "<td>{$colors}</td>";
                                    echo "<td>{$cookies_str}</td>";
                                    echo "<td>{$java_str}</td>";
                                    echo "<td><span class='text-uppercase'>{$row['language']}</span></td>";
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once 'footer.php'; ?>

<script>
$(document).ready(function() {
    $('#rejestrTabela').DataTable({
        "pageLength": 10,
        "order": [[ 0, "desc" ]],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/pl.json"
        },
        "initComplete": function(settings, json) {
            // ZMIANA: Płynne podniesienie widoczności do 100%
            $('#rejestrTabela').animate({ opacity: 1 }, 300);
        }
    });
});
</script>

</body>
</html>