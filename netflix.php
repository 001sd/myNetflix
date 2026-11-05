<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$username_session = mysqli_real_escape_string($link, $_SESSION['username']);
$user_query = mysqli_query($link, "SELECT id FROM users WHERE username = '$username_session'");
$user_id = ($user_query && mysqli_num_rows($user_query) > 0) ? (int)mysqli_fetch_assoc($user_query)['id'] : 0;

// 1. WGRYWANIE FILMU
if (isset($_POST['upload_film']) && isset($_FILES['video_file'])) {
    $title = mysqli_real_escape_string($link, $_POST['title']);
    $director = mysqli_real_escape_string($link, $_POST['director']);
    $subtitles = mysqli_real_escape_string($link, $_POST['subtitles']);
    $idft = (int)$_POST['idft'];
    
    $file_name = uniqid() . '_' . basename($_FILES["video_file"]["name"]);
    $target_file = "films/" . $file_name;

    if (!file_exists('films')) { mkdir('films', 0777, true); }

    if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $target_file)) {
        mysqli_query($link, "INSERT INTO film (title, director, user_id, filename, subtitles, idft) VALUES ('$title', '$director', $user_id, '$file_name', '$subtitles', $idft)");
    }
    header("Location: netflix.php");
    exit;
}

// 2. TWORZENIE PLAYLISTY KINOWEJ
if (isset($_POST['create_playlist'])) {
    $pl_name = mysqli_real_escape_string($link, $_POST['pl_name']);
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    mysqli_query($link, "INSERT INTO netflix_playlistname (user_id, name, public) VALUES ($user_id, '$pl_name', $is_public)");
    header("Location: netflix.php");
    exit;
}

// 3. DODAWANIE DO PLAYLISTY
if (isset($_POST['add_to_playlist'])) {
    $idpl = (int)$_POST['idpl'];
    $idf = (int)$_POST['idf'];
    $check = mysqli_query($link, "SELECT * FROM netflix_playlistdatabase WHERE idpl = $idpl AND idf = $idf");
    if($check && mysqli_num_rows($check) == 0) {
        mysqli_query($link, "INSERT INTO netflix_playlistdatabase (idpl, idf) VALUES ($idpl, $idf)");
    }
    header("Location: netflix.php");
    exit;
}

// 4. USUWANIE WŁASNEJ PLAYLISTY
if (isset($_POST['delete_playlist'])) {
    $idpl = (int)$_POST['idpl'];
    $check = mysqli_query($link, "SELECT idpl FROM netflix_playlistname WHERE idpl = $idpl AND user_id = $user_id");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($link, "DELETE FROM netflix_playlistdatabase WHERE idpl = $idpl");
        mysqli_query($link, "DELETE FROM netflix_playlistname WHERE idpl = $idpl");
    }
    header("Location: netflix.php");
    exit;
}

// 5. USUWANIE WŁASNEGO FILMU
if (isset($_POST['delete_film'])) {
    $idf = (int)$_POST['idf'];
    $check = mysqli_query($link, "SELECT filename FROM film WHERE idf = $idf AND user_id = $user_id");
    if (mysqli_num_rows($check) > 0) {
        $film_data = mysqli_fetch_assoc($check);
        @unlink("films/" . $film_data['filename']);
        mysqli_query($link, "DELETE FROM netflix_playlistdatabase WHERE idf = $idf");
        mysqli_query($link, "DELETE FROM film WHERE idf = $idf");
    }
    header("Location: netflix.php");
    exit;
}

$gatunki = mysqli_query($link, "SELECT * FROM filmtype");
$filmy = mysqli_query($link, "SELECT f.*, ft.name as gatunek FROM film f LEFT JOIN filmtype ft ON f.idft = ft.idft ORDER BY f.datetime DESC");
$moje_playlisty = mysqli_query($link, "SELECT * FROM netflix_playlistname WHERE user_id = $user_id");
$publiczne_playlisty = mysqli_query($link, "SELECT p.*, u.username as autor FROM netflix_playlistname p JOIN users u ON p.user_id = u.id WHERE p.public = 1 AND p.user_id != $user_id");
?>
<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="/sd.png">
    <title>MyNetflix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #121212 !important; color: #e5e5e5; }
        .netflix-card { background-color: #181818 !important; border: 1px solid #282828 !important; border-radius: 8px; }
        .netflix-header { background-color: transparent !important; border-bottom: none !important; color: #fff !important; font-weight: 700; font-size: 1.2rem; padding: 15px 20px 5px 20px;}
        .netflix-btn { background-color: #E50914 !important; color: #fff !important; border: none !important; border-radius: 20px !important; font-weight: bold; padding: 8px 20px; transition: 0.2s; }
        .netflix-btn:hover { background-color: #f40612 !important; transform: scale(1.05); }
        .netflix-input { background-color: #282828 !important; border: 1px solid #404040 !important; color: #fff !important; border-radius: 4px; }
        .list-group-item-netflix { background-color: transparent !important; border: 1px solid #282828 !important; color: #b3b3b3; padding: 10px 15px; margin-bottom: 5px; border-radius: 4px !important; }
        .list-group-item-netflix:hover { background-color: #2a2a2a !important; color: #fff; }
        .top-nav-btn { border: 1px solid #fff; border-radius: 50px; padding: 8px 24px; color: #fff; text-decoration: none; font-size: 0.9rem; transition: 0.2s; }
        .top-nav-btn:hover { background-color: #fff; color: #000; }
        .brand-badge { background-color: #E50914; padding: 8px 20px; font-size: 1rem; border-radius: 50px; font-weight: bold; }
        
        .btn-netflix-action { background-color: #E50914; border: none; color: #fff; transition: 0.2s; }
        .btn-netflix-action:hover { background-color: #f40612; color: #fff; transform: scale(1.05); }

        /* Zwiększony rozmiar podglądu wideo, żeby pomieścić duże kontrolki */
        .table-video-container { position: relative; width: 100%; max-width: 450px; aspect-ratio: 16/9; border-radius: 8px; overflow: hidden; background-color: #000; box-shadow: 0 4px 15px rgba(0,0,0,0.4); margin: 0 auto; }
        .table-video-container video { width: 100%; height: 100%; object-fit: contain; outline: none; }
        
        .table-custom-controls { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: space-between; opacity: 0; transition: opacity 0.3s ease; background: rgba(0,0,0,0.4); }
        .table-video-container:hover .table-custom-controls, .table-video-container.active .table-custom-controls { opacity: 1; }
        
        /* Identyczne suwaki jak w głównym playerze */
        .netflix-range { -webkit-appearance: none; appearance: none; background: #555; height: 5px; border-radius: 3px; outline: none; padding: 0; margin: 0; cursor: pointer; }
        .netflix-range::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 16px; height: 16px; border-radius: 50%; background: #E50914; cursor: pointer; transition: 0.1s; }
        .netflix-range::-webkit-slider-thumb:hover { transform: scale(1.2); }
        .netflix-range::-moz-range-thumb { width: 16px; height: 16px; border-radius: 50%; background: #E50914; cursor: pointer; border: none; transition: 0.1s; }
        .netflix-range::-moz-range-thumb:hover { transform: scale(1.2); }
        
        /* Identyczne przyciski jak w głównym playerze */
        .t-btn { background: none; border: none; color: white; cursor: pointer; transition: 0.2s; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 10px; }
        .t-btn:hover { transform: scale(1.1); color: #E50914; }
        
        .t-center-play { font-size: 4.5rem; }
        .t-skip-btn { font-size: 2.5rem; position: relative; }
        .t-skip-text { font-size: 0.8rem; position: absolute; top: 55%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; }
        
        .t-bottom-gradient { background: linear-gradient(to top, rgba(0,0,0,0.9), transparent); padding: 15px 20px; }
    </style>
</head>
<body>

<main class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <span class="badge brand-badge">myNetflix</span>
        </div>
        <div>
            <a href="home.php" class="top-nav-btn">&larr; Wróć do Strony Głównej</a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card netflix-card mb-4 shadow-sm pb-2">
                <div class="card-header netflix-header">Twoja Biblioteka</div>
                <ul class="list-group list-group-flush px-3 pt-2">
                    <?php if ($moje_playlisty) while($pl = mysqli_fetch_assoc($moje_playlisty)): ?>
                        <li class="list-group-item-netflix d-flex justify-content-between align-items-center">
                            <a href="netflix_player.php?idpl=<?php echo $pl['idpl']; ?>" class="text-decoration-none text-white fw-bold d-block flex-grow-1">
                                <?php echo htmlspecialchars($pl['name']); ?> 
                                <?php if($pl['public'] == 1) echo '<span class="badge bg-secondary ms-1">Pub</span>'; ?>
                                <small class="text-muted d-block fw-normal" style="font-size: 0.75rem;">Utworzone przez: <?php echo htmlspecialchars($_SESSION['username']); ?></small>
                            </a>
                            <form method="post" class="m-0" onsubmit="return confirm('Usunąć tę listę?');">
                                <input type="hidden" name="idpl" value="<?php echo $pl['idpl']; ?>">
                                <button type="submit" name="delete_playlist" class="btn btn-link text-danger p-0 ms-2 text-decoration-none fs-5">✕</button>
                            </form>
                        </li>
                    <?php endwhile; ?>
                    
                    <?php if ($publiczne_playlisty) while($pl = mysqli_fetch_assoc($publiczne_playlisty)): ?>
                        <li class="list-group-item-netflix d-flex justify-content-between align-items-center">
                            <a href="netflix_player.php?idpl=<?php echo $pl['idpl']; ?>" class="text-decoration-none d-block flex-grow-1" style="color: #E50914;">
                                <strong><?php echo htmlspecialchars($pl['name']); ?></strong>
                                <small class="text-muted d-block fw-normal" style="font-size: 0.75rem;">Utworzone przez: <?php echo htmlspecialchars($pl['autor']); ?></small>
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>

            <div class="card netflix-card mb-4 shadow-sm">
                <div class="card-header netflix-header">Nowa Playlista</div>
                <div class="card-body">
                    <form method="post">
                        <input type="text" name="pl_name" class="form-control netflix-input mb-3" placeholder="Nazwa playlisty..." required>
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="is_public" id="isPublic" value="1">
                            <label class="form-check-label text-white" for="isPublic">Udostępnij publicznie</label>
                        </div>
                        <button type="submit" name="create_playlist" class="btn netflix-btn w-100">Utwórz</button>
                    </form>
                </div>
            </div>

            <div class="card netflix-card mb-4 shadow-sm">
                <div class="card-header netflix-header">Dodaj Film</div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <input type="text" name="title" class="form-control netflix-input mb-3" placeholder="Tytuł filmu" required>
                        <input type="text" name="director" class="form-control netflix-input mb-3" placeholder="Reżyser" required>
                        <select name="idft" class="form-select netflix-input mb-3" required>
                            <option value="">Wybierz gatunek...</option>
                            <?php if ($gatunki) while($g = mysqli_fetch_assoc($gatunki)): ?>
                                <option value="<?php echo $g['idft']; ?>"><?php echo $g['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                        <textarea name="subtitles" class="form-control netflix-input mb-3" placeholder="Napisy (opcjonalnie)" rows="2"></textarea>
                        <input type="file" name="video_file" accept=".mp4" class="form-control netflix-input mb-4" required>
                        <button type="submit" name="upload_film" class="btn btn-outline-light rounded-pill w-100 fw-bold">Wgraj plik</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card netflix-card shadow-sm p-3">
                <div class="card-header netflix-header">Wszystkie Filmy</div>
                <div class="card-body p-0 mt-2">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover w-100 m-0 align-middle" style="background-color: transparent;">
                            <thead style="border-bottom: 1px solid #282828;">
                                <tr>
                                    <th class="text-muted fw-normal pb-3 ps-3">Tytuł</th>
                                    <th class="text-muted fw-normal pb-3 text-center">Podgląd z pełnymi kontrolkami</th>
                                    <th class="text-muted fw-normal pb-3 text-end pe-3">Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($filmy) while($film = mysqli_fetch_assoc($filmy)): 
                                    $vid_id = "vid_" . $film['idf'];
                                ?>
                                <tr style="border-bottom: 1px solid #282828;">
                                    <td class="ps-3 py-3" style="width: 25%;">
                                        <div class="text-white fw-bold fs-6"><?php echo htmlspecialchars($film['title']); ?></div>
                                        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars($film['director']); ?> • <?php echo $film['gatunek']; ?></div>
                                    </td>
                                    <td class="py-3">
                                        <!-- Mini-odtwarzacz, w którym kontrolki są 1:1 takie same jak w dużym playerze -->
                                        <div class="table-video-container" id="container_<?php echo $vid_id; ?>">
                                            <video preload="metadata" id="<?php echo $vid_id; ?>" src="films/<?php echo $film['filename']; ?>" playsinline></video>
                                            
                                            <div class="table-custom-controls" id="controls_<?php echo $vid_id; ?>">
                                                <div class="top-spacer" style="height: 30px;"></div>
                                                
                                                <!-- Środkowy rząd: Cofnij 10s, Play/Pauza, Do przodu 10s (Duże) -->
                                                <div class="d-flex justify-content-center align-items-center gap-5 w-100">
                                                    <button class="t-btn t-skip-btn" onclick="tableSkip('<?php echo $vid_id; ?>', -10)" title="Cofnij o 10s">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                        <span class="t-skip-text">10</span>
                                                    </button>
                                                    
                                                    <button class="t-btn t-center-play" onclick="tableTogglePlay('<?php echo $vid_id; ?>')" id="btnPlay_<?php echo $vid_id; ?>">
                                                        <i class="bi bi-play-circle-fill"></i>
                                                    </button>
                                                    
                                                    <button class="t-btn t-skip-btn" onclick="tableSkip('<?php echo $vid_id; ?>', 10)" title="Do przodu o 10s">
                                                        <i class="bi bi-arrow-clockwise"></i>
                                                        <span class="t-skip-text">10</span>
                                                    </button>
                                                </div>
                                                
                                                <!-- Dolny pasek sterowania -->
                                                <div class="t-bottom-gradient w-100">
                                                    <div class="d-flex align-items-center mb-2 gap-3">
                                                        <input type="range" class="netflix-range flex-grow-1" id="progress_<?php echo $vid_id; ?>" value="0" min="0" max="100" step="0.1" oninput="tableSeek('<?php echo $vid_id; ?>', this.value)">
                                                        <span class="text-white small fw-bold" id="time_<?php echo $vid_id; ?>" style="min-width: 90px; text-align: right;">0:00 / 0:00</span>
                                                    </div>
                                                    
                                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <button class="t-btn p-0 m-0" onclick="tableToggleMute('<?php echo $vid_id; ?>')" id="mute_<?php echo $vid_id; ?>">
                                                                <i class="bi bi-volume-up fs-4"></i>
                                                            </button>
                                                            <input type="range" class="netflix-range" id="vol_<?php echo $vid_id; ?>" value="1" min="0" max="1" step="0.05" style="width: 100px;" oninput="tableVolume('<?php echo $vid_id; ?>', this.value)">
                                                        </div>
                                                        
                                                        <div class="d-flex align-items-center gap-4">
                                                            <button class="t-btn p-0 m-0 d-flex flex-row align-items-center gap-2" onclick="tableChangeSpeed('<?php echo $vid_id; ?>')" title="Prędkość odtwarzania">
                                                                <i class="bi bi-speedometer2 fs-5"></i> <span id="speed_<?php echo $vid_id; ?>" class="fw-bold">1x</span>
                                                            </button>
                                                            <button class="t-btn p-0 m-0 ms-2" onclick="tableFullScreen('<?php echo $vid_id; ?>')" title="Pełny ekran">
                                                                <i class="bi bi-fullscreen fs-5"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pe-3 py-3 text-end" style="width: 25%;">
                                        <div class="d-flex justify-content-end align-items-center">
                                            <form method="post" class="d-flex me-2 m-0">
                                                <input type="hidden" name="idf" value="<?php echo $film['idf']; ?>">
                                                <select name="idpl" class="form-select form-select-sm netflix-input me-2" style="width: 130px; font-size: 0.8rem;" required>
                                                    <option value="">Do playlisty...</option>
                                                    <?php 
                                                    if ($moje_playlisty) {
                                                        mysqli_data_seek($moje_playlisty, 0);
                                                        while($mpl = mysqli_fetch_assoc($moje_playlisty)): 
                                                    ?>
                                                        <option value="<?php echo $mpl['idpl']; ?>"><?php echo htmlspecialchars($mpl['name']); ?></option>
                                                    <?php 
                                                        endwhile; 
                                                    }
                                                    ?>
                                                </select>
                                                <button type="submit" name="add_to_playlist" class="btn btn-sm btn-netflix-action rounded-circle fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">+</button>
                                            </form>
                                            <?php if($film['user_id'] == $user_id): ?>
                                            <form method="post" class="m-0" onsubmit="return confirm('Usunąć film z serwisu?');">
                                                <input type="hidden" name="idf" value="<?php echo $film['idf']; ?>">
                                                <button type="submit" name="delete_film" class="btn btn-sm btn-outline-secondary border-0 fs-5 p-1">🗑️</button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    const speedsList = [1, 1.25, 1.5, 2, 0.5];
    const speedIndices = {};

    function updateSliderColor(slider) {
        if(!slider) return;
        const percent = ((slider.value - slider.min) / (slider.max - slider.min)) * 100;
        slider.style.background = `linear-gradient(to right, #E50914 ${percent}%, #555 ${percent}%)`;
    }

    function tableTogglePlay(vidId) {
        const vid = document.getElementById(vidId);
        const btn = document.getElementById('btnPlay_' + vidId);
        const container = document.getElementById('container_' + vidId);
        
        if (vid.paused) {
            // Pauzujemy wszystkie inne filmy na stronie przed startem tego
            document.querySelectorAll('video[id^="vid_"]').forEach(v => {
                if(v.id !== vidId && !v.paused) {
                    v.pause();
                    document.getElementById('btnPlay_' + v.id).innerHTML = '<i class="bi bi-play-circle-fill"></i>';
                }
            });
            vid.play();
            btn.innerHTML = '<i class="bi bi-pause-circle-fill"></i>';
            container.classList.add('active');
        } else {
            vid.pause();
            btn.innerHTML = '<i class="bi bi-play-circle-fill"></i>';
        }
    }

    function tableSkip(vidId, time) {
        const vid = document.getElementById(vidId);
        vid.currentTime += time;
    }

    function tableSeek(vidId, val) {
        const vid = document.getElementById(vidId);
        vid.currentTime = (val / 100) * vid.duration;
        updateSliderColor(document.getElementById('progress_' + vidId));
    }

    function tableVolume(vidId, val) {
        const vid = document.getElementById(vidId);
        vid.volume = val;
        updateSliderColor(document.getElementById('vol_' + vidId));
        const muteBtn = document.getElementById('mute_' + vidId);
        if (val == 0) muteBtn.innerHTML = '<i class="bi bi-volume-mute fs-4 text-danger"></i>';
        else if (val < 0.5) muteBtn.innerHTML = '<i class="bi bi-volume-down fs-4"></i>';
        else muteBtn.innerHTML = '<i class="bi bi-volume-up fs-4"></i>';
    }

    function tableToggleMute(vidId) {
        const vid = document.getElementById(vidId);
        const volBar = document.getElementById('vol_' + vidId);
        const muteBtn = document.getElementById('mute_' + vidId);
        if (vid.volume > 0) {
            vid.dataset.vol = vid.volume;
            vid.volume = 0;
            volBar.value = 0;
            muteBtn.innerHTML = '<i class="bi bi-volume-mute fs-4 text-danger"></i>';
        } else {
            vid.volume = vid.dataset.vol || 1;
            volBar.value = vid.volume;
            muteBtn.innerHTML = '<i class="bi bi-volume-up fs-4"></i>';
        }
        updateSliderColor(volBar);
    }

    function tableChangeSpeed(vidId) {
        const vid = document.getElementById(vidId);
        if(!speedIndices[vidId]) speedIndices[vidId] = 0;
        speedIndices[vidId] = (speedIndices[vidId] + 1) % speedsList.length;
        vid.playbackRate = speedsList[speedIndices[vidId]];
        document.getElementById('speed_' + vidId).innerText = speedsList[speedIndices[vidId]] + "x";
    }

    function tableFullScreen(vidId) {
        const container = document.getElementById('container_' + vidId);
        if (!document.fullscreenElement) {
            if(container.requestFullscreen) container.requestFullscreen();
            else if(container.webkitRequestFullscreen) container.webkitRequestFullscreen();
        } else {
            if(document.exitFullscreen) document.exitFullscreen();
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const videos = document.querySelectorAll('video[id^="vid_"]');
        videos.forEach(vid => {
            const vidId = vid.id;
            const progressBar = document.getElementById('progress_' + vidId);
            const volumeBar = document.getElementById('vol_' + vidId);
            const btnPlay = document.getElementById('btnPlay_' + vidId);
            const container = document.getElementById('container_' + vidId);

            vid.volume = 1;
            updateSliderColor(volumeBar);
            updateSliderColor(progressBar);

            function formatTime(seconds) {
                let m = Math.floor(seconds / 60);
                let s = Math.floor(seconds % 60);
                if (s < 10) s = "0" + s;
                return m + ":" + s;
            }

            vid.addEventListener('timeupdate', () => {
                if (!isNaN(vid.duration)) {
                    const percent = (vid.currentTime / vid.duration) * 100;
                    progressBar.value = percent;
                    updateSliderColor(progressBar);
                    document.getElementById('time_' + vidId).innerText = formatTime(vid.currentTime) + " / " + formatTime(vid.duration);
                }
            });

            vid.addEventListener('ended', () => {
                btnPlay.innerHTML = '<i class="bi bi-play-circle-fill"></i>';
                container.classList.remove('active');
            });

            vid.addEventListener('click', () => tableTogglePlay(vidId));

            // Ukrywanie paska po 3s bezruchu
            let timeout;
            container.addEventListener('mousemove', () => {
                container.classList.add('active');
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    if(!vid.paused) container.classList.remove('active');
                }, 3000);
            });
            container.addEventListener('mouseleave', () => {
                if(!vid.paused) container.classList.remove('active');
            });
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>