<?php
/**
 * Configuration et sécurité - Chapter Studio
 * Version 2.0.0 - Ajout support Microsoft Stream
 * 
 * CHANGELOG v2.0.0 :
 * - Ajout constantes VIDEO_TYPE_YOUTUBE et VIDEO_TYPE_STREAM
 * - Ajout fonction validateStreamId() pour valider les IDs Stream
 * - Ajout fonction validateStreamUrl() avec conversion automatique stream.aspx → embed.aspx
 * - Ajout fonction getStreamTitleFromData() pour extraction du titre depuis le nom de fichier
 * - Ajout fonction detectVideoType() pour détection automatique YouTube/Stream
 * - Modification sanitizeChapter() pour support du type de vidéo
 */

// Démarrage de la session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Régénération périodique du token CSRF
if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
    (time() - $_SESSION['csrf_token_time']) > 3600) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Configuration des chemins
define('DATA_DIR', __DIR__ . '/chapters_data');
define('ELUS_FILE', __DIR__ . '/elus/elus.csv');

// Limites
define('MAX_CHAPTERS', 500);
define('MAX_TITLE_LENGTH', 200);
define('MAX_PROJECTS_PER_SESSION', 50);

// NOUVEAU v2.0.0 : Types de vidéos supportés
define('VIDEO_TYPE_YOUTUBE', 'youtube');
define('VIDEO_TYPE_STREAM', 'stream');

/**
 * Validation du token CSRF
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Fonction de sanitisation
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validation de l'ID YouTube
 */
function validateYouTubeId($id) {
    return preg_match('/^[a-zA-Z0-9_-]{11}$/', $id);
}

/**
 * NOUVEAU v2.0.0 : Validation de l'ID Stream (UniqueId du SharePoint ou MD5 du chemin)
 */
function validateStreamId($id) {
    // Format GUID: 762bcc03-9b92-4fcf-a66c-e8367a76fb89
    if (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $id)) {
        return true;
    }
    // Format MD5: 32 caractères hexadécimaux (pour les chemins de fichiers)
    if (preg_match('/^[a-f0-9]{32}$/i', $id)) {
        return true;
    }
    return false;
}

/**
 * Validation de l'URL YouTube
 */
function validateYouTubeUrl($url) {
    $patterns = [
        '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
        '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    
    // Si c'est directement un ID
    if (validateYouTubeId($url)) {
        return $url;
    }
    
    return false;
}

/**
 * NOUVEAU v2.0.0 : Validation et extraction d'URL Microsoft Stream
 * Supporte deux formats :
 * 1. Format /:v:/g/ avec UniqueId dans l'URL
 * 2. Format stream.aspx?id= avec chemin de fichier (CONVERSION AUTOMATIQUE)
 * 
 * @param string $url URL à valider
 * @return array|false Retourne un tableau avec les infos ou false
 */
function validateStreamUrl($url) {
    // Format 1: /:v:/g/ avec UniqueId
    // Exemple: https://tenant.sharepoint.com/:v:/g/personal/user/EQPMK3a...?nav=...
    $pattern1 = '/UniqueId=([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/i';
    
    // Format 2: stream.aspx avec chemin de fichier
    // Exemple: https://tenant.sharepoint.com/personal/user/_layouts/15/stream.aspx?id=%2Fpersonal%2F...%2Efile.mp4
    $pattern2 = '/stream\.aspx\?id=([^&]+)/';
    
    // Vérifier Format 1 (UniqueId)
    if (preg_match($pattern1, $url, $matches)) {
        // Extraire les infos de base
        preg_match('/https?:\/\/([^\/]+)/', $url, $domain);
        preg_match('/\/personal\/([^\/]+)\//', $url, $personal);
        
        if (!isset($domain[1]) || !isset($personal[1])) {
            return false;
        }
        
        $embedUrl = sprintf(
            'https://%s/personal/%s/_layouts/15/embed.aspx?UniqueId=%s',
            $domain[1],
            $personal[1],
            $matches[1]
        );
        
        return [
            'unique_id' => $matches[1],
            'full_url' => $url,
            'base_url' => preg_replace('/\?.*$/', '', $url),
            'embed_url' => $embedUrl,
            'format' => 'uniqueid'
        ];
    }
    
    // Vérifier Format 2 (stream.aspx avec chemin) - CONVERSION AUTOMATIQUE
    if (preg_match($pattern2, $url, $matches)) {
        // CONVERSION AUTOMATIQUE : Remplacer stream.aspx par embed.aspx
        $embedUrl = str_replace('/stream.aspx?', '/embed.aspx?', $url);
        
        // Extraire le chemin du fichier pour obtenir le titre
        $filePath = urldecode($matches[1]);
        $fileName = basename($filePath);
        
        // Enlever l'extension et nettoyer le nom
        $fileName = preg_replace('/\.(mp4|avi|mov|wmv|flv|mkv)$/i', '', $fileName);
        $fileName = str_replace(['_', '-'], ' ', $fileName); // Remplacer _ et - par espaces
        
        // Extraire un ID unique depuis le chemin (pour stockage)
        $uniqueId = md5($filePath);
        
        // Extraire l'URL de base sans paramètres
        preg_match('/^(https?:\/\/[^?]+)/', $url, $baseMatch);
        $baseUrl = $baseMatch[1] ?? $url;
        
        return [
            'unique_id' => $uniqueId,
            'full_url' => $url,
            'base_url' => $baseUrl,
            'embed_url' => $embedUrl,
            'file_path' => $filePath,
            'suggested_title' => $fileName,
            'format' => 'filepath'
        ];
    }
    
    return false;
}

/**
 * NOUVEAU v2.0.0 : Extrait un titre lisible depuis les données Stream
 * 
 * @param array $streamData Données Stream avec suggested_title
 * @return string Titre extrait ou par défaut
 */
function getStreamTitleFromData($streamData) {
    // Si un titre suggéré existe (extrait du nom de fichier)
    if (isset($streamData['suggested_title']) && !empty($streamData['suggested_title'])) {
        return sanitize($streamData['suggested_title']);
    }
    
    // Sinon titre par défaut
    return 'Vidéo Microsoft Stream';
}

/**
 * NOUVEAU v2.0.0 : Détection du type de vidéo
 * 
 * @param string $url URL à analyser
 * @return string|false Type de vidéo (youtube/stream) ou false
 */
function detectVideoType($url) {
    if (validateYouTubeUrl($url)) {
        return VIDEO_TYPE_YOUTUBE;
    }
    if (validateStreamUrl($url)) {
        return VIDEO_TYPE_STREAM;
    }
    return false;
}

/**
 * Validation de l'ID de projet
 */
function validateProjectId($id) {
    return preg_match('/^[a-f0-9]{8}$/', $id);
}

/**
 * Vérification de la limite de projets
 */
function checkProjectLimit() {
    $projectCount = $_SESSION['project_count'] ?? 0;
    if ($projectCount >= MAX_PROJECTS_PER_SESSION) {
        throw new Exception('Limite de projets atteinte pour cette session');
    }
}

/**
 * Vérification sécurisée d'un chemin
 */
function isSecurePath($path, $baseDir) {
    $realPath = realpath($path);
    $realBaseDir = realpath($baseDir);
    
    if ($realPath === false) {
        $realPath = realpath(dirname($path)) . '/' . basename($path);
    }
    
    if ($realBaseDir === false) {
        return false;
    }
    
    return strpos($realPath, $realBaseDir) === 0;
}

/**
 * Nettoyage d'un chapitre
 */
function sanitizeChapter($chapter) {
    if (!is_array($chapter)) {
        return null;
    }
    
    $clean = [
        'time' => max(0, intval($chapter['time'] ?? 0)),
        'title' => mb_substr(sanitize($chapter['title'] ?? ''), 0, MAX_TITLE_LENGTH),
        'type' => in_array($chapter['type'] ?? '', ['chapitre', 'elu', 'vote']) ? 
                  $chapter['type'] : 'chapitre'
    ];
    
    if ($clean['type'] === 'elu' && isset($chapter['elu']) && is_array($chapter['elu'])) {
        $clean['elu'] = [
            'nom' => sanitize($chapter['elu']['nom'] ?? ''),
            'fonction' => sanitize($chapter['elu']['fonction'] ?? ''),
            'groupe' => sanitize($chapter['elu']['groupe'] ?? ''),
            'description' => sanitize($chapter['elu']['description'] ?? '')
        ];
        $clean['showInfo'] = isset($chapter['showInfo']) ? (bool)$chapter['showInfo'] : false;
    }
    
    return $clean;
}

/**
 * Construction sécurisée de l'URL de base
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = sanitize($_SERVER['HTTP_HOST'] ?? 'localhost');
    $scriptPath = sanitize($_SERVER['SCRIPT_NAME'] ?? '');
    $baseDir = dirname($scriptPath);
    
    return $protocol . '://' . $host . $baseDir;
}