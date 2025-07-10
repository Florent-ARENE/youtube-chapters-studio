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

#### `/tests/`
```apache
Order Deny,Allow
Deny from all
Allow from 127.0.0.1
Allow from ::1
Allow from localhost
```
- Accès local uniquement
- Dashboard de tests sécurisé

#### `/scripts/`
- Scripts CLI uniquement
- Vérification `php_sapi_name() !== 'cli'`
- À protéger par .htaccess en production

#### `/elus/`
- Fichiers CSV en lecture seule
- Conversion d'encodage sécurisée

### 8. **Limites et quotas**
- Maximum 500 chapitres par projet
- Maximum 200 caractères par titre
- Maximum 50 projets par session
- Timeout des requêtes AJAX
- Limite de taille des fichiers JSON

### 9. **Validation AJAX**
- Vérification du header `X-Requested-With`
- Validation du token CSRF obligatoire
- Réponses JSON uniquement
- Gestion d'erreurs centralisée

### 10. **Encodage sécurisé**
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
│   ├── index.php          # Dashboard local uniquement
│   └── .htaccess          # Accès 127.0.0.1 uniquement
│
├── scripts/
│   ├── *.php              # Scripts CLI uniquement
│   └── .htaccess          # Deny from all (à créer)
│
├── chapters_data/
│   └── .htaccess          # Deny from all
│
└── elus/
    └── elus.csv           # Données en lecture seule
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
location ~ /(chapters_data|setup|tests|scripts)/ {
    deny all;
    return 403;
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
- Vérifier l'accès depuis localhost (127.0.0.1)
- Désactiver temporairement le .htaccess si nécessaire
- Utiliser `?mode=standalone` pour les tests individuels

## 📊 Limites de sécurité actuelles

- **Pas d'authentification** : Tout le monde peut créer/modifier des projets
- **Pas de chiffrement des données** : Utiliser HTTPS obligatoirement
- **Pas de backup automatique** : Sauvegarder manuellement `chapters_data/`
- **Pas de rate limiting natif** : À implémenter au niveau serveur
- **Pas de journalisation** : Ajouter des logs pour l'audit

## 🔐 Bonnes pratiques

### En développement
1. Utiliser la suite de tests `/tests/`
2. Vérifier régulièrement avec `/setup/check-installation.php`
3. Activer les logs d'erreur PHP

### En production
1. **HTTPS obligatoire** avec certificat SSL valide
2. **Sécuriser `/setup/`** après installation
3. **Créer `.htaccess` pour `/scripts/`** :
   ```apache
   Order Deny,Allow
   Deny from all
   ```
4. **Sauvegardes régulières** de `chapters_data/`
5. **Surveiller les logs** serveur pour détecter les anomalies
6. **Mettre à jour PHP** et les dépendances
7. **Permissions minimales** : 
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

---

*Dernière mise à jour : Juillet 2025 - Version 2.0.0*
*Document de sécurité maintenu avec l'évolution de l'application*