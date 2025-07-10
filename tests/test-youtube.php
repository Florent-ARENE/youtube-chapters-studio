<?php
/**
 * Test API YouTube - Titres et Player
 * Fusion de test-title.php et test-player.html
 */

// Mode de test
$testMode = $_GET['mode'] ?? 'dashboard';

// Tests de titre via API
if (isset($_GET['api']) && $_GET['api'] === 'test-title') {
    header('Content-Type: application/json');
    
    $videoId = $_GET['video_id'] ?? 'dQw4w9WgXcQ';
    $results = [];
    
    // Test 1 : noembed.com
    $url = "https://noembed.com/embed?url=https://www.youtube.com/watch?v=" . urlencode($videoId);
    $context = stream_context_create([
        'http' => ['timeout' => 10, 'user_agent' => 'Mozilla/5.0'],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['title'])) {
            $results['noembed'] = ['success' => true, 'title' => $data['title']];
        } else {
            $results['noembed'] = ['success' => false, 'error' => 'Pas de titre dans la réponse'];
        }
    } else {
        $results['noembed'] = ['success' => false, 'error' => 'Erreur de connexion'];
    }
    
    // Test 2 : YouTube oEmbed
    $url = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=" . urlencode($videoId) . "&format=json";
    $response = @file_get_contents($url, false, $context);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['title'])) {
            $results['oembed'] = ['success' => true, 'title' => $data['title']];
        } else {
            $results['oembed'] = ['success' => false, 'error' => 'Pas de titre dans la réponse'];
        }
    } else {
        $results['oembed'] = ['success' => false, 'error' => 'Erreur de connexion'];
    }
    
    // Test 3 : cURL
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=" . urlencode($videoId) . "&format=json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['title'])) {
                $results['curl'] = ['success' => true, 'title' => $data['title']];
            } else {
                $results['curl'] = ['success' => false, 'error' => 'Pas de titre dans la réponse'];
            }
        } else {
            $results['curl'] = ['success' => false, 'error' => 'Code HTTP ' . $httpCode];
        }
    } else {
        $results['curl'] = ['success' => false, 'error' => 'cURL non disponible'];
    }
    
    echo json_encode($results);
    exit;
}

// Mode standalone
if ($testMode === 'standalone'):
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test YouTube API - YouTube Chapters Studio</title>
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
        #player {
            width: 100%;
            aspect-ratio: 16/9;
            background: #000;
            border-radius: 8px;
            margin: 20px 0;
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
        .test-result {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #333;
        }
        .test-result.success {
            background: rgba(0, 255, 0, 0.1);
            border-color: #00ff00;
        }
        .test-result.error {
            background: rgba(255, 0, 0, 0.1);
            border-color: #ff0000;
        }
        .time-display {
            background: #2a2a2a;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-size: 24px;
            font-family: monospace;
            margin: 10px 0;
        }
        .player-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 10px 0;
        }
        .info-table {
            width: 100%;
            margin: 10px 0;
        }
        .info-table td {
            padding: 5px 10px;
            border-bottom: 1px solid #333;
        }
        .info-table td:first-child {
            color: #999;
            width: 200px;
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
        .loading {
            text-align: center;
            color: #999;
            padding: 20px;
        }
    </style>
</head>
<body>
    <h1>📺 Test YouTube API</h1>
    
    <div class="grid-2">
        <!-- Test des titres -->
        <div class="test-section">
            <h2>📝 Test de récupération des titres</h2>
            
            <div>
                <label>ID de la vidéo YouTube :</label>
                <input type="text" id="video-id-title" value="dQw4w9WgXcQ" placeholder="Ex: dQw4w9WgXcQ">
            </div>
            
            <table class="info-table">
                <tr>
                    <td>PHP Version :</td>
                    <td><?php echo phpversion(); ?></td>
                </tr>
                <tr>
                    <td>allow_url_fopen :</td>
                    <td><?php echo ini_get('allow_url_fopen') ? '✅ Activé' : '❌ Désactivé'; ?></td>
                </tr>
                <tr>
                    <td>cURL :</td>
                    <td><?php echo function_exists('curl_init') ? '✅ Disponible' : '❌ Non disponible'; ?></td>
                </tr>
            </table>
            
            <button onclick="testSingleTitle()">Tester cette vidéo</button>
            <button onclick="testMultipleTitles()">Tester plusieurs vidéos</button>
            
            <div id="title-results"></div>
        </div>
        
        <!-- Test du player -->
        <div class="test-section">
            <h2>🎬 Test du YouTube Player</h2>
            
            <div>
                <label>ID de la vidéo à charger :</label>
                <input type="text" id="video-id-player" value="dQw4w9WgXcQ" placeholder="Ex: dQw4w9WgXcQ">
                <button onclick="loadVideo()">Charger la vidéo</button>
            </div>
            
            <div id="player"></div>
            
            <div class="time-display" id="time-display">--:--:--</div>
            
            <div class="player-controls">
                <button onclick="playVideo()">▶️ Lecture</button>
                <button onclick="pauseVideo()">⏸️ Pause</button>
                <button onclick="captureTime()">⏱️ Capturer le temps</button>
                <button onclick="seekToTime(60)">➡️ Aller à 1:00</button>
            </div>
            
            <div id="player-status" class="test-result">
                État du player : Non initialisé
            </div>
        </div>
    </div>
    
    <div class="test-section">
        <h2>📋 Console de debug</h2>
        <div id="console"></div>
    </div>
    
    <div class="test-section">
        <h2>🧪 Tests combinés</h2>
        <button onclick="runAllTests()">▶️ Exécuter tous les tests</button>
        <button onclick="testPlayerMethods()">🔧 Tester les méthodes du player</button>
        <button onclick="clearConsole()" class="secondary">🗑️ Effacer la console</button>
    </div>
    
    <a href="index.php" class="back-link">← Retour au dashboard des tests</a>
    
    <script>
        let player;
        let playerReady = false;
        const consoleDiv = document.getElementById('console');
        
        function log(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'success' ? '#00ff00' : type === 'error' ? '#ff0000' : '#fff';
            consoleDiv.innerHTML += `<span style="color: ${color}">[${timestamp}] ${message}</span>\n`;
            consoleDiv.scrollTop = consoleDiv.scrollHeight;
        }
        
        function clearConsole() {
            consoleDiv.innerHTML = '';
            log('Console effacée');
        }
        
        // === Tests des titres ===
        function testSingleTitle() {
            const videoId = document.getElementById('video-id-title').value;
            const resultsDiv = document.getElementById('title-results');
            
            log(`Test de récupération du titre pour : ${videoId}`);
            resultsDiv.innerHTML = '<div class="loading">Récupération en cours...</div>';
            
            fetch(`?api=test-title&video_id=${encodeURIComponent(videoId)}`)
                .then(r => r.json())
                .then(results => {
                    resultsDiv.innerHTML = '';
                    
                    // Test noembed
                    const noembedDiv = document.createElement('div');
                    noembedDiv.className = `test-result ${results.noembed.success ? 'success' : 'error'}`;
                    noembedDiv.innerHTML = `
                        <strong>noembed.com :</strong><br>
                        ${results.noembed.success 
                            ? '✅ ' + results.noembed.title 
                            : '❌ ' + results.noembed.error}
                    `;
                    resultsDiv.appendChild(noembedDiv);
                    
                    // Test oEmbed
                    const oembedDiv = document.createElement('div');
                    oembedDiv.className = `test-result ${results.oembed.success ? 'success' : 'error'}`;
                    oembedDiv.innerHTML = `
                        <strong>YouTube oEmbed :</strong><br>
                        ${results.oembed.success 
                            ? '✅ ' + results.oembed.title 
                            : '❌ ' + results.oembed.error}
                    `;
                    resultsDiv.appendChild(oembedDiv);
                    
                    // Test cURL
                    const curlDiv = document.createElement('div');
                    curlDiv.className = `test-result ${results.curl.success ? 'success' : 'error'}`;
                    curlDiv.innerHTML = `
                        <strong>cURL :</strong><br>
                        ${results.curl.success 
                            ? '✅ ' + results.curl.title 
                            : '❌ ' + results.curl.error}
                    `;
                    resultsDiv.appendChild(curlDiv);
                    
                    // Log des résultats
                    const successCount = Object.values(results).filter(r => r.success).length;
                    log(`Résultats : ${successCount}/3 méthodes ont réussi`, 
                        successCount > 0 ? 'success' : 'error');
                })
                .catch(error => {
                    resultsDiv.innerHTML = `<div class="test-result error">Erreur : ${error.message}</div>`;
                    log('Erreur : ' + error.message, 'error');
                });
        }
        
        function testMultipleTitles() {
            const testVideos = [
                { id: 'dQw4w9WgXcQ', name: 'Rick Astley - Never Gonna Give You Up' },
                { id: 'jNQXAC9IVRw', name: 'Me at the zoo (première vidéo YouTube)' },
                { id: '9bZkp7q19f0', name: 'PSY - Gangnam Style' }
            ];
            
            const resultsDiv = document.getElementById('title-results');
            resultsDiv.innerHTML = '<div class="loading">Test de plusieurs vidéos...</div>';
            
            log('=== Test de plusieurs vidéos ===');
            
            Promise.all(testVideos.map(video => 
                fetch(`?api=test-title&video_id=${video.id}`)
                    .then(r => r.json())
                    .then(results => ({
                        ...video,
                        results
                    }))
            )).then(allResults => {
                resultsDiv.innerHTML = '';
                
                allResults.forEach(video => {
                    const videoDiv = document.createElement('div');
                    videoDiv.className = 'test-result';
                    
                    const success = Object.values(video.results).some(r => r.success);
                    const title = success 
                        ? Object.values(video.results).find(r => r.success)?.title 
                        : 'Échec';
                    
                    videoDiv.innerHTML = `
                        <strong>${video.name}</strong><br>
                        ID: ${video.id}<br>
                        Titre récupéré: ${success ? '✅ ' + title : '❌ Échec'}
                    `;
                    
                    resultsDiv.appendChild(videoDiv);
                    log(`${video.id}: ${success ? 'OK' : 'Échec'}`, success ? 'success' : 'error');
                });
            });
        }
        
        // === Tests du player ===
        
        // Cette fonction DOIT être globale pour l'API YouTube
        window.onYouTubeIframeAPIReady = function() {
            log('API YouTube prête, création du player...');
            
            const videoId = document.getElementById('video-id-player').value;
            
            player = new YT.Player('player', {
                height: '100%',
                width: '100%',
                videoId: videoId,
                playerVars: {
                    'autoplay': 0,
                    'controls': 1,
                    'rel': 0
                },
                events: {
                    'onReady': onPlayerReady,
                    'onStateChange': onPlayerStateChange,
                    'onError': onPlayerError
                }
            });
        };
        
        function onPlayerReady(event) {
            playerReady = true;
            log('Player prêt !', 'success');
            updatePlayerStatus('Player prêt');
            
            // Lister les méthodes disponibles
            const methods = Object.keys(player).filter(k => typeof player[k] === 'function');
            log('Méthodes disponibles : ' + methods.length);
        }
        
        function onPlayerStateChange(event) {
            const states = {
                '-1': 'Non démarré',
                '0': 'Terminé',
                '1': 'En lecture',
                '2': 'En pause',
                '3': 'En mémoire tampon',
                '5': 'Vidéo en file'
            };
            const state = states[event.data] || 'Inconnu';
            log('État changé : ' + state);
            updatePlayerStatus('État : ' + state);
        }
        
        function onPlayerError(event) {
            const errors = {
                '2': 'ID de vidéo invalide',
                '5': 'Erreur HTML5',
                '100': 'Vidéo non trouvée',
                '101': 'Vidéo privée ou intégration désactivée',
                '150': 'Vidéo privée ou intégration désactivée'
            };
            const error = errors[event.data] || 'Erreur inconnue';
            log('ERREUR : ' + error, 'error');
            updatePlayerStatus('Erreur : ' + error, 'error');
        }
        
        function updatePlayerStatus(message, type = 'info') {
            const statusDiv = document.getElementById('player-status');
            statusDiv.className = `test-result ${type === 'error' ? 'error' : 'success'}`;
            statusDiv.textContent = 'État du player : ' + message;
        }
        
        function loadVideo() {
            const videoId = document.getElementById('video-id-player').value;
            log(`Chargement de la vidéo : ${videoId}`);
            
            if (player && player.loadVideoById) {
                player.loadVideoById(videoId);
            } else {
                log('Player non initialisé, création...', 'error');
                // Recharger l'API
                const tag = document.createElement('script');
                tag.src = "https://www.youtube.com/iframe_api";
                const firstScriptTag = document.getElementsByTagName('script')[0];
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
            }
        }
        
        function playVideo() {
            if (player && player.playVideo) {
                player.playVideo();
                log('Lecture demandée');
            } else {
                log('Player non prêt', 'error');
            }
        }
        
        function pauseVideo() {
            if (player && player.pauseVideo) {
                player.pauseVideo();
                log('Pause demandée');
            } else {
                log('Player non prêt', 'error');
            }
        }
        
        function captureTime() {
            if (!playerReady || !player || !player.getCurrentTime) {
                log('Player non prêt pour capturer le temps', 'error');
                return;
            }
            
            try {
                const currentTime = player.getCurrentTime();
                const totalSeconds = Math.floor(currentTime);
                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;
                
                const timeStr = hours.toString().padStart(2, '0') + ':' +
                               minutes.toString().padStart(2, '0') + ':' +
                               seconds.toString().padStart(2, '0');
                
                document.getElementById('time-display').textContent = timeStr;
                log('Temps capturé : ' + timeStr + ' (' + currentTime + ' secondes)', 'success');
            } catch (error) {
                log('Erreur lors de la capture : ' + error.message, 'error');
            }
        }
        
        function seekToTime(seconds) {
            if (player && player.seekTo) {
                player.seekTo(seconds, true);
                log(`Navigation vers ${seconds} secondes`);
            } else {
                log('Player non prêt', 'error');
            }
        }
        
        function testPlayerMethods() {
            log('=== Test des méthodes du player ===');
            
            if (!player) {
                log('Player non défini', 'error');
                return;
            }
            
            const tests = [
                { method: 'getCurrentTime', test: () => player.getCurrentTime() },
                { method: 'getDuration', test: () => player.getDuration() },
                { method: 'getVolume', test: () => player.getVolume() },
                { method: 'getPlayerState', test: () => player.getPlayerState() },
                { method: 'getVideoUrl', test: () => player.getVideoUrl() },
                { method: 'getPlaybackRate', test: () => player.getPlaybackRate() }
            ];
            
            tests.forEach(({ method, test }) => {
                try {
                    if (typeof player[method] === 'function') {
                        const result = test();
                        log(`✅ ${method}() : ${result}`, 'success');
                    } else {
                        log(`❌ ${method}() : Non disponible`, 'error');
                    }
                } catch (e) {
                    log(`❌ ${method}() : Erreur - ${e.message}`, 'error');
                }
            });
        }
        
        function runAllTests() {
            log('=== EXÉCUTION DE TOUS LES TESTS ===');
            
            // Test des titres
            testSingleTitle();
            
            // Test du player après un délai
            setTimeout(() => {
                if (playerReady) {
                    testPlayerMethods();
                    captureTime();
                } else {
                    log('Player non prêt, chargement...', 'error');
                    loadVideo();
                }
            }, 2000);
        }
        
        // Mise à jour périodique du temps
        setInterval(() => {
            if (playerReady && player && player.getCurrentTime) {
                try {
                    const time = player.getCurrentTime();
                    const totalSeconds = Math.floor(time);
                    const hours = Math.floor(totalSeconds / 3600);
                    const minutes = Math.floor((totalSeconds % 3600) / 60);
                    const seconds = totalSeconds % 60;
                    
                    const timeStr = hours.toString().padStart(2, '0') + ':' +
                                   minutes.toString().padStart(2, '0') + ':' +
                                   seconds.toString().padStart(2, '0');
                    
                    document.getElementById('time-display').textContent = timeStr;
                } catch (e) {
                    // Silencieux
                }
            }
        }, 1000);
        
        // Charger l'API YouTube au démarrage
        log('Chargement de l\'API YouTube...');
        const tag = document.createElement('script');
        tag.src = "https://www.youtube.com/iframe_api";
        const firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    </script>
</body>
</html>
<?php exit; endif; ?>

<?php
// Mode dashboard (intégré)
echo "=== Tests YouTube API ===\n";
echo "Tests disponibles :\n";
echo "- Récupération des titres (3 méthodes)\n";
echo "- Test du player YouTube\n";
echo "- Capture du temps actuel\n";
echo "- Navigation dans la vidéo\n\n";

echo "Configuration actuelle :\n";
echo "- PHP " . phpversion() . "\n";
echo "- allow_url_fopen : " . (ini_get('allow_url_fopen') ? 'Activé' : 'Désactivé') . "\n";
echo "- cURL : " . (function_exists('curl_init') ? 'Disponible' : 'Non disponible') . "\n";
?>