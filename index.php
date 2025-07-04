<?php
session_start();

// Configuration du dossier de stockage
$dataDir = __DIR__ . '/chapters_data';
if (!file_exists($dataDir)) {
    mkdir($dataDir, 0777, true);
}

// Génération d'un ID unique pour le projet
function generateProjectId() {
    return substr(md5(uniqid(rand(), true)), 0, 8);
}

// Sauvegarde des données
function saveChapterData($videoId, $chapters, $projectId = null) {
    global $dataDir;
    
    if (!$projectId) {
        $projectId = generateProjectId();
    }
    
    $data = [
        'video_id' => $videoId,
        'chapters' => $chapters,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    file_put_contents($dataDir . '/' . $projectId . '.json', json_encode($data, JSON_PRETTY_PRINT));
    return $projectId;
}

// Chargement des données
function loadChapterData($projectId) {
    global $dataDir;
    $file = $dataDir . '/' . $projectId . '.json';
    
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return null;
}

// Récupération de tous les projets
function getAllProjects() {
    global $dataDir;
    $projects = [];
    
    if (is_dir($dataDir)) {
        $files = glob($dataDir . '/*.json');
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data) {
                $projectId = basename($file, '.json');
                $projects[] = [
                    'id' => $projectId,
                    'video_id' => $data['video_id'],
                    'chapters_count' => count($data['chapters']),
                    'created_at' => $data['created_at'] ?? 'Non défini',
                    'updated_at' => $data['updated_at'] ?? $data['created_at'] ?? 'Non défini'
                ];
            }
        }
    }
    
    // Trier par date de mise à jour décroissante
    usort($projects, function($a, $b) {
        return strtotime($b['updated_at']) - strtotime($a['updated_at']);
    });
    
    return $projects;
}

// Initialisation des variables
$videoId = '';
$chapters = [];
$projectId = $_GET['p'] ?? null;
$shareUrl = '';
$embedCode = '';
$savedMessage = false;

// Construction de l'URL de base
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];
$scriptPath = $_SERVER['SCRIPT_NAME'];
$baseDir = dirname($scriptPath);
$baseUrl = $protocol . "://" . $host . $baseDir;

// Gestion du nouveau projet
if (isset($_GET['new'])) {
    unset($_SESSION['video_id']);
    unset($_SESSION['project_id']);
    header('Location: index.php');
    exit;
}

// Chargement d'un projet existant
if ($projectId && $data = loadChapterData($projectId)) {
    $videoId = $data['video_id'];
    $chapters = $data['chapters'];
    $_SESSION['video_id'] = $videoId;
    $_SESSION['project_id'] = $projectId;
    
    // Générer les URLs pour un projet existant
    $shareUrl = $baseUrl . "/index.php?p=" . $projectId;
    $embedUrl = $baseUrl . "/viewer.php?p=" . $projectId;
    $embedCode = '<iframe src="' . $embedUrl . '" width="100%" height="600" frameborder="0" allowfullscreen></iframe>';
}

// Traitement des formulaires POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['youtube_url'])) {
        // Extraction de l'ID de la vidéo YouTube
        $url = $_POST['youtube_url'];
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches);
        if (isset($matches[1])) {
            $videoId = $matches[1];
            $_SESSION['video_id'] = $videoId;
            // Reset du projet si nouvelle vidéo
            unset($_SESSION['project_id']);
            $chapters = [];
            $projectId = null;
        }
    }
    
    if (isset($_POST['save_chapters']) && isset($_SESSION['video_id'])) {
        $chapters = json_decode($_POST['chapters_data'], true);
        $projectId = $_SESSION['project_id'] ?? null;
        $projectId = saveChapterData($_SESSION['video_id'], $chapters, $projectId);
        $_SESSION['project_id'] = $projectId;
        
        // Générer les URLs de partage
        $shareUrl = $baseUrl . "/index.php?p=" . $projectId;
        $embedUrl = $baseUrl . "/viewer.php?p=" . $projectId;
        $embedCode = '<iframe src="' . $embedUrl . '" width="100%" height="600" frameborder="0" allowfullscreen></iframe>';
        $savedMessage = true;
    }
} elseif (isset($_SESSION['video_id'])) {
    // Chargement depuis la session
    $videoId = $_SESSION['video_id'];
    if (isset($_SESSION['project_id'])) {
        $projectId = $_SESSION['project_id'];
        if ($data = loadChapterData($projectId)) {
            $chapters = $data['chapters'];
            // Générer les URLs pour le projet en session
            $shareUrl = $baseUrl . "/index.php?p=" . $projectId;
            $embedUrl = $baseUrl . "/viewer.php?p=" . $projectId;
            $embedCode = '<iframe src="' . $embedUrl . '" width="100%" height="600" frameborder="0" allowfullscreen></iframe>';
        }
    }
}

// Créer le fichier viewer.php s'il n'existe pas
$viewerContent = '<?php
$projectId = $_GET["p"] ?? null;
if (!$projectId) die("Projet non trouvé");

$dataDir = __DIR__ . "/chapters_data";
$file = $dataDir . "/" . $projectId . ".json";

if (!file_exists($file)) die("Projet non trouvé");

$data = json_decode(file_get_contents($file), true);
$videoId = $data["video_id"];
$chapters = $data["chapters"];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecteur avec chapitres</title>
    <link rel="stylesheet" href="viewer-styles.css">
</head>
<body>
    <div class="viewer-container">
        <div class="video-section">
            <div class="video-wrapper">
                <iframe id="youtube-player"
                        src="https://www.youtube.com/embed/<?php echo $videoId; ?>?enablejsapi=1"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                </iframe>
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

        function onYouTubeIframeAPIReady() {
            player = new YT.Player("youtube-player", {
                events: {
                    "onReady": onPlayerReady
                }
            });
        }

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
                chapterDiv.className = "chapter-item";
                chapterDiv.onclick = () => goToTime(chapter.time);
                chapterDiv.innerHTML = `
                    <div class="chapter-time">${formatTime(chapter.time)}</div>
                    <div class="chapter-title">${chapter.title}</div>
                `;
                listElement.appendChild(chapterDiv);
            });
        }
    </script>
</body>
</html>';

// Créer le fichier viewer.php s'il n'existe pas
if (!file_exists('viewer.php')) {
    file_put_contents('viewer.php', $viewerContent);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créateur de chapitres YouTube</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Créateur de Chapitres YouTube</h1>
            <p>Ajoutez facilement des chapitres à vos vidéos YouTube</p>
        </div>

        <?php if ($savedMessage): ?>
        <div class="success-message">
            ✅ Chapitres sauvegardés avec succès !
        </div>
        <?php endif; ?>

        <form method="POST" class="url-form">
            <div class="form-group">
                <label for="youtube_url">URL de la vidéo YouTube</label>
                <input type="url" 
                       id="youtube_url" 
                       name="youtube_url" 
                       placeholder="https://www.youtube.com/watch?v=..." 
                       value="<?php echo isset($_POST['youtube_url']) ? htmlspecialchars($_POST['youtube_url']) : ''; ?>"
                       required>
            </div>
            <button type="submit" class="btn">Charger la vidéo</button>
            <?php if ($projectId): ?>
            <a href="index.php?new=1" class="btn btn-secondary ml-10">📄 Nouveau projet</a>
            <div class="project-info">
                <strong>🔗 Projet actuel :</strong> <?php echo $projectId; ?><br>
                <div class="flex-center gap-10 mt-5">
                    <small>URL : </small>
                    <a href="<?php echo $shareUrl; ?>" class="project-link" target="_blank"><?php echo $shareUrl; ?></a>
                    <button type="button" class="btn-copy" onclick="copyProjectUrl('<?php echo $shareUrl; ?>')">📋</button>
                </div>
            </div>
            <?php endif; ?>
        </form>

        <?php if ($videoId): ?>
        <div class="main-content">
            <div class="video-container">
                <div class="video-wrapper">
                    <iframe id="youtube-player"
                            src="https://www.youtube.com/embed/<?php echo $videoId; ?>?enablejsapi=1"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen>
                    </iframe>
                </div>
                <div class="video-info">
                    <img src="https://img.youtube.com/vi/<?php echo $videoId; ?>/default.jpg" 
                         alt="Miniature" 
                         class="video-mini-thumbnail">
                    <span class="video-id-info">ID: <?php echo $videoId; ?></span>
                </div>
            </div>

            <div class="chapters-panel">
                <div class="chapters-header">
                    <h2>Chapitres</h2>
                    <span id="chapter-count">0 chapitre(s)</span>
                </div>

                <div class="chapter-form">
                    <div class="editing-indicator" id="editing-indicator">
                        Mode édition - Modifiez et cliquez sur "Mettre à jour"
                    </div>
                    <button type="button" class="btn btn-capture w-full" onclick="captureCurrentTime()">
                        ⏱️ Capturer le temps actuel de la vidéo
                    </button>
                    <div class="form-group">
                        <label>Temps (HH:MM:SS)</label>
                        <div class="time-input">
                            <div class="time-group">
                                <input type="text" id="hours" placeholder="00" maxlength="2">
                                <div class="time-controls">
                                    <button type="button" class="time-btn" onclick="adjustTime('hours', 1)">▲</button>
                                    <button type="button" class="time-btn" onclick="adjustTime('hours', -1)">▼</button>
                                </div>
                            </div>
                            <div class="time-group">
                                <input type="text" id="minutes" placeholder="00" maxlength="2">
                                <div class="time-controls">
                                    <button type="button" class="time-btn" onclick="adjustTime('minutes', 1)">▲</button>
                                    <button type="button" class="time-btn" onclick="adjustTime('minutes', -1)">▼</button>
                                </div>
                            </div>
                            <div class="time-group">
                                <input type="text" id="seconds" placeholder="00" maxlength="2">
                                <div class="time-controls">
                                    <button type="button" class="time-btn" onclick="adjustTime('seconds', 1)">▲</button>
                                    <button type="button" class="time-btn" onclick="adjustTime('seconds', -1)">▼</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="chapter-title">Titre du chapitre</label>
                        <input type="text" id="chapter-title" placeholder="Introduction">
                    </div>
                    <input type="hidden" id="editing-index" value="-1">
                    <button type="button" class="btn" id="action-button" onclick="addOrUpdateChapter()">Ajouter le chapitre</button>
                    <button type="button" class="btn btn-secondary d-none ml-10" id="cancel-button" onclick="cancelEdit()">Annuler</button>
                </div>

                <div class="chapters-list" id="chapters-list"></div>

                <form method="POST" id="save-form">
                    <input type="hidden" name="chapters_data" id="chapters-data">
                    <button type="submit" name="save_chapters" class="btn btn-secondary w-full mt-15">
                        💾 Sauvegarder les chapitres
                    </button>
                </form>
            </div>
        </div>

        <?php if ($shareUrl || $projectId): ?>
        <div class="share-section">
            <h3>🔗 Liens de partage</h3>
            <div class="form-group">
                <label>Lien d'édition (pour modifier les chapitres)</label>
                <div class="share-group">
                    <input type="text" class="share-input flex-1" value="<?php echo $shareUrl; ?>" readonly onclick="this.select()">
                    <button type="button" class="btn btn-secondary" onclick="copyProjectUrl('<?php echo $shareUrl; ?>')">📋 Copier</button>
                </div>
                <small class="share-link-info">
                    <a href="<?php echo $shareUrl; ?>" target="_blank">Ouvrir dans un nouvel onglet →</a>
                </small>
            </div>
            <div class="form-group">
                <label>Code d'intégration (iframe)</label>
                <div class="share-group">
                    <textarea class="share-input flex-1" rows="3" readonly onclick="this.select()"><?php echo htmlspecialchars($embedCode); ?></textarea>
                    <button type="button" class="btn btn-secondary" onclick="copyEmbedCode()">📋 Copier</button>
                </div>
            </div>
            <button class="btn btn-secondary" onclick="window.open('<?php echo $baseUrl . "/viewer.php?p=" . $projectId; ?>', '_blank')">
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
            <p>Entrez l'URL d'une vidéo YouTube pour commencer</p>
            
            <div class="instructions-box">
                <h3>📖 Comment utiliser cette application ?</h3>
                <ol>
                    <li><strong>Créer un nouveau projet :</strong> Collez l'URL d'une vidéo YouTube et cliquez sur "Charger la vidéo"</li>
                    <li><strong>Ajouter des chapitres :</strong> Utilisez le bouton "Capturer le temps actuel" ou saisissez manuellement</li>
                    <li><strong>Sauvegarder :</strong> Cliquez sur "💾 Sauvegarder les chapitres" pour générer un lien partageable</li>
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
                    <a href="index.php?p=<?php echo $project['id']; ?>" class="project-thumbnail-link">
                        <div class="project-thumbnail">
                            <img src="https://img.youtube.com/vi/<?php echo $project['video_id']; ?>/mqdefault.jpg" 
                                 alt="Miniature de la vidéo" 
                                 onerror="this.onerror=null; this.src='https://img.youtube.com/vi/<?php echo $project['video_id']; ?>/default.jpg'; 
                                          if(this.naturalWidth===120 && this.naturalHeight===90) { 
                                              this.style.display='none'; 
                                              this.parentElement.classList.add('no-thumbnail');
                                          }">
                            <div class="chapters-overlay">
                                <span><?php echo $project['chapters_count']; ?> chapitre(s)</span>
                            </div>
                            <div class="play-overlay">
                                <div class="play-button">▶</div>
                            </div>
                        </div>
                    </a>
                    <div class="project-card-content">
                        <div class="project-card-header">
                            <span class="project-id">ID: <?php echo $project['id']; ?></span>
                        </div>
                        <div class="project-card-body">
                            <p class="video-id">Vidéo: <?php echo $project['video_id']; ?></p>
                            <p class="project-date">Modifié: <?php echo date('d/m/Y H:i', strtotime($project['updated_at'])); ?></p>
                        </div>
                        <div class="project-card-footer">
                            <a href="index.php?p=<?php echo $project['id']; ?>" class="btn btn-secondary btn-small">
                                ✏️ Éditer
                            </a>
                            <a href="<?php echo $baseUrl; ?>/viewer.php?p=<?php echo $project['id']; ?>" target="_blank" class="btn btn-secondary btn-small">
                                👁️ Voir
                            </a>
                            <button type="button" class="btn btn-secondary btn-small" onclick="copyProjectUrl('<?php echo $baseUrl; ?>/index.php?p=<?php echo $project['id']; ?>')">
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
        // Variables globales
        let chapters = <?php echo json_encode($chapters); ?>;
        let player;
        let playerReady = false;
        let editingIndex = -1;

        // Initialisation de l'API YouTube
        function onYouTubeIframeAPIReady() {
            player = new YT.Player('youtube-player', {
                events: {
                    'onReady': onPlayerReady
                }
            });
        }

        function onPlayerReady(event) {
            playerReady = true;
        }

        // Charger l'API YouTube
        const tag = document.createElement('script');
        tag.src = "https://www.youtube.com/iframe_api";
        const firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

        // Fonctions utilitaires
        function formatTime(totalSeconds) {
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            
            if (hours > 0) {
                return `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
            return `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }

        function parseTime(hours, minutes, seconds) {
            return parseInt(hours || 0) * 3600 + parseInt(minutes || 0) * 60 + parseInt(seconds || 0);
        }

        // Gestion du temps
        function captureCurrentTime() {
            if (playerReady && player && player.getCurrentTime) {
                const currentTime = Math.floor(player.getCurrentTime());
                const hours = Math.floor(currentTime / 3600);
                const minutes = Math.floor((currentTime % 3600) / 60);
                const seconds = currentTime % 60;
                
                document.getElementById('hours').value = hours.toString().padStart(2, '0');
                document.getElementById('minutes').value = minutes.toString().padStart(2, '0');
                document.getElementById('seconds').value = seconds.toString().padStart(2, '0');
            } else {
                alert('La vidéo doit être en cours de lecture pour capturer le temps');
            }
        }

        function adjustTime(field, increment) {
            const input = document.getElementById(field);
            let value = parseInt(input.value || 0) + increment;
            
            if (field === 'hours') {
                value = Math.max(0, Math.min(99, value));
            } else {
                value = Math.max(0, Math.min(59, value));
            }
            
            input.value = value.toString().padStart(2, '0');
        }

        // Gestion des chapitres
        function editChapter(index) {
            const chapter = chapters[index];
            editingIndex = index;
            
            // Convertir le temps en HH:MM:SS
            const hours = Math.floor(chapter.time / 3600);
            const minutes = Math.floor((chapter.time % 3600) / 60);
            const seconds = chapter.time % 60;
            
            // Remplir le formulaire
            document.getElementById('hours').value = hours.toString().padStart(2, '0');
            document.getElementById('minutes').value = minutes.toString().padStart(2, '0');
            document.getElementById('seconds').value = seconds.toString().padStart(2, '0');
            document.getElementById('chapter-title').value = chapter.title;
            
            // Changer l'interface pour le mode édition
            document.getElementById('action-button').textContent = 'Mettre à jour';
            document.getElementById('cancel-button').classList.remove('d-none');
            document.getElementById('editing-indicator').style.display = 'block';
            document.getElementById('editing-index').value = index;
            
            // Mettre en surbrillance le chapitre en cours d'édition
            updateChaptersList();
            
            // Scroll vers le formulaire
            document.querySelector('.chapter-form').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function cancelEdit() {
            editingIndex = -1;
            document.getElementById('hours').value = '';
            document.getElementById('minutes').value = '';
            document.getElementById('seconds').value = '';
            document.getElementById('chapter-title').value = '';
            document.getElementById('action-button').textContent = 'Ajouter le chapitre';
            document.getElementById('cancel-button').classList.add('d-none');
            document.getElementById('editing-indicator').style.display = 'none';
            document.getElementById('editing-index').value = '-1';
            updateChaptersList();
        }

        function addOrUpdateChapter() {
            const hours = document.getElementById('hours').value || '0';
            const minutes = document.getElementById('minutes').value || '0';
            const seconds = document.getElementById('seconds').value || '0';
            const title = document.getElementById('chapter-title').value.trim();

            if (!title) {
                alert('Veuillez entrer un titre pour le chapitre');
                return;
            }

            const totalSeconds = parseTime(hours, minutes, seconds);
            
            if (editingIndex >= 0) {
                // Mode édition
                chapters[editingIndex] = {
                    time: totalSeconds,
                    title: title
                };
                cancelEdit();
            } else {
                // Mode ajout
                chapters.push({
                    time: totalSeconds,
                    title: title
                });
                
                // Réinitialiser le formulaire
                document.getElementById('hours').value = '';
                document.getElementById('minutes').value = '';
                document.getElementById('seconds').value = '';
                document.getElementById('chapter-title').value = '';
            }

            // Trier les chapitres par temps
            chapters.sort((a, b) => a.time - b.time);

            updateChaptersList();
            updateExport();
        }

        function deleteChapter(index) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce chapitre ?')) {
                chapters.splice(index, 1);
                if (editingIndex === index) {
                    cancelEdit();
                }
                updateChaptersList();
                updateExport();
            }
        }

        function goToTime(seconds) {
            if (playerReady && player) {
                player.seekTo(seconds, true);
            }
        }

        // Mise à jour de l'interface
        function updateChaptersList() {
            const listElement = document.getElementById('chapters-list');
            const countElement = document.getElementById('chapter-count');
            
            listElement.innerHTML = '';
            countElement.textContent = `${chapters.length} chapitre(s)`;

            chapters.forEach((chapter, index) => {
                const chapterDiv = document.createElement('div');
                chapterDiv.className = 'chapter-item';
                if (index === editingIndex) {
                    chapterDiv.classList.add('edit-mode');
                }
                chapterDiv.innerHTML = `
                    <span class="chapter-time cursor-pointer" onclick="goToTime(${chapter.time})">
                        ${formatTime(chapter.time)}
                    </span>
                    <span class="chapter-title cursor-pointer" onclick="editChapter(${index})">
                        ${chapter.title}
                    </span>
                    <div class="chapter-actions">
                        <button class="btn btn-icon btn-secondary" onclick="editChapter(${index})">✏️</button>
                        <button class="btn btn-icon btn-secondary" onclick="deleteChapter(${index})">🗑️</button>
                    </div>
                `;
                listElement.appendChild(chapterDiv);
            });

            // Mettre à jour les données du formulaire
            document.getElementById('chapters-data').value = JSON.stringify(chapters);
        }

        function updateExport() {
            const exportText = document.getElementById('export-text');
            let text = 'Chapitres :\n';
            
            chapters.forEach(chapter => {
                text += `${formatTime(chapter.time)} ${chapter.title}\n`;
            });

            exportText.value = text;
        }

        // Fonctions de copie
        function copyProjectUrl(url) {
            const tempInput = document.createElement('input');
            tempInput.value = url;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            
            // Feedback visuel
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '✅';
            button.style.background = '#00ff00';
            button.style.color = '#000';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '';
                button.style.color = '';
            }, 1500);
        }

        function copyEmbedCode() {
            const textarea = document.querySelector('.share-section textarea');
            textarea.select();
            document.execCommand('copy');
            
            // Feedback visuel
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '✅ Copié !';
            button.style.background = '#00ff00';
            button.style.color = '#000';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '';
                button.style.color = '';
            }, 1500);
        }

        function copyToClipboard() {
            const exportText = document.getElementById('export-text');
            exportText.select();
            document.execCommand('copy');
            
            // Feedback visuel
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '✅ Copié !';
            button.style.background = '#00ff00';
            button.style.color = '#000';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '';
                button.style.color = '';
            }, 1500);
        }

        // Événements pour les champs de temps
        ['hours', 'minutes', 'seconds'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    
                    if (id === 'hours') {
                        if (value !== '' && parseInt(value) > 99) value = '99';
                    } else {
                        if (value !== '' && parseInt(value) > 59) value = '59';
                    }
                    
                    e.target.value = value;
                });

                // Permettre l'effacement complet
                element.addEventListener('focus', function(e) {
                    if (e.target.value === '00') {
                        e.target.value = '';
                    }
                });

                element.addEventListener('blur', function(e) {
                    if (e.target.value === '') {
                        e.target.value = '00';
                    }
                });
            }
        });

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            if (chapters.length > 0) {
                updateChaptersList();
                updateExport();
            }
        });
    </script>
</body>
</html>