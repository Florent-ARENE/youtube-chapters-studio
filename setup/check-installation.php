<?php
/**
 * Vérification de l'installation
 * Extrait de debug.php - Partie installation
 */

session_start();
require_once '../config.php';

// Vérifier l'accès (installation uniquement)
if (file_exists('../.installed')) {
    die('L\'installation a déjà été effectuée. Pour réinstaller, supprimez le fichier .installed');
}

// Tests d'installation
$tests = [];
$errors = [];
$warnings = [];
$canInstall = true;

// 1. Version PHP
if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
    $tests['php_version'] = [
        'name' => 'PHP Version',
        'status' => 'success',
        'value' => PHP_VERSION,
        'required' => '>= 7.0'
    ];
} else {
    $tests['php_version'] = [
        'name' => 'PHP Version',
        'status' => 'error',
        'value' => PHP_VERSION,
        'required' => '>= 7.0'
    ];
    $errors[] = 'Version PHP trop ancienne';
    $canInstall = false;
}

// 2. Extensions requises
$requiredExtensions = [
    'mbstring' => 'Gestion des caractères multi-octets',
    'json' => 'Support JSON',
    'session' => 'Gestion des sessions'
];

foreach ($requiredExtensions as $ext => $desc) {
    if (extension_loaded($ext)) {
        $tests["ext_$ext"] = [
            'name' => "Extension $ext",
            'status' => 'success',
            'value' => 'Installée',
            'description' => $desc
        ];
    } else {
        $tests["ext_$ext"] = [
            'name' => "Extension $ext",
            'status' => 'error',
            'value' => 'Manquante',
            'description' => $desc
        ];
        $errors[] = "Extension $ext manquante";
        $canInstall = false;
    }
}

// 3. Extensions optionnelles
if (function_exists('curl_init')) {
    $tests['curl'] = [
        'name' => 'cURL',
        'status' => 'success',
        'value' => 'Disponible',
        'description' => 'Recommandé pour YouTube'
    ];
} else {
    $tests['curl'] = [
        'name' => 'cURL',
        'status' => 'warning',
        'value' => 'Non disponible',
        'description' => 'Fallback vers file_get_contents'
    ];
    $warnings[] = 'cURL non disponible';
}

// 4. Configuration PHP
if (ini_get('allow_url_fopen')) {
    $tests['allow_url_fopen'] = [
        'name' => 'allow_url_fopen',
        'status' => 'success',
        'value' => 'Activé',
        'description' => 'Requis pour récupérer les titres YouTube'
    ];
} else {
    $tests['allow_url_fopen'] = [
        'name' => 'allow_url_fopen',
        'status' => 'error',
        'value' => 'Désactivé',
        'description' => 'Requis pour récupérer les titres YouTube'
    ];
    $errors[] = 'allow_url_fopen désactivé';
    $canInstall = false;
}

// 5. Permissions d'écriture
$baseDir = dirname(__DIR__);
if (is_writable($baseDir)) {
    $tests['write_permissions'] = [
        'name' => 'Permissions d\'écriture',
        'status' => 'success',
        'value' => 'OK',
        'description' => $baseDir
    ];
} else {
    $tests['write_permissions'] = [
        'name' => 'Permissions d\'écriture',
        'status' => 'error',
        'value' => 'Insuffisantes',
        'description' => $baseDir
    ];
    $errors[] = 'Permissions d\'écriture insuffisantes';
    $canInstall = false;
}

// 6. Dossiers existants
$existingDirs = [
    'chapters_data' => 'Stockage des projets',
    'elus' => 'Base de données des élus',
    'tests' => 'Tests de l\'application'
];

foreach ($existingDirs as $dir => $desc) {
    $path = $baseDir . '/' . $dir;
    if (is_dir($path)) {
        $tests["dir_$dir"] = [
            'name' => "Dossier $dir",
            'status' => 'info',
            'value' => 'Existe déjà',
            'description' => $desc
        ];
    }
}

// 7. Fichiers de configuration
if (file_exists($baseDir . '/chapters_data/.htaccess')) {
    $tests['htaccess_data'] = [
        'name' => 'Protection chapters_data',
        'status' => 'info',
        'value' => '.htaccess présent',
        'description' => 'Sécurité déjà configurée'
    ];
}

// 8. Test de session
$_SESSION['test_install'] = true;
if ($_SESSION['test_install'] === true) {
    $tests['session'] = [
        'name' => 'Sessions PHP',
        'status' => 'success',
        'value' => 'Fonctionnelles',
        'description' => 'Stockage des données utilisateur'
    ];
} else {
    $tests['session'] = [
        'name' => 'Sessions PHP',
        'status' => 'error',
        'value' => 'Non fonctionnelles',
        'description' => 'Requis pour le fonctionnement'
    ];
    $errors[] = 'Sessions PHP non fonctionnelles';
    $canInstall = false;
}

// 9. Token CSRF
try {
    $testToken = bin2hex(random_bytes(32));
    if (strlen($testToken) === 64) {
        $tests['csrf'] = [
            'name' => 'Génération CSRF',
            'status' => 'success',
            'value' => 'OK',
            'description' => 'Sécurité des formulaires'
        ];
    }
} catch (Exception $e) {
    $tests['csrf'] = [
        'name' => 'Génération CSRF',
        'status' => 'error',
        'value' => 'Erreur',
        'description' => $e->getMessage()
    ];
    $errors[] = 'Impossible de générer des tokens sécurisés';
    $canInstall = false;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de l'installation - YouTube Chapters Studio</title>
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
            padding: 40px 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        h1 {
            color: #ff0000;
            text-align: center;
            margin-bottom: 40px;
            font-size: 2.5rem;
        }
        
        .section {
            background: #1a1a1a;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        h2 {
            color: #ff6666;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        
        .test-grid {
            display: grid;
            gap: 15px;
        }
        
        .test-item {
            background: #2a2a2a;
            padding: 15px 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid #444;
        }
        
        .test-item.success {
            border-left-color: #00ff00;
        }
        
        .test-item.error {
            border-left-color: #ff0000;
        }
        
        .test-item.warning {
            border-left-color: #ffaa00;
        }
        
        .test-item.info {
            border-left-color: #00aaff;
        }
        
        .test-info {
            flex: 1;
        }
        
        .test-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .test-description {
            font-size: 0.9rem;
            color: #999;
        }
        
        .test-value {
            color: #ccc;
            font-family: monospace;
            font-size: 0.9rem;
            margin-left: 20px;
        }
        
        .summary {
            background: #2a2a2a;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .summary.success {
            border: 2px solid #00ff00;
        }
        
        .summary.error {
            border: 2px solid #ff0000;
        }
        
        .summary-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .summary-message {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        
        .summary-details {
            color: #999;
        }
        
        .actions {
            text-align: center;
            margin-top: 40px;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: #ff0000;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 10px;
        }
        
        .btn:hover {
            background: #cc0000;
            transform: translateY(-2px);
        }
        
        .btn.disabled {
            background: #666;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .btn.disabled:hover {
            transform: none;
        }
        
        .btn-secondary {
            background: #333;
        }
        
        .btn-secondary:hover {
            background: #555;
        }
        
        .error-list, .warning-list {
            background: #2a2a2a;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .error-list {
            border: 1px solid #ff0000;
        }
        
        .warning-list {
            border: 1px solid #ffaa00;
        }
        
        .error-list h3 {
            color: #ff0000;
            margin-bottom: 10px;
        }
        
        .warning-list h3 {
            color: #ffaa00;
            margin-bottom: 10px;
        }
        
        .error-list ul, .warning-list ul {
            list-style: none;
            padding-left: 20px;
        }
        
        .error-list li::before {
            content: "❌ ";
            margin-right: 10px;
        }
        
        .warning-list li::before {
            content: "⚠️ ";
            margin-right: 10px;
        }
        
        .info-box {
            background: #0a0a0a;
            border: 1px solid #333;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .info-box h3 {
            color: #00aaff;
            margin-bottom: 10px;
        }
        
        code {
            background: #2a2a2a;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Vérification de l'installation</h1>
        
        <!-- Résumé -->
        <div class="summary <?php echo $canInstall ? 'success' : 'error'; ?>">
            <div class="summary-icon">
                <?php echo $canInstall ? '✅' : '❌'; ?>
            </div>
            <div class="summary-message">
                <?php if ($canInstall): ?>
                    Votre serveur est prêt pour l'installation !
                <?php else: ?>
                    Des prérequis manquent pour l'installation
                <?php endif; ?>
            </div>
            <div class="summary-details">
                <?php
                $successCount = count(array_filter($tests, function($t) { return $t['status'] === 'success'; }));
                $totalRequired = count($tests) - count(array_filter($tests, function($t) { return $t['status'] === 'info'; }));
                ?>
                <?php echo $successCount; ?> / <?php echo $totalRequired; ?> tests réussis
                <?php if (count($warnings) > 0): ?>
                    • <?php echo count($warnings); ?> avertissement(s)
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Erreurs -->
        <?php if (count($errors) > 0): ?>
        <div class="error-list">
            <h3>Erreurs à corriger</h3>
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Avertissements -->
        <?php if (count($warnings) > 0): ?>
        <div class="warning-list">
            <h3>Avertissements</h3>
            <ul>
                <?php foreach ($warnings as $warning): ?>
                <li><?php echo htmlspecialchars($warning); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Tests détaillés -->
        <div class="section">
            <h2>Configuration système</h2>
            <div class="test-grid">
                <?php foreach ($tests as $test): ?>
                <div class="test-item <?php echo $test['status']; ?>">
                    <div class="test-info">
                        <div class="test-name"><?php echo htmlspecialchars($test['name']); ?></div>
                        <?php if (isset($test['description'])): ?>
                        <div class="test-description"><?php echo htmlspecialchars($test['description']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="test-value">
                        <?php echo htmlspecialchars($test['value']); ?>
                        <?php if (isset($test['required'])): ?>
                        <br><small>(Requis: <?php echo htmlspecialchars($test['required']); ?>)</small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Informations supplémentaires -->
        <div class="info-box">
            <h3>📋 Informations système</h3>
            <p><strong>Serveur :</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Non défini'; ?></p>
            <p><strong>Système :</strong> <?php echo PHP_OS; ?></p>
            <p><strong>Utilisateur PHP :</strong> <?php echo get_current_user(); ?></p>
            <p><strong>Répertoire :</strong> <code><?php echo dirname(__DIR__); ?></code></p>
        </div>
        
        <!-- Actions -->
        <div class="actions">
            <?php if ($canInstall): ?>
                <a href="index.php" class="btn">
                    ▶️ Continuer l'installation
                </a>
            <?php else: ?>
                <span class="btn disabled">
                    ❌ Corriger les erreurs avant de continuer
                </span>
            <?php endif; ?>
            
            <a href="check-installation.php" class="btn btn-secondary">
                🔄 Rafraîchir
            </a>
        </div>
    </div>
</body>
</html>