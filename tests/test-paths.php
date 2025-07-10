<?php
/**
 * Test des chemins et permissions
 * Fusion de debug.php et test-path.php (partie tests)
 */

session_start();
require_once '../config.php';

// Mode de test
$testMode = $_GET['mode'] ?? 'dashboard';

// Actions de réparation
if (isset($_GET['fix'])) {
    header('Content-Type: application/json');
    
    $results = [];
    
    // Créer le dossier chapters_data avec les bonnes permissions
    if (!is_dir(DATA_DIR)) {
        $oldUmask = umask(0);
        if (mkdir(DATA_DIR, 0777, true)) {
            $results[] = 'Dossier chapters_data créé';
        } else {
            $results[] = 'Erreur création chapters_data';
        }
        umask($oldUmask);
    }
    
    // Fixer les permissions
    if (is_dir(DATA_DIR) && !is_writable(DATA_DIR)) {
        if (chmod(DATA_DIR, 0777)) {
            $results[] = 'Permissions corrigées pour chapters_data';
        } else {
            $results[] = 'Erreur permissions chapters_data';
        }
    }
    
    // Créer le fichier .htaccess
    $htaccess = DATA_DIR . '/.htaccess';
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

// Mode standalone
if ($testMode === 'standalone'):
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Chemins et Permissions - YouTube Chapters Studio</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #0f0f0f;
            color: #fff;
            padding: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 { color: #ff0000; }
        h2 { color: #ff6666; margin-top: 30px; }
        .test-section {
            background: #1a1a1a;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .test-grid {
            display: grid;
            gap: 15px;
        }
        .test-item {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 6px;
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
        .test-info {
            flex: 1;
        }
        .test-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .test-value {
            color: #999;
            font-size: 14px;
            font-family: monospace;
        }
        .test-status {
            font-size: 20px;
            margin-left: 15px;
        }
        button {
            padding: 10px 20px;
            background: #ff0000;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }
        button:hover {
            background: #cc0000;
        }
        button.secondary {
            background: #444;
        }
        button.secondary:hover {
            background: #666;
        }
        .info-box {
            background: #0a0a0a;
            border: 1px solid #333;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .info-table {
            width: 100%;
            margin: 10px 0;
        }
        .info-table td {
            padding: 8px;
            border-bottom: 1px solid #333;
        }
        .info-table td:first-child {
            color: #999;
            width: 30%;
        }
        code {
            background: #2a2a2a;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
        .commands {
            background: #0a0a0a;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 14px;
            line-height: 1.6;
            margin: 15px 0;
        }
        .fix-results {
            margin-top: 15px;
            padding: 15px;
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #00ff00;
            border-radius: 5px;
            display: none;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #ff0000;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .summary {
            background: #1a1a1a;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .summary-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 20px;
        }
        .stat {
            text-align: center;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }
        .stat-label {
            color: #999;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <h1>🔍 Test des Chemins et Permissions</h1>
    
    <?php
    // Exécution des tests
    $tests = [];
    $totalTests = 0;
    $successTests = 0;
    $errorTests = 0;
    $warningTests = 0;
    
    // Test 1 : Vérification des fichiers requis
    $requiredFiles = [
        '../config.php' => 'Configuration',
        '../functions.php' => 'Fonctions métier',
        '../ajax-handler.php' => 'Gestionnaire AJAX',
        '../chapter-form.php' => 'Formulaire de chapitres',
        '../app.js' => 'JavaScript principal',
        '../styles.css' => 'Styles CSS',
        '../viewer-styles.css' => 'Styles du viewer'
    ];
    
    foreach ($requiredFiles as $file => $desc) {
        $exists = file_exists($file);
        $tests['files'][] = [
            'name' => basename($file),
            'description' => $desc,
            'value' => $exists ? 'Présent' : 'Manquant',
            'status' => $exists ? 'success' : 'error'
        ];
        $totalTests++;
        if ($exists) $successTests++; else $errorTests++;
    }
    
    // Test 2 : Vérification des dossiers
    $requiredDirs = [
        DATA_DIR => 'Stockage des projets',
        dirname(__DIR__) . '/elus' => 'Base de données des élus'
    ];
    
    foreach ($requiredDirs as $dir => $desc) {
        $exists = is_dir($dir);
        $writable = $exists && is_writable($dir);
        
        $status = 'error';
        $value = 'Manquant';
        
        if ($exists) {
            if ($writable) {
                $status = 'success';
                $value = 'OK (écriture possible)';
                $successTests++;
            } else {
                $status = 'warning';
                $value = 'Existe (non inscriptible)';
                $warningTests++;
            }
        } else {
            $errorTests++;
        }
        
        $tests['dirs'][] = [
            'name' => basename($dir),
            'description' => $desc,
            'value' => $value,
            'status' => $status,
            'path' => $dir
        ];
        $totalTests++;
    }
    
    // Test 3 : Extensions PHP
    $requiredExtensions = [
        'mbstring' => 'Gestion des caractères multi-octets',
        'json' => 'Support JSON',
        'session' => 'Gestion des sessions'
    ];
    
    foreach ($requiredExtensions as $ext => $desc) {
        $loaded = extension_loaded($ext);
        $tests['extensions'][] = [
            'name' => $ext,
            'description' => $desc,
            'value' => $loaded ? 'Activée' : 'Manquante',
            'status' => $loaded ? 'success' : 'error'
        ];
        $totalTests++;
        if ($loaded) $successTests++; else $errorTests++;
    }
    
    // Test 4 : Configuration PHP
    $phpConfig = [
        'allow_url_fopen' => ['desc' => 'Récupération des titres YouTube', 'required' => true],
        'file_uploads' => ['desc' => 'Upload de fichiers', 'required' => false],
        'session.cookie_httponly' => ['desc' => 'Sécurité des cookies', 'required' => false]
    ];
    
    foreach ($phpConfig as $setting => $info) {
        $value = ini_get($setting);
        $isOk = $value == '1' || $value == 'On';
        
        if ($info['required'] && !$isOk) {
            $status = 'error';
            $errorTests++;
        } elseif (!$info['required'] && !$isOk) {
            $status = 'warning';
            $warningTests++;
        } else {
            $status = 'success';
            $successTests++;
        }
        
        $tests['config'][] = [
            'name' => $setting,
            'description' => $info['desc'],
            'value' => $isOk ? 'Activé' : 'Désactivé',
            'status' => $status
        ];
        $totalTests++;
    }
    
    // Test 5 : Chemins et sécurité
    $testProjectId = 'test1234';
    $testPath = DATA_DIR . '/' . $testProjectId . '.json';
    $isSecure = isSecurePath($testPath, DATA_DIR);
    
    $tests['security'][] = [
        'name' => 'isSecurePath()',
        'description' => 'Validation des chemins',
        'value' => $isSecure ? 'Sécurisé' : 'Non sécurisé',
        'status' => $isSecure ? 'success' : 'error'
    ];
    $totalTests++;
    if ($isSecure) $successTests++; else $errorTests++;
    
    // Test d'écriture
    $writeTest = false;
    if (is_dir(DATA_DIR) && is_writable(DATA_DIR)) {
        $testFile = DATA_DIR . '/test_' . uniqid() . '.json';
        $writeTest = @file_put_contents($testFile, json_encode(['test' => true]));
        if ($writeTest !== false) {
            @unlink($testFile);
        }
    }
    
    $tests['security'][] = [
        'name' => 'Test d\'écriture',
        'description' => 'Création de fichiers dans chapters_data',
        'value' => $writeTest !== false ? 'Réussi' : 'Échoué',
        'status' => $writeTest !== false ? 'success' : 'error'
    ];
    $totalTests++;
    if ($writeTest !== false) $successTests++; else $errorTests++;
    
    // Test .htaccess
    $htaccessExists = file_exists(DATA_DIR . '/.htaccess');
    $tests['security'][] = [
        'name' => '.htaccess',
        'description' => 'Protection du dossier chapters_data',
        'value' => $htaccessExists ? 'Présent' : 'Manquant',
        'status' => $htaccessExists ? 'success' : 'warning'
    ];
    $totalTests++;
    if ($htaccessExists) $successTests++; else $warningTests++;
    ?>
    
    <!-- Résumé -->
    <div class="summary">
        <h2>📊 Résumé des tests</h2>
        <div class="summary-stats">
            <div class="stat">
                <span class="stat-value"><?php echo $totalTests; ?></span>
                <span class="stat-label">Tests exécutés</span>
            </div>
            <div class="stat">
                <span class="stat-value" style="color: #00ff00;"><?php echo $successTests; ?></span>
                <span class="stat-label">Réussis</span>
            </div>
            <div class="stat">
                <span class="stat-value" style="color: #ffaa00;"><?php echo $warningTests; ?></span>
                <span class="stat-label">Avertissements</span>
            </div>
            <div class="stat">
                <span class="stat-value" style="color: #ff0000;"><?php echo $errorTests; ?></span>
                <span class="stat-label">Erreurs</span>
            </div>
        </div>
    </div>
    
    <!-- Tests des fichiers -->
    <div class="test-section">
        <h2>📁 Vérification des fichiers</h2>
        <div class="test-grid">
            <?php foreach ($tests['files'] as $test): ?>
            <div class="test-item <?php echo $test['status']; ?>">
                <div class="test-info">
                    <div class="test-name"><?php echo htmlspecialchars($test['name']); ?></div>
                    <div class="test-value"><?php echo htmlspecialchars($test['description']); ?></div>
                </div>
                <div class="test-status">
                    <?php echo $test['status'] === 'success' ? '✅' : '❌'; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Tests des dossiers -->
    <div class="test-section">
        <h2>📂 Vérification des dossiers</h2>
        <div class="test-grid">
            <?php foreach ($tests['dirs'] as $test): ?>
            <div class="test-item <?php echo $test['status']; ?>">
                <div class="test-info">
                    <div class="test-name"><?php echo htmlspecialchars($test['name']); ?></div>
                    <div class="test-value">
                        <?php echo htmlspecialchars($test['description']); ?><br>
                        <small><?php echo htmlspecialchars($test['value']); ?></small>
                    </div>
                </div>
                <div class="test-status">
                    <?php 
                    echo $test['status'] === 'success' ? '✅' : 
                         ($test['status'] === 'warning' ? '⚠️' : '❌'); 
                    ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($errorTests > 0 || $warningTests > 0): ?>
        <button onclick="fixDirectories()" style="margin-top: 15px;">
            🔧 Corriger automatiquement
        </button>
        <div id="fix-results" class="fix-results"></div>
        <?php endif; ?>
    </div>
    
    <!-- Tests des extensions -->
    <div class="test-section">
        <h2>🔧 Extensions PHP</h2>
        <div class="test-grid">
            <?php foreach ($tests['extensions'] as $test): ?>
            <div class="test-item <?php echo $test['status']; ?>">
                <div class="test-info">
                    <div class="test-name"><?php echo htmlspecialchars($test['name']); ?></div>
                    <div class="test-value"><?php echo htmlspecialchars($test['description']); ?></div>
                </div>
                <div class="test-status">
                    <?php echo $test['status'] === 'success' ? '✅' : '❌'; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Configuration PHP -->
    <div class="test-section">
        <h2>⚙️ Configuration PHP</h2>
        <div class="test-grid">
            <?php foreach ($tests['config'] as $test): ?>
            <div class="test-item <?php echo $test['status']; ?>">
                <div class="test-info">
                    <div class="test-name"><?php echo htmlspecialchars($test['name']); ?></div>
                    <div class="test-value"><?php echo htmlspecialchars($test['description']); ?></div>
                </div>
                <div class="test-status">
                    <?php 
                    echo $test['status'] === 'success' ? '✅' : 
                         ($test['status'] === 'warning' ? '⚠️' : '❌'); 
                    ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Sécurité et permissions -->
    <div class="test-section">
        <h2>🔒 Sécurité et permissions</h2>
        <div class="test-grid">
            <?php foreach ($tests['security'] as $test): ?>
            <div class="test-item <?php echo $test['status']; ?>">
                <div class="test-info">
                    <div class="test-name"><?php echo htmlspecialchars($test['name']); ?></div>
                    <div class="test-value"><?php echo htmlspecialchars($test['description']); ?></div>
                </div>
                <div class="test-status">
                    <?php 
                    echo $test['status'] === 'success' ? '✅' : 
                         ($test['status'] === 'warning' ? '⚠️' : '❌'); 
                    ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Informations système -->
    <div class="test-section">
        <h2>💻 Informations système</h2>
        <table class="info-table">
            <tr>
                <td>PHP Version</td>
                <td><?php echo phpversion(); ?></td>
            </tr>
            <tr>
                <td>Serveur</td>
                <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Non défini'; ?></td>
            </tr>
            <tr>
                <td>Système d'exploitation</td>
                <td><?php echo PHP_OS; ?></td>
            </tr>
            <tr>
                <td>Utilisateur PHP</td>
                <td><?php echo get_current_user(); ?></td>
            </tr>
            <tr>
                <td>Répertoire actuel</td>
                <td><code><?php echo getcwd(); ?></code></td>
            </tr>
            <tr>
                <td>DATA_DIR</td>
                <td><code><?php echo DATA_DIR; ?></code></td>
            </tr>
            <tr>
                <td>ELUS_FILE</td>
                <td><code><?php echo ELUS_FILE; ?></code></td>
            </tr>
            <tr>
                <td>cURL</td>
                <td><?php echo function_exists('curl_init') ? '✅ Disponible' : '⚠️ Non disponible'; ?></td>
            </tr>
        </table>
    </div>
    
    <!-- Commandes de réparation -->
    <?php if ($errorTests > 0): ?>
    <div class="test-section">
        <h2>🔨 Commandes de réparation manuelle</h2>
        <p>Si la correction automatique échoue, exécutez ces commandes :</p>
        <div class="commands">
# Créer le dossier chapters_data avec les bonnes permissions
mkdir -p <?php echo DATA_DIR; ?><br>
chmod 777 <?php echo DATA_DIR; ?><br><br>

# Créer le fichier .htaccess de protection
echo "Order Deny,Allow" > <?php echo DATA_DIR; ?>/.htaccess<br>
echo "Deny from all" >> <?php echo DATA_DIR; ?>/.htaccess<br>
echo "Options -Indexes" >> <?php echo DATA_DIR; ?>/.htaccess<br><br>

# Créer le dossier elus si nécessaire
mkdir -p <?php echo dirname(__DIR__) . '/elus'; ?><br>
chmod 755 <?php echo dirname(__DIR__) . '/elus'; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <a href="index.php" class="back-link">← Retour au dashboard des tests</a>
    
    <script>
        function fixDirectories() {
            const resultsDiv = document.getElementById('fix-results');
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
    </script>
</body>
</html>
<?php exit; endif; ?>

<?php
// Mode dashboard (intégré)
echo "=== Tests des chemins et permissions ===\n\n";

// Tests rapides pour le dashboard
$quickTests = [
    'chapters_data' => is_dir(DATA_DIR) && is_writable(DATA_DIR),
    'config.php' => file_exists('../config.php'),
    'functions.php' => file_exists('../functions.php'),
    'mbstring' => extension_loaded('mbstring'),
    'allow_url_fopen' => ini_get('allow_url_fopen')
];

$success = array_filter($quickTests);
$total = count($quickTests);

echo "Résultat rapide : " . count($success) . "/" . $total . " tests réussis\n\n";

foreach ($quickTests as $test => $result) {
    echo ($result ? "✅" : "❌") . " " . $test . "\n";
}

echo "\nCliquez sur 'Voir le test' pour l'analyse complète";
?>