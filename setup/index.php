<?php
/**
 * Interface d'installation
 * YouTube Chapters Studio
 */

session_start();

// Définir le fichier .installed avant toute utilisation
$installedFile = dirname(__DIR__) . '/.installed';

// Actions de réinitialisation
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    header('Content-Type: application/json');
    
    if (file_exists($installedFile)) {
        if (unlink($installedFile)) {
            echo json_encode(['success' => true, 'message' => 'Installation réinitialisée avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Impossible de supprimer le fichier .installed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Aucune installation trouvée']);
    }
    exit;
}

// Actions de réparation
if (isset($_GET['fix'])) {
    header('Content-Type: application/json');
    
    $results = [];
    $baseDir = dirname(__DIR__);
    
    // Créer le dossier chapters_data avec les bonnes permissions
    $dataDir = $baseDir . '/chapters_data';
    if (!is_dir($dataDir)) {
        $oldUmask = umask(0);
        if (mkdir($dataDir, 0777, true)) {
            $results[] = 'Dossier chapters_data créé';
        } else {
            $results[] = 'Erreur création chapters_data';
        }
        umask($oldUmask);
    }
    
    // Fixer les permissions
    if (is_dir($dataDir) && !is_writable($dataDir)) {
        if (chmod($dataDir, 0777)) {
            $results[] = 'Permissions corrigées pour chapters_data';
        } else {
            $results[] = 'Erreur permissions chapters_data';
        }
    }
    
    // Créer le fichier .htaccess
    $htaccess = $dataDir . '/.htaccess';
    if (!file_exists($htaccess)) {
        $content = "Order Deny,Allow\nDeny from all\nOptions -Indexes";
        if (file_put_contents($htaccess, $content)) {
            $results[] = 'Fichier .htaccess créé';
        } else {
            $results[] = 'Erreur création .htaccess';
        }
    }
    
    echo json_encode(['success' => true, 'results' => $results]);
    exit;
}

// Vérifier si l'installation a déjà été effectuée
if (file_exists($installedFile) && !isset($_GET['force'])) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Installation déjà effectuée</title>
        <style>
            body { 
                background: #0f0f0f; 
                color: #fff; 
                font-family: Arial, sans-serif; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                min-height: 100vh;
                margin: 0;
            }
            .message { 
                background: #1a1a1a; 
                padding: 40px; 
                border-radius: 12px; 
                text-align: center;
                max-width: 600px;
            }
            .message h1 { color: #ff0000; }
            .btn { 
                display: inline-block; 
                margin: 10px; 
                padding: 12px 24px; 
                background: #ff0000; 
                color: white; 
                text-decoration: none; 
                border-radius: 8px;
                cursor: pointer;
                border: none;
                font-size: 16px;
                font-weight: 600;
                transition: all 0.3s ease;
            }
            .btn:hover { 
                background: #cc0000; 
                transform: translateY(-2px);
            }
            .btn-secondary {
                background: #333;
            }
            .btn-secondary:hover {
                background: #555;
            }
            .info { 
                background: #0a0a0a; 
                padding: 15px; 
                border-radius: 8px; 
                margin: 20px 0;
                font-size: 14px;
                text-align: left;
            }
            .actions {
                margin-top: 30px;
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }
            .reset-section {
                margin-top: 40px;
                padding-top: 30px;
                border-top: 1px solid #333;
            }
            .reset-section h3 {
                color: #ff6666;
                margin-bottom: 15px;
            }
            .reset-section p {
                color: #999;
                margin-bottom: 20px;
            }
            #reset-result {
                margin-top: 15px;
                padding: 15px;
                border-radius: 8px;
                display: none;
            }
            #reset-result.success {
                background: rgba(0, 255, 0, 0.1);
                border: 1px solid #00ff00;
                color: #00ff00;
            }
            #reset-result.error {
                background: rgba(255, 0, 0, 0.1);
                border: 1px solid #ff0000;
                color: #ff0000;
            }
            .code-example {
                background: #0a0a0a;
                padding: 10px;
                border-radius: 4px;
                font-family: monospace;
                margin: 10px 0;
            }
        </style>
    </head>
    <body>
        <div class="message">
            <h1>✅ Installation déjà effectuée</h1>
            <p>YouTube Chapters Studio est déjà installé.</p>
            <div class="info">
                <?php 
                $data = json_decode(file_get_contents($installedFile), true);
                echo "Version : " . ($data['version'] ?? 'Inconnue') . "<br>";
                echo "Date : " . ($data['date'] ?? 'Inconnue') . "<br>";
                echo "PHP : " . ($data['php_version'] ?? 'Inconnue');
                ?>
            </div>
            
            <div class="actions">
                <a href="../index.php" class="btn">
                    🏠 Accéder à l'application
                </a>
                <a href="../tests/" class="btn btn-secondary">
                    🧪 Suite de tests
                </a>
            </div>
            
            <div class="reset-section">
                <h3>🔄 Réinstaller l'application</h3>
                <p>Si vous souhaitez relancer le processus d'installation :</p>
                
                <p><strong>Option 1 : Automatique</strong></p>
                <button onclick="resetInstallation()" class="btn btn-secondary">
                    🔄 Réinitialiser l'installation
                </button>
                
                <p style="margin-top: 20px;"><strong>Option 2 : Manuelle</strong></p>
                <div class="code-example">
                    # Linux/Mac<br>
                    rm ../.installed<br><br>
                    # Windows<br>
                    del ..\.installed
                </div>
                
                <div id="reset-result"></div>
            </div>
        </div>
        
        <script>
        function resetInstallation() {
            if (confirm('Êtes-vous sûr de vouloir réinitialiser l\'installation ?\n\nCela supprimera le fichier .installed et permettra de relancer le processus d\'installation.\n\nVos données (projets, configuration) ne seront PAS affectées.')) {
                const resultDiv = document.getElementById('reset-result');
                resultDiv.style.display = 'block';
                resultDiv.className = '';
                resultDiv.innerHTML = '⏳ Réinitialisation en cours...';
                
                fetch('?reset=1')
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            resultDiv.className = 'success';
                            resultDiv.innerHTML = '✅ ' + data.message + '<br>Redirection vers l\'installation...';
                            setTimeout(() => {
                                window.location.href = '?step=requirements';
                            }, 2000);
                        } else {
                            resultDiv.className = 'error';
                            resultDiv.innerHTML = '❌ ' + data.message;
                        }
                    })
                    .catch(error => {
                        resultDiv.className = 'error';
                        resultDiv.innerHTML = '❌ Erreur : ' + error.message;
                    });
            }
        }
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Étapes d'installation
$steps = [
    'requirements' => 'Vérification des prérequis',
    'directories' => 'Création des dossiers',
    'permissions' => 'Configuration des permissions',
    'security' => 'Sécurisation',
    'test' => 'Test de fonctionnement',
    'complete' => 'Installation terminée'
];

$currentStep = $_GET['step'] ?? 'requirements';
$errors = [];
$warnings = [];
$success = [];

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $processed = false;
    $nextStepAfterProcess = null;
    
    switch ($currentStep) {
        case 'directories':
            createDirectories();
            $processed = true;
            // Si pas d'erreurs critiques, passer à l'étape suivante
            if (empty($errors)) {
                $nextStepAfterProcess = 'permissions';
            }
            break;
            
        case 'permissions':
            setPermissions();
            $processed = true;
            if (empty($errors)) {
                $nextStepAfterProcess = 'security';
            }
            break;
            
        case 'security':
            createSecurityFiles();
            $processed = true;
            if (empty($errors)) {
                $nextStepAfterProcess = 'test';
            }
            break;
    }
    
    // Redirection après traitement pour éviter le re-POST
    if ($processed && $nextStepAfterProcess) {
        header("Location: ?step=" . $nextStepAfterProcess);
        exit;
    }
}

// Fonctions d'installation
function checkRequirements() {
    global $errors, $warnings, $success;
    
    // PHP Version
    if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
        $success[] = "PHP " . PHP_VERSION . " (>= 7.0 requis)";
    } else {
        $errors[] = "PHP " . PHP_VERSION . " (version 7.0 ou supérieure requise)";
    }
    
    // Extensions requises
    $requiredExtensions = [
        'mbstring' => 'Gestion des caractères multi-octets',
        'json' => 'Support JSON',
        'session' => 'Gestion des sessions'
    ];
    
    foreach ($requiredExtensions as $ext => $desc) {
        if (extension_loaded($ext)) {
            $success[] = "Extension $ext : $desc";
        } else {
            $errors[] = "Extension $ext manquante : $desc";
        }
    }
    
    // Extensions optionnelles
    if (function_exists('curl_init')) {
        $success[] = "cURL disponible (recommandé pour YouTube)";
    } else {
        $warnings[] = "cURL non disponible (fallback vers file_get_contents)";
    }
    
    // Configuration PHP
    if (ini_get('allow_url_fopen')) {
        $success[] = "allow_url_fopen activé";
    } else {
        $errors[] = "allow_url_fopen désactivé (requis pour récupérer les titres YouTube)";
    }
    
    // Permissions d'écriture
    $baseDir = dirname(__DIR__);
    if (is_writable($baseDir)) {
        $success[] = "Permissions d'écriture sur le dossier principal";
    } else {
        $errors[] = "Pas de permission d'écriture sur : " . $baseDir;
    }
}

function createDirectories() {
    global $errors, $success;
    
    $baseDir = dirname(__DIR__);
    $directories = [
        'chapters_data' => 'Stockage des projets',
        'elus' => 'Base de données des élus',
        'tests' => 'Tests de l\'application',
        'scripts' => 'Scripts de maintenance'
    ];
    
    $oldUmask = umask(0);
    $hasCreated = false;
    
    foreach ($directories as $dir => $desc) {
        $path = $baseDir . '/' . $dir;
        if (!is_dir($path)) {
            if (mkdir($path, 0777, true)) {
                $success[] = "Dossier créé : $dir ($desc)";
                $hasCreated = true;
            } else {
                $errors[] = "Impossible de créer : $dir";
            }
        } else {
            $success[] = "Dossier existant : $dir";
        }
    }
    
    umask($oldUmask);
    
    // Si tous les dossiers existent déjà et qu'il n'y a pas d'erreurs, c'est OK
    if (!$hasCreated && empty($errors)) {
        $success[] = "Tous les dossiers sont déjà en place";
    }
}

function setPermissions() {
    global $errors, $success, $warnings;
    
    $baseDir = dirname(__DIR__);
    $permissions = [
        'chapters_data' => 0777,
        'elus' => 0755,
        'tests' => 0755,
        'scripts' => 0755
    ];
    
    foreach ($permissions as $dir => $perm) {
        $path = $baseDir . '/' . $dir;
        if (is_dir($path)) {
            if (chmod($path, $perm)) {
                $success[] = "Permissions définies pour $dir : " . decoct($perm);
            } else {
                $warnings[] = "Impossible de définir les permissions pour $dir";
            }
        }
    }
}

function createSecurityFiles() {
    global $errors, $success, $warnings;
    
    $baseDir = dirname(__DIR__);
    
    // Définition de tous les fichiers .htaccess à créer
    $htaccessFiles = [
        // Protection des données
        'chapters_data/.htaccess' => [
            'content' => "# Interdire tout accès aux fichiers JSON
Order Deny,Allow
Deny from all

# Désactiver l'indexation du répertoire
Options -Indexes

# Interdire l'exécution de scripts
<FilesMatch \"\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$\">
    Deny from all
</FilesMatch>

# Interdire l'accès aux fichiers sensibles
<FilesMatch \"\.(json|log|sql|db)$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>",
            'critical' => true,
            'description' => 'Protection totale des données'
        ],
        
        // Accès local uniquement pour les tests
        'tests/.htaccess' => [
            'content' => "# Autoriser uniquement l'accès local
Order Deny,Allow
Deny from all
Allow from 127.0.0.1
Allow from ::1
Allow from localhost

# Empêcher l'indexation
Options -Indexes",
            'critical' => false,
            'description' => 'Accès local uniquement'
        ],
        
        // Protection des scripts de maintenance
        'scripts/.htaccess' => [
            'content' => "# Interdire tout accès web aux scripts
Order Deny,Allow
Deny from all

# Ces scripts doivent être exécutés uniquement en CLI
Options -Indexes",
            'critical' => false,
            'description' => 'Protection des scripts CLI'
        ],
        
        // Protection du dossier elus (lecture seule)
        'elus/.htaccess' => [
            'content' => "# Protéger les fichiers CSV
<FilesMatch \"\.(csv|txt)$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Empêcher l'indexation
Options -Indexes

# Interdire l'exécution de scripts
<FilesMatch \"\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>",
            'critical' => false,
            'description' => 'Protection des données CSV'
        ]
    ];
    
    // .htaccess pour setup (cas spécial car dans le dossier courant)
    $setupHtaccess = "# Zone d'installation - À sécuriser après installation
# Décommentez les lignes suivantes après l'installation :
# Order Deny,Allow
# Deny from all

# Empêcher l'indexation
Options -Indexes";
    
    // Créer les fichiers .htaccess
    foreach ($htaccessFiles as $path => $config) {
        $fullPath = $baseDir . '/' . $path;
        $dir = dirname($fullPath);
        
        // Vérifier si le dossier existe
        if (!is_dir($dir)) {
            if ($config['critical']) {
                $warnings[] = "Dossier manquant pour $path (sera créé à l'étape précédente)";
            }
            continue;
        }
        
        // Vérifier si le fichier existe déjà
        if (file_exists($fullPath)) {
            // Vérifier si le contenu est différent
            $existingContent = file_get_contents($fullPath);
            if (trim($existingContent) !== trim($config['content'])) {
                // Sauvegarder l'ancien fichier
                $backupPath = $fullPath . '.backup.' . date('YmdHis');
                copy($fullPath, $backupPath);
                
                // Mettre à jour avec le nouveau contenu
                if (file_put_contents($fullPath, $config['content'])) {
                    $success[] = ".htaccess mis à jour dans " . dirname($path) . " (ancien sauvegardé)";
                } else {
                    $errors[] = "Impossible de mettre à jour .htaccess dans " . dirname($path);
                }
            } else {
                $success[] = ".htaccess existe déjà dans " . dirname($path) . " (" . $config['description'] . ")";
            }
        } else {
            // Créer le nouveau fichier
            if (file_put_contents($fullPath, $config['content'])) {
                $success[] = "Fichier .htaccess créé dans " . dirname($path) . " (" . $config['description'] . ")";
            } else {
                if ($config['critical']) {
                    $errors[] = "Impossible de créer .htaccess dans " . dirname($path) . " (CRITIQUE)";
                } else {
                    $warnings[] = "Impossible de créer .htaccess dans " . dirname($path);
                }
            }
        }
    }
    
    // Traiter le .htaccess du setup séparément
    $setupPath = __DIR__ . '/.htaccess';
    if (!file_exists($setupPath)) {
        if (file_put_contents($setupPath, $setupHtaccess)) {
            $success[] = "Fichier .htaccess créé dans setup (à sécuriser après installation)";
        } else {
            $warnings[] = "Impossible de créer .htaccess dans setup";
        }
    } else {
        $success[] = ".htaccess existe déjà dans setup";
    }
    
    // Vérifier la présence d'un .htaccess à la racine (optionnel)
    $rootHtaccess = $baseDir . '/.htaccess';
    if (!file_exists($rootHtaccess)) {
        $warnings[] = "Pas de .htaccess à la racine (optionnel mais recommandé)";
        
        // Proposer un modèle de .htaccess racine
        $rootHtaccessContent = "# Configuration de sécurité générale
# Désactiver l'affichage des erreurs en production
php_flag display_errors off

# Headers de sécurité
<IfModule mod_headers.c>
    Header set X-Content-Type-Options \"nosniff\"
    Header set X-Frame-Options \"SAMEORIGIN\"
    Header set X-XSS-Protection \"1; mode=block\"
    Header set Referrer-Policy \"strict-origin-when-cross-origin\"
</IfModule>

# Empêcher l'accès aux fichiers sensibles
<FilesMatch \"\\.(log|sql|sqlite|db|env|yml|yaml|ini|conf|config)$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Protéger les fichiers commençant par un point
<FilesMatch \"^\\..*\">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Désactiver l'indexation des répertoires
Options -Indexes

# Redirection vers HTTPS (décommenter si HTTPS disponible)
# <IfModule mod_rewrite.c>
#     RewriteEngine On
#     RewriteCond %{HTTPS} off
#     RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
# </IfModule>";
        
        // Sauvegarder le modèle pour référence
        $modelPath = __DIR__ . '/htaccess-root-model.txt';
        file_put_contents($modelPath, $rootHtaccessContent);
        $success[] = "Modèle de .htaccess racine créé dans setup/htaccess-root-model.txt";
    }
}

function testInstallation() {
    global $errors, $success;
    
    $baseDir = dirname(__DIR__);
    
    // Test d'écriture
    $testFile = $baseDir . '/chapters_data/test_' . uniqid() . '.json';
    $testData = json_encode(['test' => true, 'timestamp' => time()]);
    
    if (file_put_contents($testFile, $testData)) {
        $success[] = "Test d'écriture réussi";
        unlink($testFile);
    } else {
        $errors[] = "Test d'écriture échoué dans chapters_data";
    }
    
    // Test de session
    $_SESSION['test'] = 'ok';
    if ($_SESSION['test'] === 'ok') {
        $success[] = "Sessions PHP fonctionnelles";
    } else {
        $errors[] = "Problème avec les sessions PHP";
    }
    
    // Test CSRF
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    if (strlen($_SESSION['csrf_token']) === 64) {
        $success[] = "Token CSRF généré correctement";
    } else {
        $errors[] = "Problème avec la génération du token CSRF";
    }
    
    // Si tous les tests sont OK, créer le fichier .installed
    if (empty($errors)) {
        $installedFile = $baseDir . '/.installed';
        $installedData = [
            'version' => '1.4.0',
            'date' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION
        ];
        file_put_contents($installedFile, json_encode($installedData, JSON_PRETTY_PRINT));
    }
}

// Exécution selon l'étape
switch ($currentStep) {
    case 'requirements':
        checkRequirements();
        break;
    case 'test':
        testInstallation();
        break;
}

// Fonction pour obtenir la prochaine étape
function getNextStep($current) {
    global $steps;
    $keys = array_keys($steps);
    $currentIndex = array_search($current, $keys);
    return isset($keys[$currentIndex + 1]) ? $keys[$currentIndex + 1] : null;
}

$nextStep = getNextStep($currentStep);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - YouTube Chapters Studio</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f0f;
            color: #fff;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .installer {
            width: 100%;
            max-width: 800px;
            background: #1a1a1a;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        
        .installer-header {
            background: linear-gradient(135deg, #ff0000, #cc0000);
            padding: 30px;
            text-align: center;
        }
        
        .installer-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .installer-header p {
            opacity: 0.9;
        }
        
        .progress-bar {
            background: rgba(0, 0, 0, 0.3);
            height: 6px;
            margin-top: 20px;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .progress-fill {
            background: #fff;
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .installer-body {
            padding: 40px;
        }
        
        .step-title {
            font-size: 1.5rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .step-number {
            background: #ff0000;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .step-description {
            color: #999;
            margin-bottom: 30px;
        }
        
        .check-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .check-item.success {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid rgba(0, 255, 0, 0.3);
        }
        
        .check-item.error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid rgba(255, 0, 0, 0.3);
        }
        
        .check-item.warning {
            background: rgba(255, 170, 0, 0.1);
            border: 1px solid rgba(255, 170, 0, 0.3);
        }
        
        .check-icon {
            font-size: 1.2rem;
        }
        
        .installer-footer {
            padding: 30px 40px;
            background: #0a0a0a;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn {
            padding: 12px 24px;
            background: #ff0000;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #cc0000;
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            background: #666;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-secondary {
            background: #333;
        }
        
        .btn-secondary:hover {
            background: #555;
        }
        
        .complete-message {
            text-align: center;
            padding: 40px;
        }
        
        .complete-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #00ff00;
        }
        
        .complete-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .code-block {
            background: #0a0a0a;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 14px;
            margin: 20px 0;
            overflow-x: auto;
        }
        
        .important-note {
            background: rgba(255, 0, 0, 0.2);
            border: 2px solid #ff0000;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .important-note h3 {
            margin-bottom: 10px;
            color: #ff0000;
        }
        
        .fix-results {
            margin-top: 15px;
            padding: 15px;
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #00ff00;
            border-radius: 5px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="installer">
        <div class="installer-header">
            <h1>🚀 Installation de YouTube Chapters Studio</h1>
            <p>Version 1.4.0</p>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo (array_search($currentStep, array_keys($steps)) + 1) / count($steps) * 100; ?>%"></div>
            </div>
        </div>
        
        <div class="installer-body">
            <?php
            $stepNumber = array_search($currentStep, array_keys($steps)) + 1;
            ?>
            
            <h2 class="step-title">
                <span class="step-number"><?php echo $stepNumber; ?></span>
                <?php echo $steps[$currentStep]; ?>
            </h2>
            
            <?php if ($currentStep === 'requirements'): ?>
                <p class="step-description">
                    Vérification que votre serveur dispose de tous les prérequis nécessaires.
                </p>
                
                <?php foreach ($success as $msg): ?>
                <div class="check-item success">
                    <span class="check-icon">✅</span>
                    <span><?php echo htmlspecialchars($msg); ?></span>
                </div>
                <?php endforeach; ?>
                
                <?php foreach ($warnings as $msg): ?>
                <div class="check-item warning">
                    <span class="check-icon">⚠️</span>
                    <span><?php echo htmlspecialchars($msg); ?></span>
                </div>
                <?php endforeach; ?>
                
                <?php foreach ($errors as $msg): ?>
                <div class="check-item error">
                    <span class="check-icon">❌</span>
                    <span><?php echo htmlspecialchars($msg); ?></span>
                </div>
                <?php endforeach; ?>
                
            <?php elseif ($currentStep === 'directories'): ?>
                <p class="step-description">
                    Création des dossiers nécessaires au fonctionnement de l'application.
                </p>
                
                <?php
                // Vérifier l'état actuel des dossiers
                $baseDir = dirname(__DIR__);
                $directoriesStatus = [
                    'chapters_data' => ['desc' => 'Stockage des projets', 'exists' => is_dir($baseDir . '/chapters_data')],
                    'elus' => ['desc' => 'Base de données des élus', 'exists' => is_dir($baseDir . '/elus')],
                    'tests' => ['desc' => 'Tests de l\'application', 'exists' => is_dir($baseDir . '/tests')],
                    'scripts' => ['desc' => 'Scripts de maintenance', 'exists' => is_dir($baseDir . '/scripts')]
                ];
                ?>
                
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <?php foreach ($success as $msg): ?>
                    <div class="check-item success">
                        <span class="check-icon">✅</span>
                        <span><?php echo htmlspecialchars($msg); ?></span>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php foreach ($errors as $msg): ?>
                    <div class="check-item error">
                        <span class="check-icon">❌</span>
                        <span><?php echo htmlspecialchars($msg); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($directoriesStatus as $dir => $info): ?>
                    <div class="check-item <?php echo $info['exists'] ? 'success' : ''; ?>">
                        <span class="check-icon"><?php echo $info['exists'] ? '✅' : '📁'; ?></span>
                        <span>
                            <?php echo $info['exists'] ? "Dossier existant : $dir" : "$dir/"; ?> - <?php echo $info['desc']; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php
                    // Vérifier s'il y a des dossiers manquants
                    $hasMissingDirs = false;
                    foreach ($directoriesStatus as $dir => $info) {
                        if (!$info['exists']) {
                            $hasMissingDirs = true;
                            break;
                        }
                    }
                    ?>
                    
                    <?php if ($hasMissingDirs): ?>
                    <div style="margin-top: 20px;">
                        <button type="button" onclick="fixDirectories()" class="btn btn-secondary">
                            🔧 Créer automatiquement les dossiers manquants
                        </button>
                        <div id="fix-results" class="fix-results"></div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                
            <?php elseif ($currentStep === 'permissions'): ?>
                <p class="step-description">
                    Configuration des permissions sur les dossiers créés.
                </p>
                
                <?php
                // Vérifier l'état actuel des dossiers et permissions
                $baseDir = dirname(__DIR__);
                $permissionsToSet = [
                    'chapters_data' => ['perm' => 0777, 'desc' => 'Lecture/Écriture complète (stockage)'],
                    'elus' => ['perm' => 0755, 'desc' => 'Lecture seule (base de données)'],
                    'tests' => ['perm' => 0755, 'desc' => 'Lecture/Exécution (tests)'],
                    'scripts' => ['perm' => 0755, 'desc' => 'Lecture/Exécution (scripts CLI)']
                ];
                ?>
                
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <?php foreach ($success as $msg): ?>
                    <div class="check-item success">
                        <span class="check-icon">✅</span>
                        <span><?php echo htmlspecialchars($msg); ?></span>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php foreach ($warnings as $msg): ?>
                    <div class="check-item warning">
                        <span class="check-icon">⚠️</span>
                        <span><?php echo htmlspecialchars($msg); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><strong>État actuel des dossiers :</strong></p>
                    <?php foreach ($permissionsToSet as $dir => $info): ?>
                    <?php 
                    $path = $baseDir . '/' . $dir;
                    $exists = is_dir($path);
                    $currentPerms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';
                    ?>
                    <div class="check-item <?php echo $exists ? 'success' : 'error'; ?>">
                        <span class="check-icon"><?php echo $exists ? '✅' : '❌'; ?></span>
                        <span>
                            <strong><?php echo $dir; ?>/</strong> - 
                            <?php if ($exists): ?>
                                Permissions actuelles : <?php echo $currentPerms; ?> → 
                                <strong><?php echo decoct($info['perm']); ?></strong>
                            <?php else: ?>
                                Dossier manquant !
                            <?php endif; ?>
                            <br>
                            <small style="color: #999;"><?php echo $info['desc']; ?></small>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    
                    <p style="margin-top: 20px;">Les permissions suivantes vont être appliquées :</p>
                    <div class="code-block">
chmod 777 chapters_data/
chmod 755 elus/
chmod 755 tests/
chmod 755 scripts/</div>
                    
                    <?php
                    // Vérifier s'il y a des problèmes de permissions
                    $hasPermissionIssues = false;
                    foreach ($permissionsToSet as $dir => $info) {
                        $path = $baseDir . '/' . $dir;
                        if (is_dir($path) && !is_writable($path) && $dir === 'chapters_data') {
                            $hasPermissionIssues = true;
                            break;
                        }
                    }
                    ?>
                    
                    <?php if ($hasPermissionIssues): ?>
                    <div style="margin-top: 20px;">
                        <button type="button" onclick="fixDirectories()" class="btn btn-secondary">
                            🔧 Corriger automatiquement les permissions
                        </button>
                        <div id="fix-results" class="fix-results"></div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                
            <?php elseif ($currentStep === 'security'): ?>
                <p class="step-description">
                    Création des fichiers de sécurité pour protéger vos données.
                </p>
                
                <?php
                // Vérifier l'état actuel des fichiers de sécurité
                $baseDir = dirname(__DIR__);
                $securityFiles = [
                    'chapters_data/.htaccess' => ['desc' => 'Protection totale des données JSON', 'critical' => true],
                    'tests/.htaccess' => ['desc' => 'Accès local uniquement', 'critical' => false],
                    'scripts/.htaccess' => ['desc' => 'Protection des scripts CLI', 'critical' => false],
                    'elus/.htaccess' => ['desc' => 'Protection des fichiers CSV', 'critical' => false],
                    'setup/.htaccess' => ['desc' => 'À sécuriser après installation', 'critical' => false]
                ];
                ?>
                
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <?php foreach ($success as $msg): ?>
                    <div class="check-item success">
                        <span class="check-icon">✅</span>
                        <span><?php echo htmlspecialchars($msg); ?></span>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php foreach ($errors as $msg): ?>
                    <div class="check-item error">
                        <span class="check-icon">❌</span>
                        <span><?php echo htmlspecialchars($msg); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><strong>État actuel de la sécurité :</strong></p>
                    <?php foreach ($securityFiles as $file => $info): ?>
                    <?php 
                    $fullPath = ($file === 'setup/.htaccess') ? __DIR__ . '/.htaccess' : $baseDir . '/' . $file;
                    $exists = file_exists($fullPath);
                    ?>
                    <div class="check-item <?php echo $exists ? 'success' : ($info['critical'] ? 'error' : 'warning'); ?>">
                        <span class="check-icon">
                            <?php echo $exists ? '✅' : ($info['critical'] ? '🔒' : '⚠️'); ?>
                        </span>
                        <span>
                            <strong><?php echo $file; ?></strong> - <?php echo $info['desc']; ?>
                            <?php if ($exists): ?>
                                <span style="color: #00ff00;"> (Déjà en place)</span>
                            <?php else: ?>
                                <span style="color: <?php echo $info['critical'] ? '#ff0000' : '#ffaa00'; ?>;"> (À créer)</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
            <?php elseif ($currentStep === 'test'): ?>
                <p class="step-description">
                    Test final pour vérifier que tout fonctionne correctement.
                </p>
                
                <?php
                // Récapitulatif de l'installation
                $baseDir = dirname(__DIR__);
                $installationStatus = [
                    'Dossiers' => [
                        'chapters_data' => is_dir($baseDir . '/chapters_data'),
                        'elus' => is_dir($baseDir . '/elus'),
                        'tests' => is_dir($baseDir . '/tests'),
                        'scripts' => is_dir($baseDir . '/scripts')  // ← AJOUTER
                    ],
                    'Sécurité' => [
                        'chapters_data/.htaccess' => file_exists($baseDir . '/chapters_data/.htaccess'),
                        'tests/.htaccess' => file_exists($baseDir . '/tests/.htaccess'),
                        'scripts/.htaccess' => file_exists($baseDir . '/scripts/.htaccess'),  // ← AJOUTER
                        'elus/.htaccess' => file_exists($baseDir . '/elus/.htaccess'),      // ← AJOUTER
                        'setup/.htaccess' => file_exists(__DIR__ . '/.htaccess')
                    ]
                ];
                ?>
                
                <div style="background: #0a0a0a; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <h4 style="margin-bottom: 10px;">📊 Récapitulatif de l'installation :</h4>
                    <?php foreach ($installationStatus as $category => $items): ?>
                        <strong><?php echo $category; ?> :</strong>
                        <?php 
                        $allOk = !in_array(false, $items);
                        echo $allOk ? '<span style="color: #00ff00;">✅ Tout est OK</span>' : '<span style="color: #ff0000;">❌ Problèmes détectés</span>';
                        ?>
                        <br>
                    <?php endforeach; ?>
                </div>
                
                <?php foreach ($success as $msg): ?>
                <div class="check-item success">
                    <span class="check-icon">✅</span>
                    <span><?php echo htmlspecialchars($msg); ?></span>
                </div>
                <?php endforeach; ?>
                
                <?php foreach ($errors as $msg): ?>
                <div class="check-item error">
                    <span class="check-icon">❌</span>
                    <span><?php echo htmlspecialchars($msg); ?></span>
                </div>
                <?php endforeach; ?>
                
            <?php elseif ($currentStep === 'complete'): ?>
                <div class="complete-message">
                    <div class="complete-icon">🎉</div>
                    <h2>Installation terminée avec succès !</h2>
                    <p>YouTube Chapters Studio est maintenant prêt à être utilisé.</p>
                    
                    <?php
                    // Créer un récapitulatif final
                    $baseDir = dirname(__DIR__);
                    $finalCheck = [
                        'chapters_data/' => is_dir($baseDir . '/chapters_data') && is_writable($baseDir . '/chapters_data'),
                        'elus/' => is_dir($baseDir . '/elus'),
                        'tests/' => is_dir($baseDir . '/tests'),
                        'scripts/' => is_dir($baseDir . '/scripts'),  // ← AJOUTER
                        '.htaccess sécurité' => file_exists($baseDir . '/chapters_data/.htaccess')
                    ];
                    $allGood = !in_array(false, $finalCheck);
                    ?>
                    
                    <?php if ($allGood): ?>
                    <div style="background: rgba(0, 255, 0, 0.1); border: 1px solid #00ff00; padding: 15px; border-radius: 8px; margin: 20px 0;">
                        <strong>✅ Tous les composants sont correctement installés</strong>
                    </div>
                    <?php endif; ?>
                    
                    <div class="important-note">
                        <h3>⚠️ Important - Sécurisez l'installation</h3>
                        <p>Pour des raisons de sécurité, vous devez maintenant :</p>
                        <ol>
                            <li>Modifier le fichier <strong>setup/.htaccess</strong> et décommenter les lignes pour bloquer l'accès</li>
                            <li>Ou supprimer complètement le dossier <strong>setup/</strong></li>
                        </ol>
                    </div>
                    
                    <h3>📝 Prochaines étapes</h3>
                    <ol style="text-align: left; margin: 20px auto; max-width: 500px;">
                        <li>Placez votre fichier <strong>elus.csv</strong> dans le dossier <strong>elus/</strong></li>
                        <li>Assurez-vous que le dossier <strong>chapters_data/</strong> reste inscriptible</li>
                        <li>Testez l'application avec une vidéo YouTube</li>
                    </ol>
                    
                    <div class="complete-actions">
                        <a href="../index.php" class="btn">
                            🏠 Accéder à l'application
                        </a>
                        <a href="../tests/" class="btn btn-secondary">
                            🧪 Suite de tests complète
                        </a>
                    </div>
                    
                    <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #333;">
                        <h3>🔄 Réinstallation</h3>
                        <p>Si vous souhaitez relancer le processus d'installation :</p>
                        <div style="background: #0a0a0a; padding: 15px; border-radius: 8px; margin: 15px 0;">
                            <code>rm ../.installed</code> (Linux/Mac)<br>
                            <code>del ..\.installed</code> (Windows)
                        </div>
                        <p>Ou utilisez ce bouton :</p>
                        <button onclick="resetInstallation()" class="btn btn-secondary">
                            🔄 Réinitialiser l'installation
                        </button>
                        <div id="reset-result" style="margin-top: 10px; display: none;"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="installer-footer">
            <div>
                <?php if ($currentStep !== 'requirements'): ?>
                <a href="?step=requirements" class="btn btn-secondary">
                    ← Recommencer
                </a>
                <?php endif; ?>
            </div>
            
            <div>
                <?php if ($nextStep && empty($errors)): ?>
                    <?php if (in_array($currentStep, ['directories', 'permissions', 'security'])): ?>
                    <form method="POST" style="display: inline;">
                        <button type="submit" class="btn">
                            Exécuter →
                        </button>
                    </form>
                    <?php else: ?>
                    <a href="?step=<?php echo $nextStep; ?>" class="btn">
                        Continuer →
                    </a>
                    <?php endif; ?>
                <?php elseif (!empty($errors) && $currentStep !== 'complete'): ?>
                <button class="btn" disabled>
                    Corriger les erreurs
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function resetInstallation() {
        if (confirm('Êtes-vous sûr de vouloir réinitialiser l\'installation ?\n\nCela supprimera le fichier .installed et permettra de relancer le processus d\'installation.\n\nVos données (projets, configuration) ne seront PAS affectées.')) {
            const resultDiv = document.getElementById('reset-result');
            if (resultDiv) {
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = '⏳ Réinitialisation en cours...';
                
                fetch('?reset=1')
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            resultDiv.innerHTML = '<span style="color: #00ff00;">✅ ' + data.message + '</span><br>Redirection vers l\'installation...';
                            setTimeout(() => {
                                window.location.href = '?step=requirements';
                            }, 2000);
                        } else {
                            resultDiv.innerHTML = '<span style="color: #ff0000;">❌ ' + data.message + '</span>';
                        }
                    })
                    .catch(error => {
                        resultDiv.innerHTML = '<span style="color: #ff0000;">❌ Erreur : ' + error.message + '</span>';
                    });
            }
        }
    }
    
    function fixDirectories() {
        const resultsDiv = document.getElementById('fix-results');
        if (resultsDiv) {
            resultsDiv.style.display = 'block';
            resultsDiv.innerHTML = '⏳ Correction en cours...';
            
            fetch('?fix=1')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        resultsDiv.innerHTML = '✅ Corrections appliquées :<br>' + 
                                              data.results.join('<br>') + 
                                              '<br><br>🔄 Rechargement de la page...';
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        resultsDiv.innerHTML = '❌ Erreur lors de la correction';
                    }
                })
                .catch(error => {
                    resultsDiv.innerHTML = '❌ Erreur : ' + error.message;
                });
        }
    }
    </script>
</body>
</html>