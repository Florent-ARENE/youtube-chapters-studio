<?php
/**
 * Script pour mettre à jour les titres des vidéos YouTube dans les projets existants
 * À exécuter une seule fois pour récupérer les titres manquants
 */

// Configuration
$dataDir = __DIR__ . '/chapters_data';

// Fonction pour récupérer le titre YouTube
function getYouTubeTitle($videoId) {
    echo "Récupération du titre pour : $videoId ... ";
    
    // Méthode avec noembed.com (alternative à oEmbed)
    $url = "https://noembed.com/embed?url=https://www.youtube.com/watch?v=" . $videoId;
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 15,
            'user_agent' => 'Mozilla/5.0'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['title'])) {
            echo "✓ Trouvé : " . $data['title'] . "\n";
            return $data['title'];
        }
    }
    
    // Méthode alternative avec YouTube oEmbed
    $url = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=" . $videoId . "&format=json";
    $response = @file_get_contents($url, false, $context);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['title'])) {
            echo "✓ Trouvé : " . $data['title'] . "\n";
            return $data['title'];
        }
    }
    
    echo "✗ Échec\n";
    return null;
}

// Parcourir tous les fichiers JSON
if (is_dir($dataDir)) {
    $files = glob($dataDir . '/*.json');
    $updated = 0;
    $failed = 0;
    
    echo "=== Mise à jour des titres YouTube ===\n\n";
    echo "Fichiers trouvés : " . count($files) . "\n\n";
    
    foreach ($files as $file) {
        $data = json_decode(file_get_contents($file), true);
        
        if ($data && isset($data['video_id'])) {
            $projectId = basename($file, '.json');
            echo "Projet $projectId : ";
            
            // Si le titre n'existe pas ou est vide
            if (empty($data['video_title']) || $data['video_title'] === 'Vidéo sans titre') {
                $title = getYouTubeTitle($data['video_id']);
                
                if ($title) {
                    $data['video_title'] = $title;
                    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
                    $updated++;
                } else {
                    $failed++;
                }
                
                // Pause pour éviter de surcharger l'API
                sleep(1);
            } else {
                echo "Titre déjà présent : " . $data['video_title'] . "\n";
            }
        }
    }
    
    echo "\n=== Résumé ===\n";
    echo "Titres mis à jour : $updated\n";
    echo "Échecs : $failed\n";
    echo "Terminé !\n";
} else {
    echo "Erreur : Le dossier $dataDir n'existe pas.\n";
}
?>