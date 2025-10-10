<?php
require_once "config.php";
require_once "functions.php";

$projectId = isset($_GET["p"]) ? sanitize($_GET["p"]) : null;

if (!$projectId || !validateProjectId($projectId)) {
    die("Projet non trouvé");
}

$data = loadChapterData($projectId);
if (!$data) {
    die("Projet non trouvé");
}

$videoType = $data["video_type"];
$videoId = $data["video_id"];
$videoTitle = $data["video_title"] ?? "Vidéo";
$chapters = $data["chapters"];
$streamData = $data["stream_data"] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($videoTitle); ?> - Chapitres</title>
    <link rel="stylesheet" href="viewer-styles.css">
</head>
<body>
    <div class="viewer-container">
        <div class="video-section">
            <div class="video-wrapper">
                <?php if ($videoType === VIDEO_TYPE_YOUTUBE): ?>
                    <iframe id="video-player"
                            src="https://www.youtube.com/embed/<?php echo htmlspecialchars($videoId); ?>?enablejsapi=1"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen>
                    </iframe>
                <?php elseif ($videoType === VIDEO_TYPE_STREAM && $streamData): ?>
                    <iframe id="video-player"
                            src="<?php echo htmlspecialchars($streamData['embed_url']); ?>"
                            width="640" 
                            height="360" 
                            frameborder="0" 
                            scrolling="no" 
                            allowfullscreen>
                    </iframe>
                <?php endif; ?>
            </div>
            <div class="video-viewer-info">
                <h1><?php echo htmlspecialchars($videoTitle); ?></h1>
            </div>
        </div>
        <div class="chapters-sidebar">
            <h2 class="chapters-title">Chapitres</h2>
            <div id="chapters-list"></div>
        </div>
    </div>

    <script>
        const videoType = <?php echo json_encode($videoType); ?>;
        const videoData = <?php echo json_encode([
            'type' => $videoType,
            'id' => $videoId,
            'streamData' => $streamData
        ]); ?>;
        const chapters = <?php echo json_encode($chapters); ?>;
        let player;

        // Charger le script approprié selon le type
        if (videoType === 'youtube') {
            // API YouTube
            window.onYouTubeIframeAPIReady = function() {
                player = new YT.Player('video-player', {
                    events: {
                        'onReady': onPlayerReady
                    }
                });
            };
            
            // Charger l'API YouTube
            const tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            const firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }

        function onPlayerReady(event) {
            displayChapters();
        }

        function displayChapters() {
            const list = document.getElementById('chapters-list');
            list.innerHTML = '';
            
            chapters.forEach((chapter, index) => {
                const div = document.createElement('div');
                div.className = 'chapter-item chapter-' + chapter.type;
                div.innerHTML = `
                    <span class="chapter-time">${formatTime(chapter.time)}</span>
                    <span class="chapter-title">${escapeHtml(chapter.title)}</span>
                `;
                div.onclick = () => navigateToTime(chapter.time);
                list.appendChild(div);
            });
        }

        function navigateToTime(seconds) {
            if (videoType === 'youtube' && player && player.seekTo) {
                player.seekTo(seconds, true);
            } else if (videoType === 'stream') {
                // Pour Stream, recharger l'iframe avec le bon timestamp
                const iframe = document.getElementById('video-player');
                if (iframe && videoData.streamData) {
                    const navObj = {
                        playbackOptions: {
                            startTimeInSeconds: seconds,
                            timestampedLinkReferrerInfo: {
                                scenario: "ChapterShare",
                                additionalInfo: { isSharedChapterAuto: false }
                            }
                        },
                        referralInfo: {
                            referralApp: "StreamWebApp",
                            referralView: "ShareChapterLink",
                            referralAppPlatform: "Web",
                            referralMode: "view"
                        }
                    };
                    
                    const navEncoded = btoa(JSON.stringify(navObj));
                    let newUrl = videoData.streamData.base_url + '?nav=' + encodeURIComponent(navEncoded);
                    
                    // Ajouter email et e si disponibles
                    if (videoData.streamData.full_url.includes('email=')) {
                        const emailMatch = videoData.streamData.full_url.match(/[?&]email=([^&]+)/);
                        if (emailMatch) newUrl += '&email=' + emailMatch[1];
                    }
                    if (videoData.streamData.full_url.includes('&e=')) {
                        const eMatch = videoData.streamData.full_url.match(/[?&]e=([^&]+)/);
                        if (eMatch) newUrl += '&e=' + eMatch[1];
                    }
                    
                    iframe.src = newUrl;
                }
            }
        }

        function formatTime(totalSeconds) {
            totalSeconds = Math.max(0, Math.floor(totalSeconds));
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            
            if (hours > 0) {
                return `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
            return `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Afficher les chapitres dès le chargement si ce n'est pas YouTube
        if (videoType !== 'youtube') {
            document.addEventListener('DOMContentLoaded', displayChapters);
        }
    </script>
</body>
</html>