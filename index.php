<?php
/**
 * Chapter Studio - Interface principale
 * Version 2.0.0 - Ajout support Microsoft Stream
 * 
 * CHANGELOG v2.0.0 :
 * - Ajout détection automatique YouTube/Stream via detectVideoType()
 * - Ajout traitement formulaire pour URLs Stream
 * - Ajout conversion automatique stream.aspx → embed.aspx
 * - Ajout variables $videoType et $streamData
 * - Ajout vidéos Stream dans la grille des projets
 * - Support transparent : même interface pour YouTube et Stream
 */

require_once 'config.php';
require_once 'functions.php';

// Chargement des élus
$elus = loadElus();

// MODIFIÉ v2.0.0 : Initialisation des variables avec support Stream
$videoType = VIDEO_TYPE_YOUTUBE; // NOUVEAU v2.0.0
$videoId = '';
$videoTitle = '';
$chapters = [];
$projectId = null;
$shareUrl = '';
$embedCode = '';
$error = '';
$streamData = null; // NOUVEAU v2.0.0

// Récupération sécurisée de l'ID de projet
if (isset($_GET['p'])) {
    $projectId = sanitize($_GET['p']);
    if (!validateProjectId($projectId)) {
        $error = 'ID de projet invalide';
        $projectId = null;
    }
}

// Construction de l'URL de base
$baseUrl = getBaseUrl();

// Gestion du nouveau projet
if (isset($_GET['new'])) {
    unset($_SESSION['video_type']); // NOUVEAU v2.0.0
    unset($_SESSION['video_id']);
    unset($_SESSION['project_id']);
    unset($_SESSION['stream_data']); // NOUVEAU v2.0.0
    header('Location: index.php');
    exit;
}

// MODIFIÉ v2.0.0 : Chargement d'un projet existant avec support Stream
if ($projectId && !$error) {
    $data = loadChapterData($projectId);
    if ($data) {
        $videoType = $data['video_type'] ?? VIDEO_TYPE_YOUTUBE; // NOUVEAU v2.0.0
        $videoId = $data['video_id'];
        $videoTitle = $data['video_title'] ?? ($videoType === VIDEO_TYPE_YOUTUBE ? getYouTubeTitle($videoId) : 'Vidéo Stream'); // MODIFIÉ v2.0.0
        $chapters = $data['chapters'];
        $streamData = $data['stream_data'] ?? null; // NOUVEAU v2.0.0
        
        $_SESSION['video_type'] = $videoType; // NOUVEAU v2.0.0
        $_SESSION['video_id'] = $videoId;
        $_SESSION['video_title'] = $videoTitle;
        $_SESSION['project_id'] = $projectId;
        
        if ($streamData) { // NOUVEAU v2.0.0
            $_SESSION['stream_data'] = $streamData;
        }
        
        // Générer les URLs pour un projet existant
        $shareUrl = $baseUrl . "/index.php?p=" . $projectId;
        $embedUrl = $baseUrl . "/viewer.php?p=" . $projectId;
        $embedCode = '<iframe src="' . htmlspecialchars($embedUrl) . '" width="100%" height="600" frameborder="0" allowfullscreen></iframe>';
    } else {
        $error = 'Projet non trouvé';
        $projectId = null;
    }
}

// MODIFIÉ v2.0.0 : Traitement du formulaire avec support Stream
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['youtube_url'])) {
    // Validation CSRF
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = 'Erreur de sécurité. Veuillez recharger la page.';
    } else {
        $url = $_POST['youtube_url'];
        
        // NOUVEAU v2.0.0 : Détection automatique du type
        $detectedType = detectVideoType($url);
        
        if ($detectedType === VIDEO_TYPE_YOUTUBE) {
            // Traitement YouTube (code original)
            $videoId = validateYouTubeUrl($url);
            if ($videoId) {
                $videoType = VIDEO_TYPE_YOUTUBE; // NOUVEAU v2.0.0
                $videoTitle = getYouTubeTitle($videoId);
                $_SESSION['video_type'] = $videoType; // NOUVEAU v2.0.0
                $_SESSION['video_id'] = $videoId;
                $_SESSION['video_title'] = $videoTitle;
                unset($_SESSION['project_id']);
                unset($_SESSION['stream_data']); // NOUVEAU v2.0.0
                $chapters = [];
                $projectId = null;
                $streamData = null; // NOUVEAU v2.0.0
            } else {
                $error = 'URL YouTube invalide';
            }
        } elseif ($detectedType === VIDEO_TYPE_STREAM) {
            // NOUVEAU v2.0.0 : Traitement Stream avec conversion automatique
            $streamInfo = validateStreamUrl($url);
            if ($streamInfo) {
                $videoType = VIDEO_TYPE_STREAM;
                $videoId = $streamInfo['unique_id'];
                
                // L'URL d'embed est générée automatiquement par validateStreamUrl()
                $streamData = [
                    'unique_id' => $streamInfo['unique_id'],
                    'full_url' => $streamInfo['full_url'],
                    'base_url' => $streamInfo['base_url'],
                    'embed_url' => $streamInfo['embed_url']
                ];
                
                // Titre automatique depuis le nom du fichier
                $videoTitle = getStreamTitleFromData($streamInfo);
                
                $_SESSION['video_type'] = $videoType;
                $_SESSION['video_id'] = $videoId;
                $_SESSION['video_title'] = $videoTitle;
                $_SESSION['stream_data'] = $streamData;
                unset($_SESSION['project_id']);
                $chapters = [];
                $projectId = null;
            } else {
                $error = 'URL Microsoft Stream invalide';
            }
        } else {
            // NOUVEAU v2.0.0
            $error = 'URL non reconnue. Veuillez fournir une URL YouTube ou Microsoft Stream valide.';
        }
    }
} elseif (isset($_SESSION['video_id']) && !$projectId) {
    // MODIFIÉ v2.0.0 : Chargement depuis la session avec support Stream
    $videoType = $_SESSION['video_type'] ?? VIDEO_TYPE_YOUTUBE; // NOUVEAU v2.0.0
    $videoId = $_SESSION['video_id'];
    
    if ($videoType === VIDEO_TYPE_YOUTUBE && validateYouTubeId($videoId)) {
        $videoTitle = $_SESSION['video_title'] ?? getYouTubeTitle($videoId);
        if (isset($_SESSION['project_id'])) {
            $projectId = $_SESSION['project_id'];
            if (validateProjectId($projectId)) {
                $data = loadChapterData($projectId);
                if ($data) {
                    $chapters = $data['chapters'];
                    $videoTitle = $data['video_title'] ?? $videoTitle;
                    // Générer les URLs
                    $shareUrl = $baseUrl . "/index.php?p=" . $projectId;
                    $embedUrl = $baseUrl . "/viewer.php?p=" . $projectId;
                    $embedCode = '<iframe src="' . htmlspecialchars($embedUrl) . '" width="100%" height="600" frameborder="0" allowfullscreen></iframe>';
                }
            }
        }
    } elseif ($videoType === VIDEO_TYPE_STREAM && validateStreamId($videoId)) {
        // NOUVEAU v2.0.0 : Gestion Stream depuis session
        $videoTitle = $_SESSION['video_title'] ?? 'Vidéo Microsoft Stream';
        $streamData = $_SESSION['stream_data'] ?? null;
        if (isset($_SESSION['project_id'])) {
            $projectId = $_SESSION['project_id'];
            if (validateProjectId($projectId)) {
                $data = loadChapterData($projectId);
                if ($data) {
                    $chapters = $data['chapters'];
                    $videoTitle = $data['video_title'] ?? $videoTitle;
                    $streamData = $data['stream_data'] ?? $streamData;
                    $shareUrl = $baseUrl . "/index.php?p=" . $projectId;
                    $embedUrl = $baseUrl . "/viewer.php?p=" . $projectId;
                    $embedCode = '<iframe src="' . htmlspecialchars($embedUrl) . '" width="100%" height="600" frameborder="0" allowfullscreen></iframe>';
                }
            }
        }
    }
}

// Créer le fichier viewer.php s'il n'existe pas
createViewerFile();

// Token CSRF pour les formulaires
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Créateur de chapitres YouTube avec support des interventions d'élus et des votes">
    <title>Créateur de chapitres YouTube</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Styles additionnels pour la sauvegarde automatique */
        .save-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            display: none;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }
        
        .save-notification.success {
            background: #00ff00;
            color: #000;
        }
        
        .save-notification.error {
            background: #ff0000;
            color: #fff;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .char-count {
            font-size: 12px;
            color: #666;
            display: block;
            text-align: right;
            margin-top: 5px;
        }
        
        .autosave-info {
            text-align: center;
            color: #00ff00;
            font-size: 12px;
            margin-top: 10px;
            opacity: 0.8;
        }
        
        /* NOUVEAU v2.0.0 : Styles pour Stream */
        .stream-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0078d4 0%, #005a9e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4em;
        }
        
        .video-type-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            z-index: 2;
        }
        
        .video-type-badge.youtube {
            background: #ff0000;
            color: white;
        }
        
        .video-type-badge.stream {
            background: #0078d4;
            color: white;
        }
        
        .stream-notice {
            background: #0078d4;
            color: white;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Créateur de Chapitres YouTube</h1>
            <p>Ajoutez facilement des chapitres, interventions d'élus et votes à vos vidéos YouTube</p>
        </div>

        <div id="save-notification" class="save-notification"></div>

        <?php if ($error): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="url-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <div class="form-group">
                <!-- MODIFIÉ v2.0.0 : Label et description mis à jour -->
                <label for="youtube_url">URL de la vidéo (YouTube ou Microsoft Stream)</label>
                <input type="url" 
                       id="youtube_url" 
                       name="youtube_url" 
                       placeholder="https://www.youtube.com/watch?v=... ou https://...sharepoint.com/..." 
                       value=""
                       required
                       maxlength="500">
                <small class="help-text">
                    Formats acceptés : YouTube (youtube.com, youtu.be) ou Microsoft Stream (sharepoint.com)<br>
                    <strong>Stream :</strong> Collez simplement l'URL depuis la barre d'adresse - la conversion est automatique !
                </small>
            </div>
            <button type="submit" class="btn">Charger la vidéo</button>
            <?php if ($projectId): ?>
            <a href="index.php?new=1" class="btn btn-secondary ml-10">🔄 Nouveau projet</a>
            <div class="project-info">
                <strong>🔗 Projet actuel :</strong> <?php echo htmlspecialchars($projectId); ?><br>
                <div class="flex-center gap-10 mt-5">
                    <small>URL : </small>
                    <a href="<?php echo htmlspecialchars($shareUrl); ?>" class="project-link" target="_blank"><?php echo htmlspecialchars($shareUrl); ?></a>
                    <button type="button" class="btn-copy" onclick="copyProjectUrl('<?php echo htmlspecialchars($shareUrl); ?>')">📋</button>
                </div>
            </div>
            <?php endif; ?>
        </form>

        <?php if ($videoId): // MODIFIÉ v2.0.0 : Support YouTube ET Stream ?>
        <div class="main-content">
            <div class="video-container">
                <div class="video-wrapper">
                    <?php if ($videoType === VIDEO_TYPE_YOUTUBE): ?>
                        <!-- Code YouTube original -->
                        <iframe id="youtube-player"
                                src="https://www.youtube.com/embed/<?php echo htmlspecialchars($videoId); ?>?enablejsapi=1"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen>
                        </iframe>
                    <?php elseif ($videoType === VIDEO_TYPE_STREAM && $streamData): ?>
                        <!-- NOUVEAU v2.0.0 : Player Stream -->
                        <iframe id="stream-player"
                                src="<?php echo htmlspecialchars($streamData['embed_url']); ?>"
                                width="100%" 
                                height="100%" 
                                frameborder="0" 
                                scrolling="no" 
                                allowfullscreen>
                        </iframe>
                        <div class="stream-notice">
                            ℹ️ <strong>Mode Stream :</strong> La capture automatique du temps n'est pas disponible. Saisissez les timestamps manuellement.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="video-info">
                    <?php if ($videoType === VIDEO_TYPE_YOUTUBE): ?>
                        <img src="https://img.youtube.com/vi/<?php echo htmlspecialchars($videoId); ?>/default.jpg" 
                             alt="Miniature" 
                             class="video-mini-thumbnail"
                             onerror="this.style.display='none'">
                    <?php endif; ?>
                    <div class="video-info-text">
                        <h3 class="video-main-title"><?php echo htmlspecialchars($videoTitle); ?></h3>
                        <!-- MODIFIÉ v2.0.0 : Ajout du type de vidéo -->
                        <span class="video-id-info">
                            <?php if ($videoType === VIDEO_TYPE_YOUTUBE): ?>
                                📺 YouTube -
                            <?php else: ?>
                                📹 Stream -
                            <?php endif; ?>
                            ID: <?php echo htmlspecialchars($videoId); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="chapters-panel">
                <div class="chapters-header">
                    <h2>Chapitres</h2>
                    <div class="chapters-header-actions">
                        <span id="chapter-count">0 chapitre(s)</span>
                        <button type="button" class="btn btn-secondary btn-small" onclick="toggleTimeShift()">
                            ⏱️ Décaler
                        </button>
                    </div>
                </div>

                <!-- Module de décalage temporel (code original inchangé) -->
                <div id="time-shift-module" class="time-shift-module" style="display: none;">
                    <div class="time-shift-header">
                        <h3>🔄 Décaler les chapitres dans le temps</h3>
                        <button type="button" class="btn-close" onclick="toggleTimeShift()">✕</button>
                    </div>
                    <div class="time-shift-content">
                        <p class="time-shift-info">
                            Utile après avoir coupé le début d'un live ou modifié le montage.
                        </p>
                        
                        <div class="form-group">
                            <label>Décalage (en secondes)</label>
                            <div class="time-shift-input-group">
                                <button type="button" class="btn btn-secondary btn-small" onclick="adjustShiftTime(-10)">-10s</button>
                                <button type="button" class="btn btn-secondary btn-small" onclick="adjustShiftTime(-5)">-5s</button>
                                <input type="number" id="shift-seconds" value="-5" step="1" min="-86400" max="86400" />
                                <button type="button" class="btn btn-secondary btn-small" onclick="adjustShiftTime(5)">+5s</button>
                                <button type="button" class="btn btn-secondary btn-small" onclick="adjustShiftTime(10)">+10s</button>
                            </div>
                            <small class="help-text">Négatif pour reculer, positif pour avancer</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Appliquer à</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="shift-mode" value="all" checked onchange="toggleShiftMode()">
                                    <span>Tous les chapitres</span>
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="shift-mode" value="from" onchange="toggleShiftMode()">
                                    <span>À partir d'un chapitre</span>
                                </label>
                            </div>
                        </div>
                        
                        <div id="shift-from-chapter" class="form-group" style="display: none;">
                            <label>À partir du chapitre</label>
                            <select id="shift-start-chapter" class="form-select">
                                <!-- Options remplies dynamiquement -->
                            </select>
                        </div>
                        
                        <div class="shift-preview" id="shift-preview" style="display: none;">
                            <h4>Aperçu des changements :</h4>
                            <div id="shift-preview-content"></div>
                        </div>
                        
                        <div class="time-shift-actions">
                            <button type="button" class="btn btn-secondary" onclick="previewTimeShift()">
                                👁️ Aperçu
                            </button>
                            <button type="button" class="btn" onclick="applyTimeShift()">
                                ✅ Appliquer le décalage
                            </button>
                        </div>
                    </div>
                </div>

                <?php include 'chapter-form.php'; ?>

                <div class="chapters-list" id="chapters-list"></div>

                <div class="autosave-info">
                    ⚡ Sauvegarde automatique activée
                </div>
            </div>
        </div>

        <?php if ($shareUrl || $projectId): ?>
        <div class="share-section">
            <h3>🔗 Liens de partage</h3>
            <div class="form-group">
                <label>Lien d'édition (pour modifier les chapitres)</label>
                <div class="share-group">
                    <input type="text" class="share-input flex-1" value="<?php echo htmlspecialchars($shareUrl); ?>" readonly onclick="this.select()">
                    <button type="button" class="btn btn-secondary" onclick="copyProjectUrl('<?php echo htmlspecialchars($shareUrl); ?>')">📋 Copier</button>
                </div>
                <small class="share-link-info">
                    <a href="<?php echo htmlspecialchars($shareUrl); ?>" target="_blank">Ouvrir dans un nouvel onglet →</a>
                </small>
            </div>
            <div class="form-group">
                <label>Code d'intégration (iframe)</label>
                <div class="share-group">
                    <textarea class="share-input flex-1" rows="3" readonly onclick="this.select()"><?php echo htmlspecialchars($embedCode); ?></textarea>
                    <button type="button" class="btn btn-secondary" onclick="copyEmbedCode()">📋 Copier</button>
                </div>
            </div>
            <button class="btn btn-secondary" onclick="window.open('<?php echo htmlspecialchars($baseUrl . "/viewer.php?p=" . $projectId); ?>', '_blank')">
                👁️ Prévisualiser l'iframe
            </button>
        </div>
        <?php endif; ?>

        <div class="export-section">
            <h3>Export pour description YouTube</h3>
            <p>Copiez ce texte dans la description de votre vidéo YouTube :</p>
            <textarea class="export-textarea" id="export-text" readonly></textarea>
            <button class="btn btn-secondary mt-15" onclick="copyToClipboard()">
                📋 Copier dans le presse-papier
            </button>
        </div>

        <?php else: ?>
        <div class="no-video">
            <h2>Aucune vidéo chargée</h2>
            <!-- MODIFIÉ v2.0.0 : Texte mis à jour -->
            <p>Entrez l'URL d'une vidéo YouTube ou Microsoft Stream pour commencer</p>
            
            <div class="instructions-box">
                <h3>📖 Comment utiliser cette application ?</h3>
                <ol>
                    <!-- MODIFIÉ v2.0.0 : Instructions mises à jour -->
                    <li><strong>Créer un nouveau projet :</strong> Collez l'URL d'une vidéo YouTube ou Stream et cliquez sur "Charger la vidéo"</li>
                    <li><strong>Ajouter des chapitres :</strong> 
                        <ul>
                            <li>YouTube : Utilisez le bouton "Capturer le temps actuel" ou saisissez manuellement</li>
                            <li>Stream : Saisissez manuellement les timestamps</li>
                            <li>Choisissez entre : Chapitre, Élu ou Vote</li>
                            <li>Pour les élus, utilisez la recherche par autocomplétion</li>
                        </ul>
                    </li>
                    <li><strong>Décaler les chapitres :</strong> Utilisez le bouton "⏱️ Décaler" pour ajuster tous vos timestamps après montage</li>
                    <li><strong>Sauvegarde automatique :</strong> Vos chapitres sont sauvegardés automatiquement à chaque modification</li>
                    <li><strong>Partager :</strong> Copiez le lien d'édition ou le code iframe</li>
                    <li><strong>Rouvrir un projet :</strong> Utilisez le lien format <code>index.php?p=XXXXXXXX</code></li>
                </ol>
            </div>
        </div>
        
        <?php 
        $allProjects = getAllProjects();
        if (count($allProjects) > 0): 
        ?>
        <div class="projects-list-section">
            <h3>📂 Projets existants</h3>
            <div class="projects-grid">
                <?php foreach ($allProjects as $project): ?>
                <div class="project-card">
                    <a href="index.php?p=<?php echo htmlspecialchars($project['id']); ?>" class="project-thumbnail-link">
                        <div class="project-thumbnail">
                            <?php if ($project['video_type'] === VIDEO_TYPE_YOUTUBE): ?>
                                <!-- Miniature YouTube -->
                                <img src="https://img.youtube.com/vi/<?php echo htmlspecialchars($project['video_id']); ?>/mqdefault.jpg" 
                                     alt="Miniature de la vidéo" 
                                     loading="lazy"
                                     onerror="this.onerror=null; this.src='https://img.youtube.com/vi/<?php echo htmlspecialchars($project['video_id']); ?>/default.jpg';">
                                <span class="video-type-badge youtube">YouTube</span>
                            <?php else: ?>
                                <!-- NOUVEAU v2.0.0 : Placeholder Stream -->
                                <div class="stream-placeholder">📹</div>
                                <span class="video-type-badge stream">Stream</span>
                            <?php endif; ?>
                            <div class="chapters-overlay">
                                <span><?php echo intval($project['chapters_count']); ?> chapitre(s)</span>
                            </div>
                            <div class="play-overlay">
                                <div class="play-button">▶</div>
                            </div>
                        </div>
                    </a>
                    <div class="project-card-content">
                        <div class="project-card-header">
                            <span class="project-id">ID: <?php echo htmlspecialchars($project['id']); ?></span>
                        </div>
                        <div class="project-card-body">
                            <h4 class="video-title"><?php echo htmlspecialchars($project['video_title']); ?></h4>
                            <p class="video-id">ID vidéo: <?php echo htmlspecialchars($project['video_id']); ?></p>
                            <p class="project-date">Modifié: <?php echo date('d/m/Y H:i', strtotime($project['updated_at'])); ?></p>
                        </div>
                        <div class="project-card-footer">
                            <a href="index.php?p=<?php echo htmlspecialchars($project['id']); ?>" class="btn btn-secondary btn-small">
                                ✏️ Éditer
                            </a>
                            <a href="<?php echo htmlspecialchars($baseUrl); ?>/viewer.php?p=<?php echo htmlspecialchars($project['id']); ?>" target="_blank" class="btn btn-secondary btn-small">
                                👁️ Voir
                            </a>
                            <button type="button" class="btn btn-secondary btn-small" onclick="copyProjectUrl('<?php echo htmlspecialchars($baseUrl); ?>/index.php?p=<?php echo htmlspecialchars($project['id']); ?>')">
                                📋 Copier
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        // MODIFIÉ v2.0.0 : Variables globales sécurisées avec support Stream
        window.appConfig = {
            videoType: <?php echo json_encode($videoType); ?>, // NOUVEAU v2.0.0
            chapters: <?php echo json_encode($chapters); ?>,
            elus: <?php echo json_encode($elus); ?>,
            projectId: <?php echo json_encode($projectId); ?>,
            videoId: <?php echo json_encode($videoId); ?>,
            videoTitle: <?php echo json_encode($videoTitle); ?>,
            csrfToken: <?php echo json_encode($csrfToken); ?>,
            maxChapters: <?php echo MAX_CHAPTERS; ?>,
            maxTitleLength: <?php echo MAX_TITLE_LENGTH; ?>,
            streamData: <?php echo json_encode($streamData); ?> // NOUVEAU v2.0.0
        };
        
        console.log('appConfig défini avant app.js:', window.appConfig);
    </script>
    
    <!-- Charger app.js APRÈS avoir défini appConfig -->
    <script src="app.js"></script>
    
    <script>
        // Vérifier et corriger après le chargement
        window.addEventListener('DOMContentLoaded', function() {
            console.log('=== Vérification après chargement ===');
            
            // Si les variables ne sont pas correctement initialisées
            if (window.appConfig.videoId && typeof currentVideoId !== 'undefined') {
                if (!currentVideoId || currentVideoId !== window.appConfig.videoId) {
                    console.warn('⚠️ Variables non synchronisées, mise à jour...');
                    
                    // Forcer la mise à jour
                    if (typeof window.updateGlobalVariables === 'function') {
                        window.updateGlobalVariables(window.appConfig);
                    }
                }
            }
            
            // Debug final
            setTimeout(function() {
                console.log('=== État final ===');
                console.log('currentVideoId:', typeof currentVideoId !== 'undefined' ? currentVideoId : 'non défini');
                console.log('currentProjectId:', typeof currentProjectId !== 'undefined' ? currentProjectId : 'non défini');
                console.log('autoSave disponible:', typeof autoSave === 'function');
            }, 500);
        });
    </script>
</body>
</html>