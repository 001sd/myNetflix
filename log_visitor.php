<?php
// Wczytanie zunifikowanego połączenia z bazą (plik db.php musi używać zmiennej $link)
require_once 'db.php';

$ip = $_SERVER["REMOTE_ADDR"];
$browser = $link->real_escape_string($_SERVER['HTTP_USER_AGENT']);

// Odbieranie danych przesłanych z JavaScript w formacie JSON
$json = file_get_contents('php://input');
$data = json_decode($json);

if ($data) {
    $screen_res = $link->real_escape_string($data->screen_res);
    $window_res = $link->real_escape_string($data->window_res);
    $colors = (int)$data->colors;
    $cookies = (int)$data->cookies;
    $java = (int)$data->java;
    $language = $link->real_escape_string($data->language);

    // Zapis do tabeli goscieportalu ze starych zadań
    $sql = "INSERT INTO goscieportalu (ipaddress, browser, screen_res, window_res, colors, cookies, java, language) 
            VALUES ('$ip', '$browser', '$screen_res', '$window_res', $colors, $cookies, $java, '$language')";
    $link->query($sql);
}
?>