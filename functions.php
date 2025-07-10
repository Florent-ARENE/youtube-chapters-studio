<?php
/**
 * Fonctions métier de l'application
 */

require_once 'config.php';

/**
 * Chargement sécurisé du fichier CSV des élus
 */
function loadElus() {
    $elus = [];
    
    if (!file_exists(ELUS_FILE) || !is_readable(ELUS_FILE)) {
        return $elus;
    }
    
    // Lire le fichier avec gestion de l'encodage
    $content = file_get_contents(ELUS_FILE);
    if ($content === false) {
        return $elus;
    }
    
    // Conversion de l'encodage Windows-1252 vers UTF-8
    $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
    
    // Parser le CSV de manière sécurisée
    $lines = explode("\n", $content);
    $headers = str_getcsv(array_shift($lines), ';');
    
    foreach ($lines as $line) {
        if (trim($line) === '') continue;
        
        $data = str_getcsv($line, ';');
        if (count($data) === count($headers)) {
            $elu = array_combine($headers, $data);
            // Sanitize toutes les données
            $elus[] = sanitize($elu);
        }
    }
    
    return $elus;
}

/**
 * Récupération sécurisée du titre de la vidéo YouTube
 */
function getYouTubeTitle($videoId) {
    // Validation de l'ID
    if (!validateYouTubeId($videoId)) {
        return 'Vidéo YouTube';
    }
    
    // Méthode 1 : Avec noembed.com
    $url = "https://noembed.com/embed?url=https://www.youtube.com/watch?v=" . urlencode($videoId);
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0',
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['title']) && !empty($data['title'])) {
            return sanitize($data['title']);
        }
    }
    
    // Méthode 2 : YouTube oEmbed avec cURL
    if (function_exists('curl_init')) {
        $url = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=" . 
               urlencode($videoId) . "&format=json";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['title']) && !empty($data['title'])) {
                return sanitize($data['title']);
            }
        }
    }
    
    return 'Vidéo YouTube';
}

/**
 * Chargement sécurisé des données d'un projet
 */
function loadChapterData($projectId) {
    // Validation stricte de l'ID
    if (!validateProjectId($projectId)) {
        return null;
    }
    
    // Construction sécurisée du chemin
    $filename = $projectId . '.json';
    $filepath = DATA_DIR . '/' . $filename;
    
    // Vérification du chemin
    if (!isSecurePath($filepath, DATA_DIR)) {
        return null;
    }
    
    if (!file_exists($filepath) || !is_readable($filepath)) {
        return null;
    }
    
    $content = file_get_contents($filepath);
    if ($content === false) {
        return null;
    }
    
    $data = json_decode($content, true);
    if (!is_array($data)) {
        return null;
    }
    
    // Sanitize les données chargées
    return [
        'video_id' => sanitize($data['video_id'] ?? ''),
        'video_title' => sanitize($data['video_title'] ?? ''),
        'chapters' => array_map('sanitizeChapter', $data['chapters'] ?? []),
        'created_at' => sanitize($data['created_at'] ?? ''),
        'updated_at' => sanitize($data['updated_at'] ?? '')
    ];
}

/**
 * Sauvegarde sécurisée des données d'un projet
 */
function saveChapterData($projectId, $videoId, $videoTitle, $chapters) {
    // Validations
    if (!validateProjectId($projectId)) {
        throw new Exception('ID de projet invalide');
    }
    
    if (!validateYouTubeId($videoId)) {
        throw new Exception('ID de vidéo invalide');
    }
    
    // Limite du nombre de chapitres
    if (count($chapters) > MAX_CHAPTERS) {
        throw new Exception('Trop de chapitres (max ' . MAX_CHAPTERS . ')');
    }
    
    // Créer le dossier DATA_DIR s'il n'existe pas
    if (!is_dir(DATA_DIR)) {
        $oldUmask = umask(0);
        if (!mkdir(DATA_DIR, 0777, true)) {
            umask($oldUmask);
            throw new Exception('Impossible de créer le dossier de données');
        }
        umask($oldUmask);
    }
    
    // Construction sécurisée du chemin
    $filename = $projectId . '.json';
    $filepath = DATA_DIR . '/' . $filename;
    
    // Vérification du chemin avec la fonction corrigée
    if (!isSecurePath($filepath, DATA_DIR)) {
        throw new Exception('Chemin invalide');
    }
    
    // Charger les données existantes pour conserver la date de création
    $existingData = loadChapterData($projectId);
    
    // Préparer les données
    $data = [
        'video_id' => $videoId,
        'video_title' => mb_substr(sanitize($videoTitle), 0, 500),
        'chapters' => array_map('sanitizeChapter', $chapters),
        'created_at' => $existingData['created_at'] ?? date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Sauvegarder
    $result = file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
    if ($result === false) {
        throw new Exception('Erreur lors de la sauvegarde');
    }
    
    return true;
}

/**
 * Récupération sécurisée de tous les projets
 */
function getAllProjects() {
    $projects = [];
    
    if (!is_dir(DATA_DIR)) {
        return $projects;
    }
    
    $files = glob(DATA_DIR . '/*.json');
    
    foreach ($files as $file) {
        // Vérifier que le fichier est dans le bon dossier
        if (!isSecurePath($file, DATA_DIR)) {
            continue;
        }
        
        $projectId = basename($file, '.json');
        if (!validateProjectId($projectId)) {
            continue;
        }
        
        $data = loadChapterData($projectId);
        if ($data) {
            $projects[] = [
                'id' => $projectId,
                'video_id' => $data['video_id'],
                'video_title' => $data['video_title'] ?: 'Vidéo sans titre',
                'chapters_count' => count($data['chapters']),
                'created_at' => $data['created_at'] ?? 'Non défini',
                'updated_at' => $data['updated_at'] ?? $data['created_at'] ?? 'Non défini'
            ];
        }
    }
    
    // Trier par date de mise à jour décroissante
    usort($projects, function($a, $b) {
        return strtotime($b['updated_at']) - strtotime($a['updated_at']);
    });
    
    return $projects;
}

/**
 * Création du fichier viewer.php s'il n'existe pas
 */
function createViewerFile() {
    if (file_exists('viewer.php')) {
        return;
    }
    
    $viewerContent = '<?php
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

        // Cette fonction DOIT être dans le scope global pour l\'API YouTube
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
</html>';

    file_put_contents('viewer.php', $viewerContent);
}