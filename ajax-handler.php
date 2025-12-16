<?php
/**
 * Gestionnaire des requêtes AJAX
 * Version 2.0.3 avec support Microsoft Stream
 * 
 * CHANGELOG v2.0.3 :
 * - Correction double sanitization des chapitres
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
 * MODIFIÉ v2.0.3 : Sauvegarde des chapitres avec support YouTube et Stream
 */
function handleSaveChapters() {
    // Validation CSRF
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        throw new Exception('Token de sécurité invalide');
    }
    
    // NOUVEAU v2.0.0 : Déterminer le type de vidéo
    $videoType = $_POST['video_type'] ?? VIDEO_TYPE_YOUTUBE;
    if (!in_array($videoType, [VIDEO_TYPE_YOUTUBE, VIDEO_TYPE_STREAM])) {
        throw new Exception('Type de vidéo invalide');
    }
    
    // MODIFIÉ v2.0.0 : Validation de l'ID vidéo selon le type
    $videoId = $_POST['video_id'] ?? '';
    
    if ($videoType === VIDEO_TYPE_YOUTUBE) {
        if (!validateYouTubeId($videoId)) {
            throw new Exception('ID vidéo YouTube invalide');
        }
    } elseif ($videoType === VIDEO_TYPE_STREAM) {
        if (!validateStreamId($videoId)) {
            throw new Exception('ID vidéo Stream invalide');
        }
    }
    
    // Validation et nettoyage des chapitres
    $chaptersRaw = json_decode($_POST['chapters'] ?? '[]', true);
    if (!is_array($chaptersRaw)) {
        throw new Exception('Format de données invalide');
    }
    
    // MODIFIÉ v2.0.3 : Nettoyer chaque chapitre ET filtrer les valeurs null
    $chapters = array_values(array_filter(array_map('sanitizeChapter', $chaptersRaw)));
    
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
        checkProjectLimit();
        $projectId = generateProjectId();
        $_SESSION['project_count'] = ($_SESSION['project_count'] ?? 0) + 1;
        $_SESSION['project_id'] = $projectId;
    }
    
    // Récupérer le titre de la vidéo
    $videoTitle = $_POST['video_title'] ?? '';
    if (empty($videoTitle)) {
        if ($videoType === VIDEO_TYPE_YOUTUBE) {
            $videoTitle = getYouTubeTitle($videoId);
        } else {
            $videoTitle = 'Vidéo Microsoft Stream';
        }
    }
    $videoTitle = mb_substr(sanitize($videoTitle), 0, 500);

    // Mettre à jour les variables de session
    $_SESSION['video_type'] = $videoType;
    $_SESSION['video_id'] = $videoId;
    $_SESSION['video_title'] = $videoTitle;
    $_SESSION['project_id'] = $projectId;
    
    // NOUVEAU v2.0.0 : Préparer les données Stream si nécessaire
    $streamData = null;
    if ($videoType === VIDEO_TYPE_STREAM) {
        $streamDataRaw = json_decode($_POST['stream_data'] ?? '{}', true);
        if (is_array($streamDataRaw)) {
            $streamData = [
                'unique_id' => sanitize($streamDataRaw['unique_id'] ?? $videoId),
                'full_url' => sanitize($streamDataRaw['full_url'] ?? ''),
                'base_url' => sanitize($streamDataRaw['base_url'] ?? ''),
                'embed_url' => sanitize($streamDataRaw['embed_url'] ?? '')
            ];
            $_SESSION['stream_data'] = $streamData;
        }
    }
    
    // MODIFIÉ v2.0.3 : Sauvegarder avec le type de vidéo (chapitres déjà sanitizés)
    saveChapterData($projectId, $videoType, $videoId, $videoTitle, $chapters, $streamData);
    
    // Générer les URLs
    $baseUrl = getBaseUrl();
    
    echo json_encode([
        'success' => true,
        'project_id' => $projectId,
        'video_type' => $videoType,
        'share_url' => $baseUrl . '/index.php?p=' . $projectId,
        'embed_url' => $baseUrl . '/viewer.php?p=' . $projectId
    ]);
}