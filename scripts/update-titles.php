<?php
/**
 * Script de maintenance pour mettre à jour les titres des vidéos YouTube
 * À exécuter une seule fois pour récupérer les titres manquants
 * 
 * Usage : php scripts/update-titles.php
 */

// Remonter d'un niveau pour accéder aux fichiers de config
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// Vérifier qu'on est en ligne de commande
if (php_sapi_name() !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande\n");
}

echo "=== Mise à jour des titres YouTube ===\n\n";

// Parcourir tous les fichiers JSON
if (!is_dir(DATA_DIR)) {
    die("Erreur : Le dossier " . DATA_DIR . " n'existe pas.\n");
}

$files = glob(DATA_DIR . '/*.json');
$updated = 0;
$failed = 0;
$skipped = 0;

echo "Fichiers trouvés : " . count($files) . "\n\n";

foreach ($files as $file) {
    $data = json_decode(file_get_contents($file), true);
    
    if ($data && isset($data['video_id'])) {
        $projectId = basename($file, '.json');
        echo "Projet $projectId : ";
        
        // Si le titre n'existe pas ou est vide
        if (empty($data['video_title']) || $data['video_title'] === 'Vidéo sans titre') {
            $title = getYouTubeTitle($data['video_id']);
            
            if ($title && $title !== 'Vidéo YouTube') {
                $data['video_title'] = $title;
                file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
                echo "✅ Titre mis à jour : $title\n";
                $updated++;
            } else {
                echo "❌ Échec de la récupération\n";
                $failed++;
            }
            
            // Pause pour éviter de surcharger l'API
            sleep(1);
        } else {
            echo "⏭️ Titre déjà présent : " . $data['video_title'] . "\n";
            $skipped++;
        }
    }
}

echo "\n=== Résumé ===\n";
echo "Titres mis à jour : $updated\n";
echo "Déjà à jour : $skipped\n";
echo "Échecs : $failed\n";
echo "Total traité : " . count($files) . "\n";
echo "\nTerminé !\n";
?>