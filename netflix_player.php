<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['idpl']) || empty($_GET['idpl'])) {
    die("<body style='background-color: #121212; color: white; text-align: center; margin-top: 50px;'><h2>Nie wybrano listy.</h2><br><a href='netflix.php' style='color: #E50914;'>Wróć do MyNetflix</a></body>");
}

$idpl = (int)$_GET['idpl'];

$username_session = mysqli_real_escape_string($link, $_SESSION['username']);
$user_query = mysqli_query($link, "SELECT id FROM users WHERE username = '$username_session'");
$user_id = ($user_query && mysqli_num_rows($user_query) > 0) ? (int)mysqli_fetch_assoc($user_query)['id'] : 0;

$playlist_query = mysqli_query($link, "SELECT name, user_id FROM netflix_playlistname WHERE idpl = $idpl");
$playlist = mysqli_fetch_assoc($playlist_query);

$is_owner = ($playlist && $playlist['user_id'] == $user_id);

if (isset($_POST['remove_from_playlist']) && $is_owner) {
    $remove_idf = (int)$_POST['remove_idf'];
    mysqli_query($link, "DELETE FROM netflix_playlistdatabase WHERE idpl = $idpl AND idf = $remove_idf");
    header("Location: netflix_player.php?idpl=$idpl");
    exit;
}

$films_query = mysqli_query($link, "SELECT f.idf, f.title, f.director, f.filename, f.subtitles FROM netflix_playlistdatabase pld JOIN film f ON pld.idf = f.idf WHERE pld.idpl = $idpl");

$playlist_data = array();
if ($films_query) {
    while($row = mysqli_fetch_assoc($films_query)) {
        $playlist_data[] = array(
            'idf' => $row['idf'],
            'title' => $row['title'],
            'director' => $row['director'],
            'file' => 'films/' . $row['filename']
        );
    }
}
$ilosc_filmow = count($playlist_data);
?>
<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="/sd.png">
    <title>Odtwarzacz - <?php echo htmlspecialchars($playlist['name'] ?? ''); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    
    <style>
        body { background-color: #121212 !important; color: #e5e5e5; display: flex; flex-direction: column; min-height: 100vh; position: relative;}
        .netflix-card { background-color: #181818 !important; border-radius: 12px; border: none; box-shadow: 0 8px 24px rgba(0,0,0,0.5);}
        
        /* Kontener i Wideo */
        .video-container { position: relative; width: 100%; border-radius: 8px; overflow: hidden; background-color: #000; box-shadow: 0 4px 15px rgba(0,0,0,0.4); margin-bottom: 10px; display: flex; justify-content: center; align-items: center; }
        video { width: 100%; height: auto; aspect-ratio: 16/9; object-fit: contain; outline: none; }
        
        /* Niestandardowe Sterowanie */
        .custom-controls { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: space-between; opacity: 0; transition: opacity 0.3s ease; background: rgba(0,0,0,0.4); }
        .video-container:hover .custom-controls, .video-container.active .custom-controls { opacity: 1; }
        
        /* Zmodyfikowane czerwone suwaki (bez domyślnego niebieskiego z Bootstrapa) */
        .netflix-range { -webkit-appearance: none; appearance: none; background: #555; height: 5px; border-radius: 3px; outline: none; padding: 0; margin: 0; cursor: pointer; }
        .netflix-range::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 16px; height: 16px; border-radius: 50%; background: #E50914; cursor: pointer; transition: 0.1s; }
        .netflix-range::-webkit-slider-thumb:hover { transform: scale(1.2); }
        .netflix-range::-moz-range-thumb { width: 16px; height: 16px; border-radius: 50%; background: #E50914; cursor: pointer; border: none; transition: 0.1s; }
        .netflix-range::-moz-range-thumb:hover { transform: scale(1.2); }
        
        .control-btn { background: none; border: none; color: white; cursor: pointer; transition: 0.2s; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 10px; }
        .control-btn:hover { transform: scale(1.1); color: #E50914; }
        
        .center-play-btn { font-size: 4.5rem; }
        .skip-btn { font-size: 2.5rem; position: relative; }
        .skip-text { font-size: 0.8rem; position: absolute; top: 55%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; }
        
        .bottom-gradient { background: linear-gradient(to top, rgba(0,0,0,0.9), transparent); padding: 15px 20px; }
        
        /* Lista filmów - podświetlenie na czerwono */
        .track-item { transition: 0.2s; border-radius: 4px; border-bottom: 1px solid #282828;}
        .track-item:hover { background-color: #2a2a2a !important; }
        .active-playing { background-color: #2a2a2a !important; border-left: 4px solid #E50914;}
        .active-playing .track-title { color: #E50914 !important; } /* Tytuł na czerwono */
        
        .top-nav-btn { border: 1px solid #fff; border-radius: 50px; padding: 8px 24px; color: #fff; text-decoration: none; font-size: 0.9rem; transition: 0.2s; display: inline-block;}
        .top-nav-btn:hover { background-color: #fff; color: #000; }
        .drag-handle { cursor: grab; font-size: 1.2rem; color: #666; transition: color 0.2s; }
        .drag-handle:hover { color: #fff; }
        .drag-handle:active { cursor: grabbing; }
        .clickable-area { cursor: pointer; }
    </style>
</head>
<body>

<div class="p-4 w-100 position-absolute top-0 start-0 z-3">
    <a href="netflix.php" class="top-nav-btn">&larr; Wróć</a>
</div>

<main class="container d-flex flex-grow-1 align-items-center justify-content-center mt-5 pt-4 mb-5">
    
    <div class="card netflix-card p-3 w-100" style="max-width: 900px; margin-top: 40px;">
        <div class="text-center mb-3">
            <small class="fw-bold" style="color: #E50914; letter-spacing: 2px;">PLAYLISTA: <?php echo mb_strtoupper(htmlspecialchars($playlist['name'] ?? '')); ?></small>
        </div>
        
        <?php if($ilosc_filmow > 0): ?>
            
            <div class="video-container" id="videoContainer">
                <video id="video-player" playsinline></video>
                
                <div class="custom-controls" id="customControls">
                    <div class="top-spacer" style="height: 50px;"></div>
                    
                    <div class="d-flex justify-content-center align-items-center gap-5 w-100">
                        <button class="control-btn skip-btn" onclick="skip(-10)" title="Cofnij o 10s">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span class="skip-text">10</span>
                        </button>
                        
                        <button class="control-btn center-play-btn" onclick="togglePlay()" id="centerPlayBtn">
                            <i class="bi bi-pause-circle-fill"></i>
                        </button>
                        
                        <button class="control-btn skip-btn" onclick="skip(10)" title="Do przodu o 10s">
                            <i class="bi bi-arrow-clockwise"></i>
                            <span class="skip-text">10</span>
                        </button>
                    </div>

                    <div class="bottom-gradient w-100">
                        <div class="d-flex align-items-center mb-2 gap-3">
                            <!-- Czerwony pasek postępu -->
                            <input type="range" class="netflix-range flex-grow-1" id="progressBar" value="0" min="0" max="100" step="0.1">
                            <span class="text-white small fw-bold" id="timeDisplay" style="min-width: 90px; text-align: right;">0:00 / 0:00</span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="d-flex align-items-center gap-3">
                                <button class="control-btn p-0 m-0" onclick="toggleMute()" id="muteBtn">
                                    <i class="bi bi-volume-up fs-4"></i>
                                </button>
                                <!-- Czerwony pasek głośności -->
                                <input type="range" class="netflix-range" id="volumeBar" value="1" min="0" max="1" step="0.05" style="width: 100px;">
                            </div>
                            
                            <div class="d-flex align-items-center gap-3">
                                <button class="control-btn p-0 m-0 d-flex flex-row align-items-center gap-2 me-2" onclick="changeSpeed()" title="Prędkość odtwarzania">
                                    <i class="bi bi-speedometer2 fs-5"></i> <span id="speedDisplay" class="fw-bold small">1x</span>
                                </button>
                                <!-- Przycisk POPRZEDNI -->
                                <button class="control-btn p-0 m-0 d-flex flex-row align-items-center gap-1" onclick="prevTrack()" title="Poprzedni odcinek">
                                    <i class="bi bi-skip-start-fill fs-3"></i> <span class="d-none d-md-inline fw-bold small">Poprzedni</span>
                                </button>
                                <!-- Przycisk NASTĘPNY -->
                                <button class="control-btn p-0 m-0 d-flex flex-row align-items-center gap-1" onclick="nextTrack()" title="Następny odcinek">
                                    <i class="bi bi-skip-end-fill fs-3"></i> <span class="d-none d-md-inline fw-bold small">Następny</span>
                                </button>
                                <button class="control-btn p-0 m-0 ms-2" onclick="toggleFullScreen()" title="Pełny ekran">
                                    <i class="bi bi-fullscreen fs-5"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mb-4">
                <h3 id="track-title" class="text-white fw-bold mb-0 mt-2">Tytuł filmu</h3>
                <p id="track-director" class="text-muted" style="font-size: 1rem;">Reżyseria</p>
            </div>

            <div class="mt-2">
                <ul class="list-group list-group-flush" id="playlist-items">
                    <?php foreach($playlist_data as $index => $track): ?>
                        <li class="list-group-item bg-transparent text-white d-flex justify-content-between align-items-center px-3 py-3 track-item">
                            <div class="drag-handle me-3" title="Chwyć, aby przenieść">☰</div>
                            <div class="flex-grow-1 clickable-area" onclick="playSpecificNode(this)">
                                <!-- Dodano klasę track-title do zmiany koloru -->
                                <strong class="fs-6 track-title"><?php echo htmlspecialchars($track['title']); ?></strong>
                                <span class="text-muted ms-2 small">Reżyseria: <?php echo htmlspecialchars($track['director']); ?></span>
                            </div>
                            <?php if($is_owner): ?>
                            <form method="post" class="m-0" onsubmit="return confirm('Usunąć ten film z listy?');">
                                <input type="hidden" name="remove_idf" value="<?php echo $track['idf']; ?>">
                                <button type="submit" name="remove_from_playlist" class="btn btn-sm btn-link text-danger text-decoration-none fs-5 p-0 ms-2" title="Usuń z listy">✕</button>
                            </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

        <?php else: ?>
            <div class="text-center py-5">
                <h4 class="text-white mb-3">Ta lista jest pusta.</h4>
                <a href="netflix.php" class="btn btn-outline-danger rounded-pill px-4">Wróć do MyNetflix i dodaj filmy</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    let playlist = <?php echo json_encode($playlist_data); ?>;
    
    if (playlist && playlist.length > 0) {
        let currentTrack = 0;
        
        const videoContainer = document.getElementById('videoContainer');
        const videoPlayer = document.getElementById('video-player');
        const customControls = document.getElementById('customControls');
        const centerPlayBtn = document.getElementById('centerPlayBtn');
        const progressBar = document.getElementById('progressBar');
        const timeDisplay = document.getElementById('timeDisplay');
        const volumeBar = document.getElementById('volumeBar');
        const muteBtn = document.getElementById('muteBtn');
        const speedDisplay = document.getElementById('speedDisplay');
        
        const trackTitle = document.getElementById('track-title');
        const trackDirector = document.getElementById('track-director');
        const items = document.getElementById('playlist-items').children;

        const speeds = [1, 1.25, 1.5, 2, 0.5];
        let speedIndex = 0;

        videoPlayer.volume = 1;
        
        // Funkcja kolorująca tło suwaka na bieżąco (efekt wypełnienia na czerwono)
        function updateSliderColor(slider) {
            const percent = ((slider.value - slider.min) / (slider.max - slider.min)) * 100;
            slider.style.background = `linear-gradient(to right, #E50914 ${percent}%, #555 ${percent}%)`;
        }

        // Główna funkcja ładująca wideo
        function loadTrack(index) {
            videoPlayer.src = playlist[index].file;
            trackTitle.innerText = playlist[index].title;
            trackDirector.innerText = playlist[index].director;
            
            // Podświetlenie odpowiedniego wiersza w playliście
            for(let i=0; i<items.length; i++) {
                if(i === index) items[i].classList.add('active-playing');
                else items[i].classList.remove('active-playing');
            }
            
            // Zachowanie wybranej prędkości
            videoPlayer.playbackRate = speeds[speedIndex];
            
            // AUTO ODTWARZANIE
            videoPlayer.play().then(() => {
                centerPlayBtn.innerHTML = '<i class="bi bi-pause-circle-fill"></i>';
            }).catch(err => {
                // Jeśli przeglądarka zablokuje autoplay
                centerPlayBtn.innerHTML = '<i class="bi bi-play-circle-fill"></i>';
            });
        }

        function playSpecificNode(node) {
            let li = node.closest('li');
            let index = Array.from(li.parentNode.children).indexOf(li);
            if (currentTrack !== index) {
                currentTrack = index;
                loadTrack(currentTrack);
            }
        }

        function nextTrack() {
            if (currentTrack < playlist.length - 1) {
                currentTrack++; 
                loadTrack(currentTrack); 
            } else {
                currentTrack = 0; // Zapętl do początku
                loadTrack(currentTrack);
            }
        }

        function prevTrack() {
            if (currentTrack > 0) {
                currentTrack--; 
                loadTrack(currentTrack); 
            } else {
                currentTrack = playlist.length - 1; // Zapętl do końca
                loadTrack(currentTrack);
            }
        }

        // Kontrolki Wideo
        function togglePlay() {
            if (videoPlayer.paused) {
                videoPlayer.play();
                centerPlayBtn.innerHTML = '<i class="bi bi-pause-circle-fill"></i>';
            } else {
                videoPlayer.pause();
                centerPlayBtn.innerHTML = '<i class="bi bi-play-circle-fill"></i>';
            }
        }

        function skip(time) {
            videoPlayer.currentTime += time;
        }

        volumeBar.addEventListener('input', function() {
            videoPlayer.volume = this.value;
            updateVolumeIcon();
            updateSliderColor(this);
        });

        function toggleMute() {
            if(videoPlayer.volume > 0) {
                videoPlayer.dataset.savedVolume = videoPlayer.volume;
                videoPlayer.volume = 0;
                volumeBar.value = 0;
            } else {
                videoPlayer.volume = videoPlayer.dataset.savedVolume || 1;
                volumeBar.value = videoPlayer.volume;
            }
            updateVolumeIcon();
            updateSliderColor(volumeBar);
        }

        function updateVolumeIcon() {
            if (videoPlayer.volume === 0) muteBtn.innerHTML = '<i class="bi bi-volume-mute fs-4"></i>';
            else if (videoPlayer.volume < 0.5) muteBtn.innerHTML = '<i class="bi bi-volume-down fs-4"></i>';
            else muteBtn.innerHTML = '<i class="bi bi-volume-up fs-4"></i>';
        }

        function formatTime(seconds) {
            let m = Math.floor(seconds / 60);
            let s = Math.floor(seconds % 60);
            if (s < 10) s = "0" + s;
            return m + ":" + s;
        }

        videoPlayer.addEventListener('timeupdate', () => {
            if (!isNaN(videoPlayer.duration)) {
                const percent = (videoPlayer.currentTime / videoPlayer.duration) * 100;
                progressBar.value = percent;
                timeDisplay.innerText = formatTime(videoPlayer.currentTime) + " / " + formatTime(videoPlayer.duration);
                updateSliderColor(progressBar);
            }
        });

        progressBar.addEventListener('input', function() {
            const time = (this.value / 100) * videoPlayer.duration;
            videoPlayer.currentTime = time;
            updateSliderColor(this);
        });

        function changeSpeed() {
            speedIndex = (speedIndex + 1) % speeds.length;
            videoPlayer.playbackRate = speeds[speedIndex];
            speedDisplay.innerText = speeds[speedIndex] + "x";
        }

        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                if(videoContainer.requestFullscreen) videoContainer.requestFullscreen();
                else if(videoContainer.webkitRequestFullscreen) videoContainer.webkitRequestFullscreen();
            } else {
                if(document.exitFullscreen) document.exitFullscreen();
            }
        }

        // Auto przejście po końcu
        videoPlayer.addEventListener('ended', nextTrack);

        // Znikanie paska nawigacji po 3s bezruchu
        let timeout;
        function resetControlsTimeout() {
            videoContainer.classList.add('active');
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                if(!videoPlayer.paused) {
                    videoContainer.classList.remove('active');
                }
            }, 3000);
        }
        videoContainer.addEventListener('mousemove', resetControlsTimeout);
        videoContainer.addEventListener('mouseleave', () => videoContainer.classList.remove('active'));
        
        videoPlayer.addEventListener('click', togglePlay);

        // Przeciąganie 
        new Sortable(document.getElementById('playlist-items'), {
            animation: 150,
            handle: '.drag-handle',
            onEnd: function (evt) {
                if (evt.oldIndex === evt.newIndex) return;
                
                const movedItem = playlist.splice(evt.oldIndex, 1)[0];
                playlist.splice(evt.newIndex, 0, movedItem);

                if (currentTrack === evt.oldIndex) currentTrack = evt.newIndex;
                else if (currentTrack > evt.oldIndex && currentTrack <= evt.newIndex) currentTrack--;
                else if (currentTrack < evt.oldIndex && currentTrack >= evt.newIndex) currentTrack++;
                
                for(let i=0; i<items.length; i++) {
                    if(i === currentTrack) items[i].classList.add('active-playing');
                    else items[i].classList.remove('active-playing');
                }
            }
        });

        // Start
        updateSliderColor(volumeBar);
        updateSliderColor(progressBar);
        loadTrack(currentTrack);
    }
</script>
</body>
</html>