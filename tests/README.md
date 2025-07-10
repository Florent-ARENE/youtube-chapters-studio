# 🧪 Suite de tests - YouTube Chapters Studio

Ce dossier contient tous les tests de l'application YouTube Chapters Studio.

## 📋 Tests disponibles

### 1. Dashboard des tests (`index.php`)
Point d'entrée principal pour tous les tests. Interface web intuitive qui permet :
- Vue d'ensemble de tous les tests
- Exécution individuelle ou groupée
- Résultats en temps réel
- Accès uniquement en local (127.0.0.1)

**Accès :** http://localhost/youtube-chapters-studio/tests/

### 2. Test des chemins et permissions (`test-paths.php`)
- ✅ Vérification des fichiers requis
- ✅ Contrôle des dossiers et permissions
- ✅ Test des extensions PHP
- ✅ Configuration PHP
- ✅ Tests de sécurité
- 🔧 Correction automatique des problèmes

### 3. Test AJAX et sauvegarde (`test-ajax.php`)
- ✅ Connexion AJAX
- ✅ Token CSRF
- ✅ Sauvegarde simple et complexe
- ✅ Auto-save
- ✅ Gestion d'erreurs
- ✅ Tests de session

### 4. Test API YouTube (`test-youtube.php`)
- ✅ Récupération des titres (3 méthodes)
- ✅ Player YouTube
- ✅ Capture du temps
- ✅ Navigation dans la vidéo
- ✅ Test des méthodes du player

### 5. Test JavaScript (`test-javascript.php`)
- ✅ Variables globales
- ✅ Fonctions disponibles
- ✅ appConfig
- ✅ Chargement de vidéo
- ✅ Flux complet d'utilisation
- ✅ Mise à jour dynamique

## 🚀 Utilisation

### Méthode 1 : Dashboard (recommandé)
1. Accédez à `/tests/` dans votre navigateur
2. Cliquez sur "▶️ Exécuter tous les tests" ou testez individuellement
3. Consultez les résultats en temps réel

### Méthode 2 : Tests individuels
Chaque test peut être exécuté en mode standalone :
```
http://localhost/youtube-chapters-studio/tests/test-ajax.php?mode=standalone
```

## 🔒 Sécurité

- Accès limité aux IP locales (127.0.0.1, ::1)
- Fichier `.htaccess` configuré pour bloquer l'accès distant
- Pas de données sensibles dans les tests

## 🛠️ Helper

### `get-csrf-token.php`
Utilitaire pour récupérer le token CSRF de la session. Utilisé par les tests JavaScript.

## 📊 Interprétation des résultats

- ✅ **Vert** : Test réussi
- ⚠️ **Orange** : Avertissement (non bloquant)
- ❌ **Rouge** : Erreur (à corriger)

## 🔄 Maintenance

Pour ajouter un nouveau test :
1. Créez `test-nouveau.php` dans ce dossier
2. Ajoutez-le dans `index.php` dans le tableau `$tests`
3. Implémentez les modes `dashboard` et `standalone`
4. Documentez-le ici

## 💡 Conseils

- Exécutez tous les tests après une mise à jour
- Vérifiez les permissions si des tests échouent
- Consultez la console pour les détails techniques
- Utilisez le mode standalone pour déboguer