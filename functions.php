<?php
/**
 * Fonctions métier - Chapter Studio
 * Version 2.0.3 - Correction double encodage Stream
 * 
 * CHANGELOG v2.0.3 :
 * - Ajout validateLoadedChapter() pour éviter double sanitization au chargement
 * - Suppression double sanitization dans saveChapterData()
 * - Correction affichage des chapitres Stream avec apostrophes
 */

require_once 'config.php';

/**
 * Génération d'un ID unique de projet
 */
function generateProjectId() {
    return substr(md5(uniqid(rand(), true)), 0, 8);
}

/**
 * Récupération du titre d'une vidéo YouTube
 */
function getYouTubeTitle($videoId) {
    if (!validateYouTubeId($videoId)) {
        return 'Vidéo YouTube';
    }
    
    // Méthode 1 : oEmbed (recommandée)
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
 * NOUVEAU v2.0.0 : Construction de l'URL Stream avec timestamp
 * Génère une URL Stream complète avec paramètre nav encodé en base64
 * 
 * @param array $streamData Données Stream (base_url, full_url)
 * @param int $timeInSeconds Temps en secondes
 * @return string URL avec timestamp
 */
function buildStreamUrlWithTimestamp($streamData, $timeInSeconds) {
    if (!$streamData || !isset($streamData['base_url'])) {
        return '';
    }
    
    $baseUrl = $streamData['base_url'];
    
    // Construire l'objet JSON pour le paramètre nav
    $navObject = [
        'playbackOptions' => [
            'startTimeInSeconds' => floatval($timeInSeconds),
            'timestampedLinkReferrerInfo' => [
                'scenario' => 'ChapterShare',
                'additionalInfo' => [
                    'isSharedChapterAuto' => false
                ]
            ]
        ],
        'referralInfo' => [
            'referralApp' => 'StreamWebApp',
            'referralView' => 'ShareChapterLink',
            'referralAppPlatform' => 'Web',
            'referralMode' => 'view'
        ]
    ];
    
    // Encoder en JSON puis en Base64
    $navJson = json_encode($navObject);
    $navEncoded = base64_encode($navJson);
    
    // Construire l'URL complète
    $url = $baseUrl . '?nav=' . urlencode($navEncoded);
    
    // Ajouter les autres paramètres si présents dans l'URL originale
    if (isset($streamData['full_url'])) {
        if (preg_match('/[?&]email=([^&]+)/', $streamData['full_url'], $matches)) {
            $url .= '&email=' . urlencode($matches[1]);
        }
        if (preg_match('/[?&]e=([^&]+)/', $streamData['full_url'], $matches)) {
            $url .= '&e=' . urlencode($matches[1]);
        }
    }
    
    return $url;
}

/**
 * MODIFIÉ v2.0.3 : Chargement sécurisé des données d'un projet avec support Stream
 * Utilise validateLoadedChapter() au lieu de sanitizeChapter() pour éviter double encodage
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
    
    // MODIFIÉ v2.0.3 : Validation sans re-sanitization pour éviter double encodage
    $cleanData = [
        'video_type' => in_array($data['video_type'] ?? '', [VIDEO_TYPE_YOUTUBE, VIDEO_TYPE_STREAM]) ? 
                       $data['video_type'] : VIDEO_TYPE_YOUTUBE,
        'video_id' => sanitize($data['video_id'] ?? ''),
        'video_title' => sanitize($data['video_title'] ?? ''),
        'chapters' => array_values(array_filter(array_map('validateLoadedChapter', $data['chapters'] ?? []))),
        'created_at' => sanitize($data['created_at'] ?? ''),
        'updated_at' => sanitize($data['updated_at'] ?? '')
    ];
    
    // NOUVEAU v2.0.0 : Pour Stream, ajouter les données spécifiques
    if ($cleanData['video_type'] === VIDEO_TYPE_STREAM && isset($data['stream_data'])) {
        $cleanData['stream_data'] = [
            'unique_id' => sanitize($data['stream_data']['unique_id'] ?? ''),
            'full_url' => sanitize($data['stream_data']['full_url'] ?? ''),
            'base_url' => sanitize($data['stream_data']['base_url'] ?? ''),
            'embed_url' => sanitize($data['stream_data']['embed_url'] ?? '')
        ];
    }
    
    return $cleanData;
}

/**
 * MODIFIÉ v2.0.3 : Sauvegarde sécurisée des données d'un projet avec support Stream
 * Les chapitres sont déjà sanitizés dans ajax-handler.php, on ne les re-sanitize PAS ici
 * 
 * @param string $projectId ID du projet
 * @param string $videoType Type de vidéo (youtube/stream)
 * @param string $videoId ID de la vidéo
 * @param string $videoTitle Titre de la vidéo
 * @param array $chapters Tableau des chapitres (DÉJÀ SANITIZÉS)
 * @param array|null $streamData Données Stream (uniquement si videoType = stream)
 */
function saveChapterData($projectId, $videoType, $videoId, $videoTitle, $chapters, $streamData = null) {
    // Validations
    if (!validateProjectId($projectId)) {
        throw new Exception('ID de projet invalide');
    }
    
    // Validation du type de vidéo
    if (!in_array($videoType, [VIDEO_TYPE_YOUTUBE, VIDEO_TYPE_STREAM])) {
        throw new Exception('Type de vidéo invalide');
    }
    
    // Validation selon le type
    if ($videoType === VIDEO_TYPE_YOUTUBE && !validateYouTubeId($videoId)) {
        throw new Exception('ID de vidéo YouTube invalide');
    }
    
    if ($videoType === VIDEO_TYPE_STREAM && !validateStreamId($videoId)) {
        throw new Exception('ID de vidéo Stream invalide');
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
    
    // Vérification du chemin
    if (!isSecurePath($filepath, DATA_DIR)) {
        throw new Exception('Chemin invalide');
    }
    
    // Charger les données existantes pour conserver la date de création
    $existingData = loadChapterData($projectId);
    
    // MODIFIÉ v2.0.3 : Préparer les données SANS re-sanitizer les chapitres
    $data = [
        'video_type' => $videoType,
        'video_id' => $videoId,
        'video_title' => mb_substr(sanitize($videoTitle), 0, 500),
        'chapters' => $chapters, // IMPORTANT : Déjà sanitizés dans ajax-handler.php
        'created_at' => $existingData['created_at'] ?? date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Ajouter les données Stream si nécessaire
    if ($videoType === VIDEO_TYPE_STREAM && $streamData) {
        $data['stream_data'] = [
            'unique_id' => sanitize($streamData['unique_id']),
            'full_url' => sanitize($streamData['full_url']),
            'base_url' => sanitize($streamData['base_url']),
            'embed_url' => sanitize($streamData['embed_url'] ?? '')
        ];
    }
    
    // Sauvegarder
    $result = file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    if ($result === false) {
        throw new Exception('Erreur lors de la sauvegarde');
    }
    
    return true;
}

/**
 * MODIFIÉ v2.0.0 : Récupération sécurisée de tous les projets avec support Stream
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
                'video_type' => $data['video_type'] ?? VIDEO_TYPE_YOUTUBE,
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
 * Chargement sécurisé des élus depuis le CSV
 */
function loadElus() {
    if (!file_exists(ELUS_FILE)) {
        return [];
    }
    
    $elus = [];
    $handle = fopen(ELUS_FILE, 'r');
    
    if ($handle) {
        // Lire l'en-tête
        $header = fgetcsv($handle, 1000, ';');
        if (!$header) {
            fclose($handle);
            return [];
        }
        
        // Trouver les indices des colonnes
        $nomIndex = array_search('nom', array_map('trim', $header));
        $fonctionIndex = array_search('fonction', array_map('trim', $header));
        $groupeIndex = array_search('groupe', array_map('trim', $header));
        $descriptionIndex = array_search('description', array_map('trim', $header));
        
        // Lire les données
        while (($data = fgetcsv($handle, 1000, ';')) !== false) {
            if ($nomIndex !== false && isset($data[$nomIndex])) {
                // Convertir depuis Windows-1252 vers UTF-8
                $nom = mb_convert_encoding(trim($data[$nomIndex]), 'UTF-8', 'Windows-1252');
                
                if (!empty($nom)) {
                    $elus[] = [
                        'nom' => $nom,
                        'fonction' => $fonctionIndex !== false && isset($data[$fonctionIndex]) ?
                                     mb_convert_encoding(trim($data[$fonctionIndex]), 'UTF-8', 'Windows-1252') : '',
                        'groupe' => $groupeIndex !== false && isset($data[$groupeIndex]) ?
                                   mb_convert_encoding(trim($data[$groupeIndex]), 'UTF-8', 'Windows-1252') : '',
                        'description' => $descriptionIndex !== false && isset($data[$descriptionIndex]) ?
                                        mb_convert_encoding(trim($data[$descriptionIndex]), 'UTF-8', 'Windows-1252') : ''
                    ];
                }
            }
        }
        
        fclose($handle);
    }
    
    return $elus;
}

/**
 * MODIFIÉ v2.0.3 : Création du fichier viewer.php s'il n'existe pas (avec support Stream)
 * Correction : Pas d'escapeHtml() pour éviter double encodage
 */
function createViewerFile() {
    if (file_exists('viewer.php')) {
        return;
    }
    
    $viewerContent = '<?php
/**
 * Viewer - Visionneuse de chapitres
 * Version 2.0.3 avec support YouTube et Microsoft Stream + autoplay
 * CORRECTION : Utilisation de embed_url + pas de double encodage HTML
 */

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

$videoType = $data["video_type"] ?? VIDEO_TYPE_YOUTUBE;
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
                            src="<?php echo htmlspecialchars($streamData[\'embed_url\']); ?>"
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
            \'type\' => $videoType,
            \'id\' => $videoId,
            \'streamData\' => $streamData
        ]); ?>;
        const chapters = <?php echo json_encode($chapters); ?>;
        let player;

        // Charger le script approprié selon le type
        if (videoType === \'youtube\') {
            // API YouTube
            window.onYouTubeIframeAPIReady = function() {
                player = new YT.Player(\'video-player\', {
                    events: {
                        \'onReady\': onPlayerReady
                    }
                });
            };
            
            // Charger l\'API YouTube
            const tag = document.createElement(\'script\');
            tag.src = "https://www.youtube.com/iframe_api";
            const firstScriptTag = document.getElementsByTagName(\'script\')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }

        function onPlayerReady(event) {
            displayChapters();
        }

        function displayChapters() {
            const list = document.getElementById(\'chapters-list\');
            list.innerHTML = \'\';
            
            chapters.forEach((chapter, index) => {
                const div = document.createElement(\'div\');
                div.className = \'chapter-item chapter-\' + (chapter.type || \'chapitre\');
                div.innerHTML = `
                    <span class="chapter-time">${formatTime(chapter.time)}</span>
                    <span class="chapter-title">${chapter.title}</span>
                `;
                div.onclick = () => navigateToTime(chapter.time);
                list.appendChild(div);
            });
        }

        function navigateToTime(seconds) {
            console.log(\'Navigation vers:\', seconds, \'secondes - Type:\', videoType);
            
            if (videoType === \'youtube\' && player && player.seekTo) {
                player.seekTo(seconds, true);
                if (player.playVideo) {
                    player.playVideo();
                }
            } else if (videoType === \'stream\') {
                // Navigation Stream avec embed_url + autoplay
                const iframe = document.getElementById(\'video-player\');
                if (!iframe) {
                    console.error(\'Iframe non trouvé\');
                    return;
                }
                
                if (!videoData.streamData) {
                    console.error(\'streamData non défini\');
                    return;
                }
                
                console.log(\'streamData viewer:\', videoData.streamData);
                
                // Construire l\'objet de navigation
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
                const embedParam = encodeURIComponent(\'{"af":true,"ust":true}\');
                
                // IMPORTANT : Utiliser embed_url, pas base_url
                let newUrl = videoData.streamData.embed_url;
                
                // Ajouter nav
                if (newUrl.includes(\'?\')) {
                    newUrl += \'&nav=\' + encodeURIComponent(navEncoded);
                } else {
                    newUrl += \'?nav=\' + encodeURIComponent(navEncoded);
                }
                
                // Ajouter embed pour autoplay
                newUrl += \'&embed=\' + embedParam;
                
                // Ajouter ga=1 si absent
                if (!newUrl.includes(\'ga=\')) {
                    newUrl += \'&ga=1\';
                }
                
                console.log(\'Nouvelle URL viewer:\', newUrl);
                iframe.src = newUrl;
            }
        }

        function formatTime(totalSeconds) {
            totalSeconds = Math.max(0, Math.floor(totalSeconds));
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            
            if (hours > 0) {
                return `${hours}:${minutes.toString().padStart(2, \'0\')}:${seconds.toString().padStart(2, \'0\')}`;
            }
            return `${minutes}:${seconds.toString().padStart(2, \'0\')}`;
        }

        // Afficher les chapitres dès le chargement si ce n\'est pas YouTube
        if (videoType !== \'youtube\') {
            document.addEventListener(\'DOMContentLoaded\', displayChapters);
        }
    </script>
</body>
</html>';

    file_put_contents('viewer.php', $viewerContent);
}