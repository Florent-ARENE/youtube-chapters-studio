<?php
/**
 * Script de test pour vérifier la récupération des titres YouTube
 */

// Fonction pour récupérer le titre YouTube
function getYouTubeTitle($videoId) {
    echo "\n=== Test pour la vidéo : $videoId ===\n";
    
    // Vérification de l'ID
    if (empty($videoId)) {
        echo "✗ ID vide\n";
        return;
    }
    
    // Test 1 : noembed.com
    echo "Test 1 - noembed.com : ";
    $url = "https://noembed.com/embed?url=https://www.youtube.com/watch?v=" . $videoId;
    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['title'])) {
            echo "✓ " . $data['title'] . "\n";
        } else {
            echo "✗ Pas de titre dans la réponse\n";
            echo "Réponse : " . substr($response, 0, 200) . "...\n";
        }
    } else {
        echo "✗ Erreur de connexion\n";
    }
    
    // Test 2 : YouTube oEmbed
    echo "Test 2 - YouTube oEmbed : ";
    $url = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=" . $videoId . "&format=json";
    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['title'])) {
            echo "✓ " . $data['title'] . "\n";
        } else {
            echo "✗ Pas de titre dans la réponse\n";
        }
    } else {
        echo "✗ Erreur de connexion\n";
    }
    
    // Test 3 : avec cURL
    if (function_exists('curl_init')) {
        echo "Test 3 - cURL : ";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=" . $videoId . "&format=json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['title'])) {
                echo "✓ " . $data['title'] . "\n";
            } else {
                echo "✗ Pas de titre dans la réponse\n";
            }
        } else {
            echo "✗ Code HTTP : $httpCode\n";
        }
    } else {
        echo "Test 3 - cURL : ✗ cURL non disponible\n";
    }
}

// Tests avec les vidéos de votre capture d'écran
$testVideos = [
    'AOsk77MJy1o' => 'Vidéo YouTube 1',
    'lgtyB9eUAM' => 'Vidéo YouTube 2',
    'dQw4w9WgXcQ' => 'Rick Astley - Never Gonna Give You Up (test)'
];

echo "=== Test de récupération des titres YouTube ===\n";
echo "PHP Version : " . phpversion() . "\n";
echo "allow_url_fopen : " . (ini_get('allow_url_fopen') ? 'Activé' : 'Désactivé') . "\n";
echo "cURL : " . (function_exists('curl_init') ? 'Disponible' : 'Non disponible') . "\n";

foreach ($testVideos as $videoId => $expectedTitle) {
    getYouTubeTitle($videoId);
    sleep(1); // Pause pour éviter de surcharger l'API
}

echo "\n=== Fin des tests ===\n";
?>