<?php
/**
 * Dashboard centralisé des tests avec authentification
 * YouTube Chapters Studio
 */

session_start();

// Inclure le système d'authentification
require_once 'test-auth.php';

// Vérifier l'authentification
requireTestAuth();

// Configuration des tests
$tests = [
    'test-paths.php' => [
        'name' => 'Test des chemins et permissions',
        'description' => 'Vérifie les dossiers, permissions et chemins de fichiers',
        'icon' => '📁'
    ],
    'test-ajax.php' => [
        'name' => 'Test AJAX et sauvegarde',
        'description' => 'Teste les requêtes AJAX et la sauvegarde automatique',
        'icon' => '💾'
    ],
    'test-youtube.php' => [
        'name' => 'Test API YouTube',
        'description' => 'Teste la récupération des titres et le player YouTube',
        'icon' => '📺'
    ],
    'test-javascript.php' => [
        'name' => 'Test JavaScript',
        'description' => 'Vérifie l\'initialisation des variables JavaScript',
        'icon' => '📜'
    ]
];

// Exécution d'un test via AJAX
if (isset($_GET['run']) && isset($tests[$_GET['run']])) {
    header('Content-Type: application/json');
    $testFile = $_GET['run'];
    
    // Capturer la sortie du test
    ob_start();
    $startTime = microtime(true);
    
    // Capturer aussi les erreurs PHP
    $errorOutput = '';
    $oldErrorHandler = set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$errorOutput) {
        $errorOutput .= "Erreur PHP [$errno]: $errstr dans $errfile ligne $errline\n";
        return true;
    });
    
    try {
        if (file_exists($testFile)) {
            // Forcer le mode dashboard pour avoir une sortie
            $_GET['mode'] = 'dashboard';
            include $testFile;
            $output = ob_get_clean();
            
            // Ajouter les erreurs PHP si présentes
            if ($errorOutput) {
                $output = "Erreurs PHP détectées:\n" . $errorOutput . "\n\nSortie du test:\n" . $output;
                $success = false;
            } else {
                $success = true;
            }
            
            // Si aucune sortie, générer un message par défaut
            if (empty(trim($output))) {
                $output = "Test exécuté sans erreur (pas de sortie)";
            }
        } else {
            throw new Exception("Fichier de test non trouvé: " . $testFile);
        }
    } catch (Exception $e) {
        $output = ob_get_clean();
        $output = "Erreur : " . $e->getMessage() . "\n" . $e->getTraceAsString();
        if ($errorOutput) {
            $output .= "\n\nErreurs PHP:\n" . $errorOutput;
        }
        $success = false;
    }
    
    // Restaurer le gestionnaire d'erreurs
    restore_error_handler();
    
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    echo json_encode([
        'success' => $success,
        'output' => $output,
        'duration' => $duration
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tests - YouTube Chapters Studio</title>
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
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            padding: 40px 0;
            border-bottom: 1px solid #333;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #ff0000, #ff4444);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header p {
            color: #999;
            font-size: 1.1rem;
        }
        
        .auth-status {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #1a1a1a;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .auth-status a {
            color: #ff0000;
            text-decoration: none;
            margin-left: 10px;
        }
        
        .auth-status a:hover {
            text-decoration: underline;
        }
        
        .warning-banner {
            background: #ff0000;
            color: #fff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            justify-content: center;
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
        
        .tests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .test-card {
            background: #1a1a1a;
            border-radius: 12px;
            padding: 20px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .test-card:hover {
            border-color: #ff0000;
            transform: translateY(-2px);
        }
        
        .test-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .test-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .test-icon {
            font-size: 1.5rem;
        }
        
        .test-status {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .test-status.success {
            background: #00ff00;
            color: #000;
        }
        
        .test-status.error {
            background: #ff0000;
            color: #fff;
        }
        
        .test-status.running {
            background: #ffaa00;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .test-description {
            color: #999;
            margin-bottom: 15px;
        }
        
        .test-actions {
            display: flex;
            gap: 10px;
        }
        
        .test-output {
            margin-top: 15px;
            padding: 15px;
            background: #0a0a0a;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            line-height: 1.4;
            max-height: 300px;
            overflow-y: auto;
            display: none;
            white-space: pre-wrap;
        }
        
        .test-output.show {
            display: block;
        }
        
        .test-duration {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .results-summary {
            background: #1a1a1a;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            display: none;
        }
        
        .results-summary.show {
            display: block;
        }
        
        .results-stats {
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
        
        .footer {
            text-align: center;
            padding: 40px 0;
            border-top: 1px solid #333;
            margin-top: 60px;
            color: #666;
        }
        
        .footer a {
            color: #ff0000;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .tests-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .auth-status {
                position: static;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-status">
        <?php echo getAuthStatus(); ?>
        <?php if (isset($_SESSION[TEST_SESSION_KEY])): ?>
            <a href="?logout=1">Déconnexion</a>
        <?php endif; ?>
    </div>
    
    <div class="container">
        <div class="header">
            <h1>🧪 Centre de Tests</h1>
            <p>YouTube Chapters Studio - Suite de tests complète</p>
        </div>
        
        <div class="warning-banner">
            ⚠️ Zone de développement - Tests et diagnostics
        </div>
        
        <div class="actions">
            <button class="btn" onclick="runAllTests()">
                ▶️ Exécuter tous les tests
            </button>
            <button class="btn btn-secondary" onclick="clearResults()">
                🗑️ Effacer les résultats
            </button>
            <a href="../index.php" class="btn btn-secondary">
                🏠 Retour à l'application
            </a>
        </div>
        
        <div class="tests-grid">
            <?php foreach ($tests as $file => $test): ?>
            <?php
            // Générer un ID simple et cohérent
            $testId = preg_replace('/[^a-z0-9]/', '', strtolower($file));
            ?>
            <div class="test-card" data-test="<?php echo htmlspecialchars($file); ?>" data-test-id="<?php echo $testId; ?>">
                <div class="test-header">
                    <div class="test-title">
                        <span class="test-icon"><?php echo $test['icon']; ?></span>
                        <span><?php echo htmlspecialchars($test['name']); ?></span>
                    </div>
                    <div class="test-status" id="status-<?php echo $testId; ?>"></div>
                </div>
                <div class="test-description">
                    <?php echo htmlspecialchars($test['description']); ?>
                </div>
                <div class="test-actions">
                    <button class="btn btn-secondary" onclick="runTest('<?php echo htmlspecialchars($file); ?>', '<?php echo $testId; ?>')">
                        ▶️ Exécuter
                    </button>
                    <button class="btn btn-secondary" onclick="viewTest('<?php echo htmlspecialchars($file); ?>')">
                        👁️ Voir le test
                    </button>
                </div>
                <div class="test-output" id="output-<?php echo $testId; ?>"></div>
                <div class="test-duration" id="duration-<?php echo $testId; ?>"></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="results-summary" id="results-summary">
            <h2>📊 Résumé des tests</h2>
            <div class="results-stats">
                <div class="stat">
                    <span class="stat-value" id="stat-total">0</span>
                    <span class="stat-label">Tests exécutés</span>
                </div>
                <div class="stat">
                    <span class="stat-value" id="stat-success" style="color: #00ff00;">0</span>
                    <span class="stat-label">Réussis</span>
                </div>
                <div class="stat">
                    <span class="stat-value" id="stat-error" style="color: #ff0000;">0</span>
                    <span class="stat-label">Échoués</span>
                </div>
                <div class="stat">
                    <span class="stat-value" id="stat-duration">0ms</span>
                    <span class="stat-label">Durée totale</span>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>
                YouTube Chapters Studio v2.0.0 | 
                <a href="../">Application</a> | 
                <a href="../setup/">Installation</a> | 
                <a href="https://github.com/Florent-ARENE/youtube-chapters-studio" target="_blank">GitHub</a>
            </p>
        </div>
    </div>
    
    <script>
        let testResults = {
            total: 0,
            success: 0,
            error: 0,
            duration: 0
        };
        
        function runTest(testFile, testId) {
            console.log('Exécution du test:', testFile, 'avec ID:', testId);
            
            const statusEl = document.getElementById('status-' + testId);
            const outputEl = document.getElementById('output-' + testId);
            const durationEl = document.getElementById('duration-' + testId);
            
            if (!statusEl || !outputEl || !durationEl) {
                console.error('Éléments non trouvés pour le test ID:', testId);
                return;
            }
            
            statusEl.className = 'test-status running';
            statusEl.innerHTML = '⏳';
            outputEl.className = 'test-output show';
            outputEl.innerHTML = 'Exécution du test...';
            durationEl.innerHTML = '';
            
            fetch('?run=' + encodeURIComponent(testFile))
                .then(response => {
                    console.log('Réponse reçue:', response.status);
                    if (!response.ok) {
                        throw new Error('Erreur HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Données reçues:', data);
                    
                    if (data.success) {
                        statusEl.className = 'test-status success';
                        statusEl.innerHTML = '✓';
                        testResults.success++;
                    } else {
                        statusEl.className = 'test-status error';
                        statusEl.innerHTML = '✗';
                        testResults.error++;
                    }
                    
                    outputEl.innerHTML = '<pre>' + data.output.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</pre>';
                    durationEl.innerHTML = `Durée : ${data.duration}ms`;
                    
                    testResults.total++;
                    testResults.duration += data.duration;
                    updateSummary();
                })
                .catch(error => {
                    console.error('Erreur lors du test:', error);
                    statusEl.className = 'test-status error';
                    statusEl.innerHTML = '✗';
                    outputEl.innerHTML = '<pre>Erreur : ' + error.message + '</pre>';
                    testResults.error++;
                    testResults.total++;
                    updateSummary();
                });
        }
        
        function runAllTests() {
            clearResults();
            const tests = document.querySelectorAll('.test-card');
            tests.forEach((card, index) => {
                setTimeout(() => {
                    const testFile = card.dataset.test;
                    const testId = card.dataset.testId;
                    runTest(testFile, testId);
                }, index * 500); // Délai entre chaque test
            });
        }
        
        function clearResults() {
            testResults = { total: 0, success: 0, error: 0, duration: 0 };
            
            document.querySelectorAll('.test-status').forEach(el => {
                el.className = 'test-status';
                el.innerHTML = '';
            });
            
            document.querySelectorAll('.test-output').forEach(el => {
                el.className = 'test-output';
                el.innerHTML = '';
            });
            
            document.querySelectorAll('.test-duration').forEach(el => {
                el.innerHTML = '';
            });
            
            document.getElementById('results-summary').className = 'results-summary';
        }
        
        function updateSummary() {
            document.getElementById('results-summary').className = 'results-summary show';
            document.getElementById('stat-total').textContent = testResults.total;
            document.getElementById('stat-success').textContent = testResults.success;
            document.getElementById('stat-error').textContent = testResults.error;
            document.getElementById('stat-duration').textContent = Math.round(testResults.duration) + 'ms';
        }
        
        function viewTest(testFile) {
            window.open(testFile, '_blank');
        }
        
    </script>
</body>
</html>