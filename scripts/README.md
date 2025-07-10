# 📜 Scripts de maintenance - YouTube Chapters Studio

Ce dossier contient les scripts de maintenance et d'administration de l'application.

## 📋 Scripts disponibles

### 1. `update-titles.php`
Met à jour les titres manquants dans les projets existants en interrogeant l'API YouTube.

**Usage :**
```bash
php scripts/update-titles.php
```

**Fonctionnalités :**
- Parcourt tous les fichiers JSON dans `chapters_data/`
- Récupère les titres manquants via l'API YouTube
- Met à jour uniquement les projets sans titre ou avec "Vidéo sans titre"
- Pause d'1 seconde entre chaque requête (respect de l'API)
- Affiche un résumé des opérations

**Quand l'utiliser :**
- Après une migration de données
- Si des projets ont été créés sans titre
- Pour corriger des titres manquants en masse

## 🔧 Ajouter un nouveau script

Pour ajouter un script de maintenance :

1. Créez le fichier dans ce dossier
2. Incluez les fichiers de configuration :
   ```php
   require_once __DIR__ . '/../config.php';
   require_once __DIR__ . '/../functions.php';
   ```
3. Vérifiez l'exécution en CLI :
   ```php
   if (php_sapi_name() !== 'cli') {
       die("Ce script doit être exécuté en ligne de commande\n");
   }
   ```
4. Documentez-le dans ce README

## 🔒 Sécurité

- Ces scripts doivent être exécutés uniquement en ligne de commande
- Ne pas exposer ce dossier via le serveur web
- Ajouter un `.htaccess` si nécessaire :
  ```apache
  Order Deny,Allow
  Deny from all
  ```

## 💡 Scripts suggérés pour le futur

- `cleanup-old-projects.php` : Nettoyer les projets anciens
- `export-statistics.php` : Exporter des statistiques d'utilisation
- `backup-projects.php` : Sauvegarder tous les projets
- `import-elus.php` : Importer/mettre à jour la base des élus
- `check-integrity.php` : Vérifier l'intégrité des données