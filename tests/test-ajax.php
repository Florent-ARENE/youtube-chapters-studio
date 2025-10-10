<?php
/**
 * Test AJAX et sauvegarde automatique
 * Fusion de test-save.php et test-simple-save.html
 */

// Démarrer la session seulement si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

// Mode de test
$testMode = $_GET['mode'] ?? 'dashboard';

// Générer un token CSRF si nécessaire
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// API endpoint pour les tests
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['api']) {
        case 'csrf':
            echo json_encode(['token' => $_SESSION['csrf_token']]);
            break;
            
        case 'session':
            echo json_encode([
                'csrf_token' => substr($_SESSION['csrf_token'] ?? '', 0, 16) . '...',
                'video_id' => $_SESSION['video_id'] ?? null,
                'project_id' => $_SESSION['project_id'] ?? null,
                'project_count' => $_SESSION['project_count'] ?? 0
            ]);
            break;
            
        case 'clear':
            session_destroy();
            session_start();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            echo json_encode(['success' => true]);
            break;
    }
    exit;
}

// Mode standalone
if ($testMode === 'standalone'):
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test AJAX et Sauvegarde - YouTube Chapters Studio</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #0f0f0f;
            color: #fff;
            padding: 20px;
            max-width: 800px;
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
        .test-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        button {
            padding: 10px 20px;
            background: #ff0000;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
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
        .status-table {
            width: 100%;
            margin: 20px 0;
        }
        .status-table td {
            padding: 5px 10px;
            border-bottom: 1px solid #333;
        }
        .status-table td:first-child {
            color: #999;
            width: 150px;
        }
        .input-group {
            margin: 10px 0;
        }
        .input-group input {
            width: 100%;
            padding: 8px;
            background: #2a2a2a;
            border: 1px solid #444;
            color: #fff;
            border-radius: 4px;
        }
        .test-result {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .test-result.success {
            background: rgba(0, 255, 0, 0.2);
            border: 1px solid #00ff00;
        }
        .test-result.error {
            background: rgba(255, 0, 0, 0.2);
            border: 1px solid #ff0000;
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
    <h1>🧪 Test AJAX et Sauvegarde Automatique</h1>
    
    <div class="test-section">
        <h2>📊 État actuel</h2>
        <table class="status-table">
            <tr>
                <td>Token CSRF :</td>
                <td id="csrf-status"><?php echo substr($_SESSION['csrf_token'], 0, 16); ?>...</td>
            </tr>
            <tr>
                <td>Video ID :</td>
                <td id="video-status"><?php echo $_SESSION['video_id'] ?? 'Non défini'; ?></td>
            </tr>
            <tr>
                <td>Project ID :</td>
                <td id="project-status"><?php echo $_SESSION['project_id'] ?? 'Non défini'; ?></td>
            </tr>
            <tr>
                <td>Nombre de projets :</td>
                <td id="count-status"><?php echo $_SESSION['project_count'] ?? 0; ?></td>
            </tr>
        </table>
    </div>
    
    <div class="test-section">
        <h2>🔧 Configuration du test</h2>
        <div class="input-group">
            <label>ID de la vidéo YouTube :</label>
            <input type="text" id="video-id" value="dQw4w9WgXcQ" placeholder="Ex: dQw4w9WgXcQ">
        </div>
        <div class="input-group">
            <label>Titre de la vidéo :</label>
            <input type="text" id="video-title" value="Test de sauvegarde automatique" placeholder="Titre de test">
        </div>
    </div>
    
    <div class="test-section">
        <h2>🧪 Tests disponibles</h2>
        <div class="test-controls">
            <button onclick="testConnection()">1. Tester connexion AJAX</button>
            <button onclick="testGetCsrf()">2. Récupérer token CSRF</button>
            <button onclick="testSimpleSave()">3. Test simple sauvegarde</button>
            <button onclick="testComplexSave()">4. Test sauvegarde complexe</button>
            <button onclick="testAutoSave()">5. Simuler auto-save</button>
            <button onclick="testErrorHandling()">6. Test gestion d'erreurs</button>
            <button onclick="clearSession()" class="secondary">Effacer session</button>
            <button onclick="runAllTests()">▶️ Tout tester</button>
        </div>
    </div>
    
    <div class="test-section">
        <h2>📋 Console</h2>
        <div id="console"></div>
    </div>
    
    <div id="notification" class="notification"></div>
    
    <a href="index.php" class="back-link">← Retour au dashboard des tests</a>
    
    <script>
        let csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
        const console = document.getElementById('console');
        let testResults = [];
        
        function log(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'success' ? '#00ff00' : type === 'error' ? '#ff0000' : '#fff';
            console.innerHTML += `<span style="color: ${color}">[${timestamp}] ${message}</span>\n`;
            console.scrollTop = console.scrollHeight;
        }
        
        function showNotification(message, type = 'success') {
            const notif = document.getElementById('notification');
            notif.textContent = message;
            notif.className = `notification ${type}`;
            notif.style.display = 'block';
            
            setTimeout(() => {
                notif.style.display = 'none';
            }, 2000);
        }
        
        function updateStatus() {
            fetch('?api=session')
                .then(r => r.json())
                .then(data => {
                    document.getElementById('csrf-status').textContent = data.csrf_token;
                    document.getElementById('video-status').textContent = data.video_id || 'Non défini';
                    document.getElementById('project-status').textContent = data.project_id || 'Non défini';
                    document.getElementById('count-status').textContent = data.project_count;
                });
        }
        
        // Test 1: Connexion AJAX basique
        function testConnection() {
            log('=== Test 1: Connexion AJAX ===');
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../ajax-handler.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        log('✅ Connexion réussie: ' + JSON.stringify(data), 'success');
                        testResults.push({test: 'connection', success: true});
                    } catch (e) {
                        log('❌ Erreur parsing: ' + e.message, 'error');
                        testResults.push({test: 'connection', success: false});
                    }
                } else {
                    log('❌ Erreur HTTP ' + xhr.status, 'error');
                    testResults.push({test: 'connection', success: false});
                }
            };
            
            xhr.onerror = function() {
                log('❌ Erreur réseau', 'error');
                testResults.push({test: 'connection', success: false});
            };
            
            xhr.send('action=test');
        }
        
        // Test 2: Récupération token CSRF
        function testGetCsrf() {
            log('=== Test 2: Récupération token CSRF ===');
            
            fetch('?api=csrf')
                .then(r => r.json())
                .then(data => {
                    csrfToken = data.token;
                    log('✅ Token CSRF récupéré: ' + csrfToken.substr(0, 16) + '...', 'success');
                    testResults.push({test: 'csrf', success: true});
                })
                .catch(error => {
                    log('❌ Erreur: ' + error.message, 'error');
                    testResults.push({test: 'csrf', success: false});
                });
        }
        
        // Test 3: Sauvegarde simple
        function testSimpleSave() {
            log('=== Test 3: Sauvegarde simple ===');
            
            const videoId = document.getElementById('video-id').value;
            const videoTitle = document.getElementById('video-title').value;
            
            const chapters = [
                { time: 0, title: 'Introduction', type: 'chapitre' },
                { time: 60, title: 'Chapitre 1', type: 'chapitre' },
                { time: 120, title: 'Conclusion', type: 'chapitre' }
            ];
            
            const params = new URLSearchParams({
                action: 'save_chapters',
                csrf_token: csrfToken,
                video_id: videoId,
                video_title: videoTitle,
                chapters: JSON.stringify(chapters),
                project_id: ''
            });
            
            log('Envoi de ' + chapters.length + ' chapitres...');
            
            fetch('../ajax-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: params
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    log('✅ Sauvegarde réussie!', 'success');
                    log('Project ID: ' + data.project_id, 'success');
                    showNotification('Sauvegardé avec succès');
                    testResults.push({test: 'simple_save', success: true});
                    updateStatus();
                } else {
                    log('❌ Erreur: ' + data.error, 'error');
                    testResults.push({test: 'simple_save', success: false});
                }
            })
            .catch(error => {
                log('❌ Erreur: ' + error.message, 'error');
                testResults.push({test: 'simple_save', success: false});
            });
        }
        
        // Test 4: Sauvegarde complexe (avec élus et votes)
        function testComplexSave() {
            log('=== Test 4: Sauvegarde complexe ===');
            
            const videoId = document.getElementById('video-id').value;
            const videoTitle = document.getElementById('video-title').value;
            
            const chapters = [
                { time: 0, title: 'Introduction', type: 'chapitre' },
                { 
                    time: 60, 
                    type: 'elu',
                    title: 'Jean DUPONT',
                    elu: {
                        nom: 'Jean DUPONT',
                        fonction: 'Conseiller départemental',
                        majo: 'Majorité',
                        groupe: 'Groupe Test'
                    },
                    showInfo: true
                },
                {
                    time: 180,
                    type: 'vote',
                    title: 'Vote du budget 2025'
                },
                { time: 300, title: 'Conclusion', type: 'chapitre' }
            ];
            
            const params = new URLSearchParams({
                action: 'save_chapters',
                csrf_token: csrfToken,
                video_id: videoId,
                video_title: videoTitle,
                chapters: JSON.stringify(chapters),
                project_id: ''
            });
            
            log('Envoi de ' + chapters.length + ' chapitres (incluant élu et vote)...');
            
            fetch('../ajax-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: params
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    log('✅ Sauvegarde complexe réussie!', 'success');
                    testResults.push({test: 'complex_save', success: true});
                    updateStatus();
                } else {
                    log('❌ Erreur: ' + data.error, 'error');
                    testResults.push({test: 'complex_save', success: false});
                }
            });
        }
        
        // Test 5: Simulation auto-save
        function testAutoSave() {
            log('=== Test 5: Simulation auto-save ===');
            log('Simulation d\'ajout de chapitre et sauvegarde automatique...');
            
            // Simuler l'ajout d'un chapitre
            const newChapter = {
                time: Math.floor(Math.random() * 600),
                title: 'Chapitre auto-généré ' + Date.now(),
                type: 'chapitre'
            };
            
            log('Nouveau chapitre: ' + JSON.stringify(newChapter));
            
            // Simuler un délai de 500ms (comme dans autoSave)
            setTimeout(() => {
                testSimpleSave();
            }, 500);
        }
        
        // Test 6: Gestion d'erreurs
        function testErrorHandling() {
            log('=== Test 6: Test gestion d\'erreurs ===');
            
            // Test avec token CSRF invalide
            log('Test 1: Token CSRF invalide...');
            const params1 = new URLSearchParams({
                action: 'save_chapters',
                csrf_token: 'invalid_token',
                video_id: 'test',
                chapters: '[]'
            });
            
            fetch('../ajax-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: params1
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    log('✅ Erreur CSRF détectée correctement: ' + data.error, 'success');
                    testResults.push({test: 'error_csrf', success: true});
                }
            });
            
            // Test avec ID vidéo invalide
            setTimeout(() => {
                log('Test 2: ID vidéo invalide...');
                const params2 = new URLSearchParams({
                    action: 'save_chapters',
                    csrf_token: csrfToken,
                    video_id: 'invalid',
                    chapters: '[]'
                });
                
                fetch('../ajax-handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: params2
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        log('✅ Erreur ID vidéo détectée: ' + data.error, 'success');
                        testResults.push({test: 'error_video_id', success: true});
                    }
                });
            }, 1000);
        }
        
        // Effacer la session
        function clearSession() {
            if (confirm('Effacer toutes les variables de session ?')) {
                fetch('?api=clear')
                    .then(r => r.json())
                    .then(() => {
                        log('✅ Session effacée', 'success');
                        showNotification('Session effacée');
                        updateStatus();
                    });
            }
        }
        
        // Exécuter tous les tests
        function runAllTests() {
            log('=== EXÉCUTION DE TOUS LES TESTS ===');
            testResults = [];
            
            testConnection();
            setTimeout(() => testGetCsrf(), 1000);
            setTimeout(() => testSimpleSave(), 2000);
            setTimeout(() => testComplexSave(), 3500);
            setTimeout(() => testAutoSave(), 5000);
            setTimeout(() => testErrorHandling(), 6500);
            
            setTimeout(() => {
                log('\n=== RÉSUMÉ DES TESTS ===');
                const success = testResults.filter(r => r.success).length;
                const total = testResults.length;
                const percentage = Math.round((success / total) * 100);
                
                log(`Tests réussis: ${success}/${total} (${percentage}%)`, 
                    percentage === 100 ? 'success' : percentage >= 50 ? 'info' : 'error');
                
                testResults.forEach(result => {
                    log(`${result.success ? '✅' : '❌'} ${result.test}`, 
                        result.success ? 'success' : 'error');
                });
            }, 9000);
        }
        
        // Au chargement
        log('Test AJAX et sauvegarde prêt');
        log('Token CSRF actuel: ' + csrfToken.substr(0, 16) + '...');
    </script>
</body>
</html>
<?php exit; endif; ?>

<?php
// Mode dashboard (intégré)
if (isset($_GET['test'])) {
    // Exécuter les tests en mode API
    $results = [];
    
    // Test 1: Vérification AJAX handler
    $results[] = "=== Test AJAX Handler ===";
    if (file_exists('../ajax-handler.php')) {
        $results[] = "✅ ajax-handler.php existe";
    } else {
        $results[] = "❌ ajax-handler.php introuvable";
    }
    
    // Test 2: Session et CSRF
    $results[] = "\n=== Test Session et CSRF ===";
    if (isset($_SESSION['csrf_token'])) {
        $results[] = "✅ Token CSRF généré: " . substr($_SESSION['csrf_token'], 0, 16) . "...";
    } else {
        $results[] = "❌ Token CSRF non généré";
    }
    
    // Test 3: Variables de session
    $results[] = "\n=== Variables de session ===";
    $results[] = "Video ID: " . ($_SESSION['video_id'] ?? 'Non défini');
    $results[] = "Project ID: " . ($_SESSION['project_id'] ?? 'Non défini');
    $results[] = "Project count: " . ($_SESSION['project_count'] ?? 0);
    
    // Test 4: Configuration
    $results[] = "\n=== Configuration ===";
    $results[] = "MAX_CHAPTERS: " . MAX_CHAPTERS;
    $results[] = "MAX_TITLE_LENGTH: " . MAX_TITLE_LENGTH;
    $results[] = "DATA_DIR: " . DATA_DIR;
    
    echo implode("\n", $results);
} else {
    // Afficher les résultats basiques pour le dashboard
    echo "Tests AJAX et sauvegarde disponibles\n";
    echo "Cliquez sur 'Voir le test' pour l'interface complète";
}
?>