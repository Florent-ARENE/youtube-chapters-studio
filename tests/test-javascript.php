<?php
/**
 * Test JavaScript et initialisation
 * Fusion de test-js-init.php et test-video-load.php
 */

session_start();
require_once '../config.php';
require_once '../functions.php';

// Mode de test
$testMode = $_GET['mode'] ?? 'dashboard';

// Simuler le chargement d'une vidéo pour les tests
$videoId = $_SESSION['video_id'] ?? '';
$videoTitle = $_SESSION['video_title'] ?? '';
$chapters = [];
$projectId = $_SESSION['project_id'] ?? null;

// Traitement du formulaire de chargement de vidéo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['youtube_url'])) {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = 'Erreur de sécurité. Veuillez recharger la page.';
    } else {
        $videoId = validateYouTubeUrl($_POST['youtube_url']);
        if ($videoId) {
            $videoTitle = 'Test Video Title';
            $_SESSION['video_id'] = $videoId;
            $_SESSION['video_title'] = $videoTitle;
            unset($_SESSION['project_id']);
            $chapters = [];
            $projectId = null;
            $success = true;
        } else {
            $error = 'URL YouTube invalide';
        }
    }
}

$csrfToken = $_SESSION['csrf_token'];

// Mode standalone
if ($testMode === 'standalone'):
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test JavaScript - YouTube Chapters Studio</title>
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
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
        input {
            width: 100%;
            padding: 8px;
            background: #2a2a2a;
            border: 1px solid #444;
            color: #fff;
            border-radius: 4px;
            margin: 5px 0;
        }
        #console {
            background: #000;
            color: #0f0;
            font-family: monospace;
            padding: 15px;
            border-radius: 5px;
            font-size: 12px;
            line-height: 1.4;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .debug-box {
            background: #0a0a0a;
            border: 1px solid #333;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
        }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .warning { color: #ffaa00; }
        .info { color: #00aaff; }
        .status-table {
            width: 100%;
            margin: 10px 0;
        }
        .status-table td {
            padding: 5px 10px;
            border-bottom: 1px solid #333;
        }
        .status-table td:first-child {
            color: #999;
            width: 40%;
        }
        .test-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 15px 0;
        }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            display: none;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }
        .notification.success {
            background: #00ff00;
            color: #000;
        }
        .notification.error {
            background: #ff0000;
            color: #fff;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .variable-state {
            background: #1a1a1a;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
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
    </style>
</head>
<body>
    <h1>📜 Test JavaScript et Initialisation</h1>
    
    <div class="grid-2">
        <!-- Test d'initialisation -->
        <div class="test-section">
            <h2>🔧 Test des variables globales</h2>
            
            <div class="debug-box">
                <strong>État PHP actuel :</strong><br>
                - videoId: <?php echo $videoId ?: 'vide'; ?><br>
                - videoTitle: <?php echo $videoTitle ?: 'vide'; ?><br>
                - projectId: <?php echo $projectId ?: 'null'; ?><br>
                - csrfToken: <?php echo substr($csrfToken, 0, 16); ?>...<br>
                - chapters: <?php echo count($chapters); ?> élément(s)
            </div>
            
            <div class="test-controls">
                <button onclick="testGlobals()">Test variables globales</button>
                <button onclick="testFunctions()">Test fonctions</button>
                <button onclick="testAppConfig()">Test appConfig</button>
                <button onclick="testAutoSave()">Test autoSave</button>
            </div>
            
            <div id="globals-result" class="variable-state"></div>
        </div>
        
        <!-- Test de chargement vidéo -->
        <div class="test-section">
            <h2>📹 Test de chargement vidéo</h2>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <label>URL YouTube :</label>
                <input type="url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." required>
                <button type="submit">Charger la vidéo</button>
            </form>
            
            <?php if (isset($error)): ?>
            <p class="error">❌ <?php echo $error; ?></p>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
            <p class="success">✅ Vidéo chargée avec succès!</p>
            <?php endif; ?>
            
            <div id="video-state" class="variable-state"></div>
        </div>
    </div>
    
    <div class="test-section">
        <h2>🧪 Tests d'intégration</h2>
        
        <div class="test-controls">
            <button onclick="simulateAddChapter()">Ajouter un chapitre test</button>
            <button onclick="testUpdateGlobals()">Test mise à jour variables</button>
            <button onclick="testFullFlow()">Test flux complet</button>
            <button onclick="runAllTests()">▶️ Tout tester</button>
        </div>
        
        <div id="integration-results"></div>
    </div>
    
    <div class="test-section">
        <h2>📊 État JavaScript en temps réel</h2>
        <table class="status-table">
            <tr>
                <td>window.appConfig :</td>
                <td id="js-appconfig">-</td>
            </tr>
            <tr>
                <td>currentVideoId :</td>
                <td id="js-videoid">-</td>
            </tr>
            <tr>
                <td>currentProjectId :</td>
                <td id="js-projectid">-</td>
            </tr>
            <tr>
                <td>chapters.length :</td>
                <td id="js-chapters">-</td>
            </tr>
            <tr>
                <td>autoSave fonction :</td>
                <td id="js-autosave">-</td>
            </tr>
        </table>
    </div>
    
    <div class="test-section">
        <h2>📋 Console</h2>
        <div id="console"></div>
    </div>
    
    <div id="save-notification" class="notification"></div>
    <div id="chapters-list" style="display: none;"></div>
    
    <a href="index.php" class="back-link">← Retour au dashboard des tests</a>
    
    <!-- Configuration comme dans index.php -->
    <script>
        // Variables globales sécurisées
        window.appConfig = {
            chapters: <?php echo json_encode($chapters); ?>,
            elus: [],
            projectId: <?php echo json_encode($projectId); ?>,
            videoId: <?php echo json_encode($videoId); ?>,
            videoTitle: <?php echo json_encode($videoTitle); ?>,
            csrfToken: <?php echo json_encode($csrfToken); ?>,
            maxChapters: <?php echo MAX_CHAPTERS; ?>,
            maxTitleLength: <?php echo MAX_TITLE_LENGTH; ?>
        };
        
        console.log('=== appConfig initial (avant app.js) ===');
        console.log(window.appConfig);
    </script>
    
    <!-- Charger app.js -->
    <script src="../app.js"></script>
    
    <script>
        // Console custom
        const consoleDiv = document.getElementById('console');
        let testResults = [];
        
        function log(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const entry = document.createElement('div');
            entry.className = type;
            entry.textContent = `[${timestamp}] ${message}`;
            consoleDiv.appendChild(entry);
            consoleDiv.scrollTop = consoleDiv.scrollHeight;
            
            // Log aussi dans la vraie console
            console.log(message);
        }
        
        // Override console.log pour capturer les logs
        const originalLog = console.log;
        console.log = function(...args) {
            originalLog.apply(console, args);
            log(args.join(' '), 'info');
        };
        
        const originalError = console.error;
        console.error = function(...args) {
            originalError.apply(console, args);
            log(args.join(' '), 'error');
        };
        
        function updateJsStatus() {
            document.getElementById('js-appconfig').textContent = 
                typeof window.appConfig !== 'undefined' ? 'Défini' : 'Non défini';
            document.getElementById('js-videoid').textContent = 
                typeof currentVideoId !== 'undefined' ? currentVideoId || 'vide' : 'Non défini';
            document.getElementById('js-projectid').textContent = 
                typeof currentProjectId !== 'undefined' ? currentProjectId || 'null' : 'Non défini';
            document.getElementById('js-chapters').textContent = 
                typeof chapters !== 'undefined' ? chapters.length : 'Non défini';
            document.getElementById('js-autosave').textContent = 
                typeof autoSave === 'function' ? 'Disponible' : 'Non disponible';
        }
        
        function showNotification(message, type = 'success') {
            const notif = document.getElementById('save-notification');
            notif.textContent = message;
            notif.className = `notification ${type}`;
            notif.style.display = 'block';
            
            setTimeout(() => {
                notif.style.display = 'none';
            }, 2000);
        }
        
        // Test 1: Variables globales
        function testGlobals() {
            log('=== Test des variables globales ===', 'info');
            testResults = [];
            
            // Test window.appConfig
            if (typeof window.appConfig !== 'undefined') {
                log('✅ window.appConfig existe', 'success');
                log('appConfig: ' + JSON.stringify(window.appConfig, null, 2), 'info');
                testResults.push({test: 'appConfig', success: true});
            } else {
                log('❌ window.appConfig n\'existe pas', 'error');
                testResults.push({test: 'appConfig', success: false});
            }
            
            // Test des variables depuis app.js
            log('--- Variables dans app.js ---', 'info');
            
            const vars = [
                'chapters', 'elus', 'currentVideoId', 'currentProjectId', 
                'currentVideoTitle', 'csrfToken', 'maxChapters', 'maxTitleLength'
            ];
            
            vars.forEach(varName => {
                if (typeof window[varName] !== 'undefined') {
                    log(`✅ ${varName}: ${JSON.stringify(window[varName])}`, 'success');
                    testResults.push({test: varName, success: true});
                } else {
                    log(`❌ ${varName}: non défini`, 'error');
                    testResults.push({test: varName, success: false});
                }
            });
            
            updateJsStatus();
            displayResults('globals-result');
        }
        
        // Test 2: Fonctions
        function testFunctions() {
            log('=== Test des fonctions ===', 'info');
            
            const functions = [
                'autoSave', 'saveChapters', 'showSaveNotification',
                'updateGlobalVariables', 'addOrUpdateChapter', 
                'captureCurrentTime', 'editChapter', 'deleteChapter'
            ];
            
            functions.forEach(funcName => {
                if (typeof window[funcName] === 'function') {
                    log(`✅ ${funcName}: fonction disponible`, 'success');
                } else {
                    log(`❌ ${funcName}: non disponible`, 'error');
                }
            });
        }
        
        // Test 3: appConfig
        function testAppConfig() {
            log('=== Test appConfig ===', 'info');
            
            if (typeof window.appConfig === 'undefined') {
                log('❌ appConfig non défini', 'error');
                return;
            }
            
            const expected = ['chapters', 'elus', 'projectId', 'videoId', 
                            'videoTitle', 'csrfToken', 'maxChapters', 'maxTitleLength'];
            
            expected.forEach(key => {
                if (key in window.appConfig) {
                    log(`✅ appConfig.${key}: ${JSON.stringify(window.appConfig[key])}`, 'success');
                } else {
                    log(`❌ appConfig.${key}: manquant`, 'error');
                }
            });
        }
        
        // Test 4: AutoSave
        function testAutoSave() {
            log('=== Test de autoSave() ===', 'info');
            
            if (typeof autoSave === 'function') {
                log('✅ autoSave est une fonction', 'success');
                
                // Vérifier currentVideoId
                if (typeof currentVideoId !== 'undefined' && currentVideoId) {
                    log('currentVideoId défini: ' + currentVideoId, 'info');
                    log('Appel de autoSave()...', 'info');
                    
                    try {
                        autoSave();
                        log('✅ autoSave() appelé sans erreur', 'success');
                    } catch (e) {
                        log('❌ Erreur lors de l\'appel: ' + e.message, 'error');
                    }
                } else {
                    log('⚠️ currentVideoId non défini, autoSave ne fonctionnera pas', 'warning');
                }
            } else {
                log('❌ autoSave n\'est pas définie', 'error');
            }
        }
        
        // Test 5: Mise à jour des variables
        function testUpdateGlobals() {
            log('=== Test updateGlobalVariables ===', 'info');
            
            if (typeof updateGlobalVariables !== 'function') {
                log('❌ updateGlobalVariables non disponible', 'error');
                return;
            }
            
            const testConfig = {
                videoId: 'test123',
                videoTitle: 'Test Update',
                projectId: 'proj123',
                chapters: [{time: 0, title: 'Updated'}]
            };
            
            log('Mise à jour avec: ' + JSON.stringify(testConfig), 'info');
            updateGlobalVariables(testConfig);
            
            setTimeout(() => {
                log('Vérification après mise à jour:', 'info');
                log('currentVideoId: ' + currentVideoId, 'info');
                log('currentProjectId: ' + currentProjectId, 'info');
                updateJsStatus();
            }, 100);
        }
        
        // Test 6: Ajout de chapitre
        function simulateAddChapter() {
            log('=== Simulation ajout chapitre ===', 'info');
            
            if (typeof chapters === 'undefined') {
                log('❌ Variable chapters non définie', 'error');
                return;
            }
            
            if (!currentVideoId) {
                log('⚠️ Pas de vidéo chargée, forçage d\'un ID', 'warning');
                currentVideoId = 'test-video-id';
            }
            
            const testChapter = {
                time: 60,
                title: 'Chapitre de test ' + Date.now(),
                type: 'chapitre'
            };
            
            chapters.push(testChapter);
            log('Chapitre ajouté: ' + JSON.stringify(testChapter), 'success');
            log('Nombre de chapitres: ' + chapters.length, 'info');
            
            if (typeof updateChaptersList === 'function') {
                updateChaptersList();
            }
            
            if (typeof autoSave === 'function') {
                log('Appel de autoSave()...', 'info');
                autoSave();
            }
            
            updateJsStatus();
        }
        
        // Test 7: Flux complet
        function testFullFlow() {
            log('=== Test flux complet ===', 'info');
            
            // 1. Vérifier/forcer les variables
            if (!currentVideoId) {
                currentVideoId = 'dQw4w9WgXcQ';
                currentVideoTitle = 'Test complet';
                log('Variables forcées', 'warning');
            }
            
            // 2. Ajouter plusieurs chapitres
            const testChapters = [
                {time: 0, title: 'Introduction', type: 'chapitre'},
                {time: 60, title: 'Jean TEST', type: 'elu', elu: {nom: 'Jean TEST'}, showInfo: true},
                {time: 120, title: 'Vote test', type: 'vote'}
            ];
            
            chapters.length = 0;
            chapters.push(...testChapters);
            log('3 chapitres ajoutés', 'success');
            
            // 3. Appeler autoSave
            if (typeof autoSave === 'function') {
                log('Sauvegarde automatique...', 'info');
                autoSave();
            }
            
            updateJsStatus();
        }
        
        // Exécuter tous les tests
        function runAllTests() {
            log('=== EXÉCUTION DE TOUS LES TESTS ===', 'info');
            testResults = [];
            
            testGlobals();
            setTimeout(() => testFunctions(), 500);
            setTimeout(() => testAppConfig(), 1000);
            setTimeout(() => testAutoSave(), 1500);
            setTimeout(() => {
                log('\n=== RÉSUMÉ DES TESTS ===', 'info');
                const success = testResults.filter(r => r.success).length;
                const total = testResults.length;
                log(`Tests réussis: ${success}/${total}`, 
                    success === total ? 'success' : 'warning');
            }, 2000);
        }
        
        function displayResults(elementId) {
            const element = document.getElementById(elementId);
            if (!element) return;
            
            const success = testResults.filter(r => r.success).length;
            const total = testResults.length;
            
            element.innerHTML = `
                <strong>Résultats:</strong> ${success}/${total} tests réussis<br>
                ${testResults.map(r => 
                    `${r.success ? '✅' : '❌'} ${r.test}`
                ).join('<br>')}
            `;
        }
        
        // Créer les éléments cachés nécessaires
        function createHiddenElements() {
            const elements = [
                '<input type="hidden" id="hours" value="00">',
                '<input type="hidden" id="minutes" value="00">',
                '<input type="hidden" id="seconds" value="00">',
                '<input type="hidden" id="chapter-title" value="">',
                '<input type="hidden" id="chapter-type" value="chapitre">',
                '<input type="hidden" id="editing-index" value="-1">',
                '<input type="hidden" id="selected-elu-data" value="">'
            ];
            
            elements.forEach(html => {
                document.body.insertAdjacentHTML('beforeend', html);
            });
        }
        
        // Initialisation
        window.addEventListener('DOMContentLoaded', function() {
            log('=== Page chargée ===', 'success');
            
            createHiddenElements();
            
            // Mise à jour initiale du statut
            updateJsStatus();
            
            // Vérification après un délai
            setTimeout(function() {
                log('Vérification après chargement...', 'info');
                
                if (window.appConfig && window.appConfig.videoId) {
                    if (typeof currentVideoId !== 'undefined' && !currentVideoId) {
                        log('⚠️ Variables non synchronisées, correction...', 'warning');
                        
                        if (typeof updateGlobalVariables === 'function') {
                            updateGlobalVariables(window.appConfig);
                            log('✅ Variables mises à jour', 'success');
                        }
                    }
                }
                
                updateJsStatus();
            }, 500);
            
            // Mise à jour périodique du statut
            setInterval(updateJsStatus, 2000);
        });
    </script>
</body>
</html>
<?php exit; endif; ?>

<?php
// Mode dashboard (intégré)
echo "=== Tests JavaScript et Initialisation ===\n\n";

echo "Configuration PHP :\n";
echo "- Session démarrée : " . (session_status() === PHP_SESSION_ACTIVE ? "Oui" : "Non") . "\n";
echo "- CSRF Token : " . (isset($_SESSION['csrf_token']) ? substr($_SESSION['csrf_token'], 0, 16) . "..." : "Non généré") . "\n";
echo "- Video ID : " . ($_SESSION['video_id'] ?? "Non défini") . "\n";
echo "- Project ID : " . ($_SESSION['project_id'] ?? "Non défini") . "\n\n";

echo "Tests disponibles :\n";
echo "- Variables globales JavaScript\n";
echo "- Fonctions d'initialisation\n";
echo "- Chargement de vidéo\n";
echo "- Sauvegarde automatique\n";
echo "- Flux complet d'utilisation\n";
?>