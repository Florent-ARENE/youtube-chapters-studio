# 🔒 Sécurité - YouTube Chapters Studio v2.0.0

Ce document décrit les mesures de sécurité implémentées dans l'application.

## 📋 Vue d'ensemble

L'application a été sécurisée contre les principales vulnérabilités web :
- ✅ Injections XSS (Cross-Site Scripting)
- ✅ Injections SQL (pas de base de données, mais validation des fichiers)
- ✅ CSRF (Cross-Site Request Forgery)
- ✅ Path Traversal / Directory Traversal
- ✅ File Upload (pas d'upload, mais validation des données)
- ✅ Session Hijacking
- ✅ Clickjacking
- ✅ Accès non autorisé aux zones sensibles
- ✅ **Authentification sécurisée pour les tests** (v2.0.0)

## 🛡️ Mesures de sécurité implémentées

### 1. **Protection XSS**
- Fonction `sanitize()` appliquée à toutes les entrées utilisateur
- `htmlspecialchars()` avec ENT_QUOTES et UTF-8
- Validation côté client ET serveur
- Headers de sécurité X-XSS-Protection

### 2. **Protection CSRF**
- Token CSRF unique par session
- Validation sur toutes les requêtes POST
- Token régénéré après connexion
- Validation stricte dans `ajax-handler.php`

### 3. **Validation des entrées**
- IDs YouTube : regex stricte `/^[a-zA-Z0-9_-]{11}$/`
- IDs de projet : regex stricte `/^[a-f0-9]{8}$/`
- Titres : longueur maximale et caractères interdits
- Types de chapitres : liste blanche stricte (`chapitre`, `elu`, `vote`)

### 4. **Protection Path Traversal**
- Utilisation de `basename()` pour les noms de fichiers
- Vérification avec `isSecurePath()` du chemin complet
- Validation que le fichier est dans le bon dossier
- Protection contre les attaques `../`

### 5. **Sécurité des sessions**
- `session.cookie_httponly = 1`
- `session.use_only_cookies = 1`
- Token CSRF stocké en session
- Régénération d'ID de session sur actions sensibles
- **Sessions avec timeout pour l'authentification des tests** (v2.0.0)

### 6. **Headers de sécurité**
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`

### 7. **Protection des fichiers et dossiers**

#### Racine
- Fichiers PHP sécurisés avec validation des entrées
- `viewer.php` généré automatiquement si absent

#### `/chapters_data/`
```apache
Order Deny,Allow
Deny from all
Options -Indexes
```
- Accès direct interdit
- Fichiers JSON non exécutables
- Lecture uniquement via PHP

#### `/setup/`
```apache
# À décommenter après installation
# Order Deny,Allow
# Deny from all
```
- Protection à activer après installation
- Empêche la réinstallation accidentelle

#### `/tests/` (Mise à jour v2.0.0)
```apache
# Autoriser tous les accès (l'authentification sera gérée par PHP)
Order Allow,Deny
Allow from all

# Empêcher l'indexation
Options -Indexes

# Headers de sécurité
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
```
- **Authentification par mot de passe** pour l'accès distant
- **Accès local automatique** depuis 127.0.0.1
- Sessions sécurisées avec timeout
- Dashboard de tests protégé

#### `/scripts/`
- Scripts CLI uniquement
- Vérification `php_sapi_name() !== 'cli'`
- Protection totale par .htaccess

#### `/elus/`
- Fichiers CSV en lecture seule
- Conversion d'encodage sécurisée
- Protection contre le téléchargement direct

### 8. **Authentification des tests (v2.0.0)**
- **Mot de passe configurable** dans `test-auth.php`
- **Sessions temporaires** avec timeout (1 heure par défaut)
- **Accès local sans mot de passe** pour le développement
- **Protection contre le brute force** via les logs serveur
- **HTTPS recommandé** pour l'accès distant

### 9. **Limites et quotas**
- Maximum 500 chapitres par projet
- Maximum 200 caractères par titre
- Maximum 50 projets par session
- Timeout des requêtes AJAX
- Limite de taille des fichiers JSON
- **Timeout de session pour les tests** : 1 heure

### 10. **Validation AJAX**
- Vérification du header `X-Requested-With`
- Validation du token CSRF obligatoire
- Réponses JSON uniquement
- Gestion d'erreurs centralisée

### 11. **Encodage sécurisé**
- UTF-8 partout
- Conversion sécurisée depuis Windows-1252 pour les CSV
- Protection contre les injections d'encodage
- Sanitisation des caractères spéciaux

## 📁 Structure sécurisée

```
youtube-chapters-studio/
├── index.php              # Interface avec CSRF
├── viewer.php             # Visualisation sécurisée
├── ajax-handler.php       # AJAX avec validation CSRF
├── config.php             # Configuration sécurisée
├── functions.php          # Fonctions métier validées
├── .htaccess              # Protection racine (optionnel)
│
├── setup/
│   ├── index.php          # Installation guidée
│   └── .htaccess          # À activer après installation
│
├── tests/
│   ├── index.php          # Dashboard avec authentification
│   ├── test-auth.php      # Système d'authentification
│   └── .htaccess          # Autoriser tous (auth par PHP)
│
├── scripts/
│   ├── *.php              # Scripts CLI uniquement
│   └── .htaccess          # Deny from all
│
├── chapters_data/
│   └── .htaccess          # Deny from all
│
└── elus/
    ├── elus.csv           # Données en lecture seule
    └── .htaccess          # Protection CSV
```

## 🔧 Configuration serveur recommandée

### PHP
```ini
display_errors = Off
error_reporting = E_ALL
log_errors = On
session.cookie_httponly = 1
session.cookie_secure = 1  ; Si HTTPS
session.use_strict_mode = 1
upload_max_filesize = 2M
post_max_size = 3M
max_execution_time = 30
```

### Apache
```apache
# .htaccess racine recommandé
Options -Indexes
ServerSignature Off

# Headers de sécurité
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Protection des fichiers sensibles
<FilesMatch "\.(json|csv|log|md)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
```

### Nginx (alternative)
```nginx
# Désactiver l'indexation
autoindex off;

# Headers de sécurité
add_header X-Content-Type-Options "nosniff";
add_header X-Frame-Options "SAMEORIGIN";
add_header X-XSS-Protection "1; mode=block";
add_header Referrer-Policy "strict-origin-when-cross-origin";

# Protection des dossiers
location ~ /(chapters_data|setup|scripts)/ {
    deny all;
    return 403;
}

# Tests avec authentification PHP
location /tests/ {
    # PHP gère l'authentification
    try_files $uri $uri/ /tests/index.php?$query_string;
}
```

## 🔐 Configuration de l'authentification des tests

### Configuration initiale
1. **Modifier le mot de passe** dans `tests/test-auth.php` :
   ```php
   define('TEST_PASSWORD', 'VotreMotDePasseSecurise2025!');
   ```

2. **Ou utiliser le script de configuration** :
   ```bash
   cd tests/
   php setup-auth.php
   ```

### Options avancées
```php
// Durée de session (en secondes)
define('TEST_SESSION_TIMEOUT', 3600); // 1 heure

// Liste blanche d'IPs (optionnel)
$whitelistedIPs = ['192.168.1.100', '10.0.0.50'];
```

### Logs d'accès (optionnel)
```php
// Dans test-auth.php, ajouter :
function logAttempt($success) {
    $log = date('Y-m-d H:i:s') . ' - ' . $_SERVER['REMOTE_ADDR'] . 
           ' - ' . ($success ? 'SUCCESS' : 'FAILED') . PHP_EOL;
    file_put_contents('test-access.log', $log, FILE_APPEND);
}
```

## 🚨 En cas de problème

### 1. **Erreur de token CSRF**
- Recharger la page (F5)
- Vider le cache du navigateur
- Vérifier les cookies de session
- S'assurer que les sessions PHP fonctionnent

### 2. **Fichiers non accessibles**
- Vérifier les permissions (750 pour dossiers, 640 pour fichiers)
- S'assurer que PHP peut écrire dans `chapters_data/`
- Vérifier les règles .htaccess

### 3. **Validation échouée**
- Format des données incorrect
- Pas de caractères `<` ou `>` dans les titres
- IDs YouTube de 11 caractères exactement
- IDs de projet de 8 caractères hexadécimaux

### 4. **Accès refusé aux tests**
- Vérifier le mot de passe dans `test-auth.php`
- S'assurer que le fichier existe
- Vérifier l'IP pour l'accès local
- Utiliser HTTPS pour l'accès distant

### 5. **Session expirée**
- Se reconnecter avec le mot de passe
- Augmenter `TEST_SESSION_TIMEOUT` si nécessaire
- Vérifier `session.gc_maxlifetime` dans PHP

## 📊 Limites de sécurité actuelles

- **Pas d'authentification principale** : Tout le monde peut créer/modifier des projets
- **Pas de chiffrement des données** : Utiliser HTTPS obligatoirement
- **Pas de backup automatique** : Sauvegarder manuellement `chapters_data/`
- **Pas de rate limiting natif** : À implémenter au niveau serveur
- **Journalisation limitée** : Ajouter des logs pour l'audit

## 🔐 Bonnes pratiques

### En développement
1. Utiliser la suite de tests `/tests/` (accès local automatique)
2. Vérifier régulièrement avec `/setup/check-installation.php`
3. Activer les logs d'erreur PHP
4. Tester avec différents navigateurs

### En production
1. **HTTPS obligatoire** avec certificat SSL valide
2. **Sécuriser `/setup/`** après installation
3. **Configurer l'authentification des tests** :
   - Changer le mot de passe par défaut
   - Activer les logs d'accès
   - Surveiller les tentatives de connexion
4. **Créer `.htaccess` pour `/scripts/`** :
   ```apache
   Order Deny,Allow
   Deny from all
   ```
5. **Sauvegardes régulières** de `chapters_data/`
6. **Surveiller les logs** serveur pour détecter les anomalies
7. **Mettre à jour PHP** et les dépendances
8. **Permissions minimales** : 
   - Dossiers : 750
   - Fichiers : 640
   - `chapters_data/` : 770 (écriture nécessaire)

### Hardening supplémentaire
```bash
# Permissions recommandées
find . -type f -name "*.php" -exec chmod 640 {} \;
find . -type d -exec chmod 750 {} \;
chmod 770 chapters_data/
chmod 640 elus/elus.csv

# Propriétaire correct
chown -R www-data:www-data .
```

## 🆘 Signaler une vulnérabilité

Si vous découvrez une vulnérabilité :

1. **NE PAS** la publier publiquement
2. Envoyer un rapport détaillé à l'équipe de développement
3. Inclure :
   - Description de la vulnérabilité
   - Étapes de reproduction
   - Impact potentiel
   - Suggestion de correction (si possible)
4. Attendre le correctif avant toute divulgation

### Processus de correction
1. Accusé de réception sous 48h
2. Analyse et développement du correctif
3. Test de la solution
4. Publication de la mise à jour
5. Crédit au découvreur (si souhaité)

## 📚 Ressources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [CSP (Content Security Policy)](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP)
- [Secure Headers](https://securityheaders.com/)
- [Session Security](https://www.php.net/manual/en/session.security.php)

---

*Dernière mise à jour : Juillet 2025 - Version 2.0.0*
*Document de sécurité maintenu avec l'évolution de l'application*