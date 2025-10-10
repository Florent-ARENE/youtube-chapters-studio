<?php
/**
 * Configuration et fonctions de sécurité
 */

// Configuration de sécurité PHP
// Ne modifier les paramètres de session que si aucune session n'est active
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
}

ini_set('display_errors', 0);
error_reporting(E_ALL);

// Si HTTPS, décommenter la ligne suivante
// if (session_status() === PHP_SESSION_NONE) {
//     ini_set('session.cookie_secure', 1);
// }

// Démarrer la session de manière sécurisée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Générer un token CSRF si nécessaire
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Constantes de configuration
define('MAX_CHAPTERS', 500);
define('MAX_TITLE_LENGTH', 200);
define('MAX_PROJECTS_PER_SESSION', 50);
define('DATA_DIR', __DIR__ . '/chapters_data');
define('ELUS_FILE', __DIR__ . '/elus/elus.csv');

// Créer le dossier de données s'il n'existe pas
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0750, true);
}

/**
 * Fonction de sécurisation des entrées
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Validation de l'ID de vidéo YouTube
 */
function validateYouTubeId($id) {
    return preg_match('/^[a-zA-Z0-9_-]{11}$/', $id);
}

/**
 * Validation de l'ID de projet
 */
function validateProjectId($id) {
    return preg_match('/^[a-f0-9]{8}$/', $id);
}

/**
 * Validation d'URL YouTube
 */
function validateYouTubeUrl($url) {
    $url = filter_var($url, FILTER_SANITIZE_URL);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    $pattern = '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    return false;
}

/**
 * Validation du token CSRF
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Génération d'un ID de projet sécurisé
 */
function generateProjectId() {
    return substr(bin2hex(random_bytes(16)), 0, 8);
}

/**
 * Vérification du chemin sécurisé
 */
function isSecurePath($path, $baseDir) {
    // Obtenir le répertoire parent du fichier (car le fichier peut ne pas encore exister)
    $pathDir = dirname($path);
    
    // Si le répertoire n'existe pas encore, vérifier le chemin théorique
    if (!file_exists($pathDir)) {
        $normalizedPath = str_replace(['../', '..\\', './', '.\\'], '', $path);
        $normalizedBase = str_replace(['../', '..\\', './', '.\\'], '', $baseDir);
        return strpos($normalizedPath, $normalizedBase) === 0;
    }
    
    // Si le répertoire existe, utiliser realpath
    $realPathDir = realpath($pathDir);
    $realBaseDir = realpath($baseDir);
    
    return $realPathDir !== false && 
           $realBaseDir !== false && 
           strpos($realPathDir, $realBaseDir) === 0;
}

/**
 * Limite le nombre de projets par session
 */
function checkProjectLimit() {
    if (!isset($_SESSION['project_count'])) {
        $_SESSION['project_count'] = 0;
    }
    
    if ($_SESSION['project_count'] >= MAX_PROJECTS_PER_SESSION) {
        throw new Exception('Limite de projets atteinte pour cette session');
    }
}

/**
 * Nettoie et valide un chapitre
 */
function sanitizeChapter($chapter) {
    $clean = [
        'time' => max(0, intval($chapter['time'] ?? 0)),
        'type' => in_array($chapter['type'] ?? '', ['chapitre', 'elu', 'vote']) 
                  ? $chapter['type'] : 'chapitre'
    ];
    
    // Titre
    if (isset($chapter['title'])) {
        $title = sanitize($chapter['title']);
        $clean['title'] = mb_substr($title, 0, MAX_TITLE_LENGTH);
    }
    
    // Données d'élu
    if ($clean['type'] === 'elu' && isset($chapter['elu'])) {
        $clean['elu'] = [
            'nom' => sanitize($chapter['elu']['nom'] ?? ''),
            'fonction' => sanitize($chapter['elu']['fonction'] ?? ''),
            'majo' => sanitize($chapter['elu']['majo'] ?? ''),
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