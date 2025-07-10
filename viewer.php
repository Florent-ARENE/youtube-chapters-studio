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

$videoId = $data["video_id"];
$videoTitle = $data["video_title"] ?? "Vidéo YouTube";
$chapters = $data["chapters"];
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
                <iframe id="youtube-player"
                        src="https://www.youtube.com/embed/<?php echo htmlspecialchars($videoId); ?>?enablejsapi=1"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                </iframe>
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
        let player;
        const chapters = <?php echo json_encode($chapters); ?>;

        // Cette fonction DOIT être dans le scope global pour l'API YouTube
        window.onYouTubeIframeAPIReady = function() {
            player = new YT.Player("youtube-player", {
                events: {
                    "onReady": onPlayerReady
                }
            });
        };

        function onPlayerReady(event) {
            renderChapters();
        }

        const tag = document.createElement("script");
        tag.src = "https://www.youtube.com/iframe_api";
        const firstScriptTag = document.getElementsByTagName("script")[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

        function formatTime(totalSeconds) {
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            
            if (hours > 0) {
                return `${hours}:${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`;
            }
            return `${minutes}:${seconds.toString().padStart(2, "0")}`;
        }

        function goToTime(seconds) {
            if (player && player.seekTo) {
                player.seekTo(seconds, true);
            }
        }

        function renderChapters() {
            const listElement = document.getElementById("chapters-list");
            listElement.innerHTML = "";
            
            chapters.forEach((chapter) => {
                const chapterDiv = document.createElement("div");
                let className = "chapter-item";
                if (chapter.type === "elu") className += " chapter-elu";
                else if (chapter.type === "vote") className += " chapter-vote";
                chapterDiv.className = className;
                chapterDiv.onclick = () => goToTime(chapter.time);
                
                let content = `<div class="chapter-time">${formatTime(chapter.time)}</div>`;
                
                if (chapter.type === "elu" && chapter.elu) {
                    content += `
                        <div class="chapter-content">
                            <div class="chapter-title">
                                <span class="elu-icon">👤</span> ${chapter.elu.nom}
                            </div>`;
                    
                    if (chapter.showInfo && chapter.elu.fonction) {
                        content += `<div class="elu-info">${chapter.elu.fonction}</div>`;
                    }
                    content += `</div>`;
                } else if (chapter.type === "vote") {
                    content += `<div class="chapter-title"><span class="vote-icon">🗳️</span> ${chapter.title}</div>`;
                } else {
                    content += `<div class="chapter-title">${chapter.title}</div>`;
                }
                
                chapterDiv.innerHTML = content;
                listElement.appendChild(chapterDiv);
            });
        }
    </script>
</body>
</html>