<?php
/**
 * Gestionnaire des requêtes AJAX
 */

require_once 'config.php';
require_once 'functions.php';

// Vérifier que c'est une requête AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit('Accès interdit');
}

// Header JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // Vérifier la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Vérifier l'action
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_chapters':
            handleSaveChapters();
            break;
            
        case 'get_youtube_title':
            handleGetYouTubeTitle();
            break;
            
        case 'test':
            echo json_encode([
                'success' => true,
                'message' => 'AJAX fonctionne correctement',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Sauvegarde des chapitres
 */
function handleSaveChapters() {
    // Validation CSRF
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        throw new Exception('Token de sécurité invalide');
    }
    
    // Validation de l'ID vidéo
    $videoId = $_POST['video_id'] ?? '';
    if (!validateYouTubeId($videoId)) {
        throw new Exception('ID vidéo invalide');
    }
    
    // Validation et nettoyage des chapitres
    $chaptersRaw = json_decode($_POST['chapters'] ?? '[]', true);
    if (!is_array($chaptersRaw)) {
        throw new Exception('Format de données invalide');
    }
    
    // Nettoyer chaque chapitre
    $chapters = array_map('sanitizeChapter', $chaptersRaw);
    
    // Limiter le nombre de chapitres
    if (count($chapters) > MAX_CHAPTERS) {
        throw new Exception('Trop de chapitres (maximum ' . MAX_CHAPTERS . ')');
    }
    
    // ID du projet
    $projectId = $_POST['project_id'] ?? null;
    if ($projectId && !validateProjectId($projectId)) {
        throw new Exception('ID de projet invalide');
    }
    
    // Si pas de projectId, en créer un nouveau
    if (!$projectId) {
        checkProjectLimit(); // Vérifier la limite de projets
        $projectId = generateProjectId();
        $_SESSION['project_count'] = ($_SESSION['project_count'] ?? 0) + 1;
        $_SESSION['project_id'] = $projectId; // Sauvegarder dans la session
    }
    
    // Récupérer le titre de la vidéo
    $videoTitle = $_POST['video_title'] ?? '';
    if (empty($videoTitle)) {
        $videoTitle = getYouTubeTitle($videoId);
    }
    $videoTitle = mb_substr(sanitize($videoTitle), 0, 500);

    // Mettre à jour les variables de session
    $_SESSION['video_id'] = $videoId;
    $_SESSION['video_title'] = $videoTitle;
    $_SESSION['project_id'] = $projectId;
    
    // Sauvegarder
    saveChapterData($projectId, $videoId, $videoTitle, $chapters);
    
    // Générer les URLs
    $baseUrl = getBaseUrl();
    
    echo json_encode([
        'success' => true,
        'project_id' => $projectId,
        'share_url' => $baseUrl . '/index.php?p=' . $projectId,
        'embed_url' => $baseUrl . '/viewer.php?p=' . $projectId
    ]);
}

/**
 * Récupération du titre YouTube
 */
function handleGetYouTubeTitle() {
    $videoId = $_POST['video_id'] ?? '';
    
    if (!validateYouTubeId($videoId)) {
        throw new Exception('ID vidéo invalide');
    }
    
    $title = getYouTubeTitle($videoId);
    
    echo json_encode([
        'success' => true,
        'title' => $title
    ]);
}