# 🧪 Suite de tests - YouTube Chapters Studio

Ce dossier contient tous les tests de l'application YouTube Chapters Studio.

## 🔒 Authentification

Depuis la version 2.0.0, les tests sont protégés par authentification pour permettre l'accès distant sécurisé.

### Accès aux tests

1. **Accès local automatique** : Si vous accédez depuis `localhost` (127.0.0.1), l'accès est automatique
2. **Accès distant** : Nécessite un mot de passe

### Configuration du mot de passe

1. Ouvrez `test-auth.php`
2. Modifiez la constante `TEST_PASSWORD` :
   ```php
   define('TEST_PASSWORD', 'VotreMotDePasseSecurise2025!');
   ```
3. Utilisez un mot de passe fort et unique

### Fonctionnalités de sécurité

- ✅ Session avec timeout (1 heure par défaut)
- ✅ Protection contre le brute force (via les logs serveur)
- ✅ Déconnexion automatique après inactivité
- ✅ Accès local toujours autorisé
- ✅ HTTPS recommandé pour l'accès distant

## 📋 Tests disponibles

### 1. Dashboard des tests (`index.php`)
Point d'entrée principal pour tous les tests. Interface web intuitive qui permet :
- Vue d'ensemble de tous les tests
- Exécution individuelle ou groupée
- Résultats en temps réel
- **Authentification requise pour l'accès distant**

**Accès :** 
- Local : http://localhost/youtube-chapters-studio/tests/
- Distant : https://votre-domaine.com/youtube-chapters-studio/tests/

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

## 🚀 Installation

### 1. Copier les fichiers

1. Créez `test-auth.php` dans le dossier `tests/`
2. Remplacez `index.php` par la version avec authentification
3. Remplacez `.htaccess` par la nouvelle version
4. **IMPORTANT** : Changez le mot de passe par défaut

### 2. Modifier les tests individuels (optionnel)

Pour protéger les tests individuels en mode standalone :

```php
// Au début du fichier de test
$testMode = $_GET['mode'] ?? 'dashboard';

if ($testMode === 'standalone') {
    require_once 'test-auth.php';
    requireTestAuth();
}
```

### 3. Configuration serveur

Pour Apache, le nouveau `.htaccess` autorise tous les accès (l'authentification est gérée par PHP).

Pour Nginx :
```nginx
location ~ /tests/ {
    # Autoriser tous les accès (PHP gère l'authentification)
    try_files $uri $uri/ /tests/index.php?$query_string;
}
```

## 🔐 Sécurité renforcée

### Bonnes pratiques

1. **Mot de passe fort** : Utilisez au moins 12 caractères avec majuscules, minuscules, chiffres et symboles
2. **HTTPS obligatoire** : Pour l'accès distant, utilisez toujours HTTPS
3. **Changez régulièrement** : Modifiez le mot de passe périodiquement
4. **Logs d'accès** : Surveillez les logs serveur pour détecter les tentatives d'intrusion

### Options avancées

Dans `test-auth.php`, vous pouvez modifier :

```php
// Durée de session (en secondes)
define('TEST_SESSION_TIMEOUT', 3600); // 1 heure

// Ajouter une liste blanche d'IPs
$whitelistedIPs = ['192.168.1.100', '10.0.0.50'];
if (in_array($_SERVER['REMOTE_ADDR'], $whitelistedIPs)) {
    return true; // Accès automatique
}
```

## 📊 Utilisation

### Première connexion

1. Accédez à `/tests/`
2. Si vous êtes en distant, entrez le mot de passe
3. La session reste active pendant 1 heure
4. L'indicateur en haut à droite montre le statut

### États possibles

- 🟢 **Accès local automatique** : Vous êtes sur localhost
- 🔓 **Authentifié** : Session active avec temps restant
- 🔒 **Non authentifié** : Connexion requise

### Déconnexion

- Cliquez sur "Déconnexion" en haut à droite
- Ou attendez l'expiration de la session

## 🛠️ Dépannage

### "Accès refusé"
- Vérifiez que vous avez bien créé `test-auth.php`
- Assurez-vous d'avoir modifié le `.htaccess`
- Vérifiez les permissions du dossier

### "Mot de passe incorrect"
- Vérifiez la constante `TEST_PASSWORD` dans `test-auth.php`
- Attention aux espaces avant/après le mot de passe

### Session qui expire trop vite
- Augmentez `TEST_SESSION_TIMEOUT`
- Vérifiez la configuration PHP de `session.gc_maxlifetime`

## 💡 Personnalisation

### Ajouter un captcha

Pour plus de sécurité, vous pouvez ajouter un captcha :

```php
// Dans showLoginForm()
<div class="form-group">
    <label>Vérification</label>
    <img src="captcha.php" alt="Captcha">
    <input type="text" name="captcha" required>
</div>
```

### Logger les tentatives

```php
// Dans authenticateTest()
function logAttempt($success) {
    $log = date('Y-m-d H:i:s') . ' - ' . $_SERVER['REMOTE_ADDR'] . 
           ' - ' . ($success ? 'SUCCESS' : 'FAILED') . PHP_EOL;
    file_put_contents('test-access.log', $log, FILE_APPEND);
}
```

### Notification par email

```php
// Après authentification réussie
if ($success && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    mail('admin@example.com', 'Accès aux tests', 
         'IP: ' . $_SERVER['REMOTE_ADDR'] . ' à ' . date('Y-m-d H:i:s'));
}
```

## 🔄 Migration depuis l'ancienne version

Si vous aviez l'ancienne version (accès local uniquement) :

1. **Sauvegardez** votre dossier `tests/` actuel
2. **Remplacez** les fichiers comme indiqué ci-dessus
3. **Testez** d'abord en local
4. **Configurez** le mot de passe
5. **Déployez** sur le serveur

L'accès local continuera de fonctionner exactement comme avant, sans mot de passe.