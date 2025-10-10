# 🚀 Installation - YouTube Chapters Studio

Ce dossier contient le système d'installation guidée de YouTube Chapters Studio.

## 📋 Vue d'ensemble

L'installation se fait en 6 étapes automatisées qui vérifient et configurent tout ce qui est nécessaire au bon fonctionnement de l'application.

## 🔧 Fichiers du dossier

### 1. `index.php`
Interface d'installation principale avec processus guidé en 6 étapes :
- **Étape 1 : Vérification des prérequis**
- **Étape 2 : Création des dossiers**
- **Étape 3 : Configuration des permissions**
- **Étape 4 : Sécurisation (création automatique des .htaccess)**
- **Étape 5 : Test de fonctionnement**
- **Étape 6 : Installation terminée**

### 2. `check-installation.php`
Script de vérification détaillée qui teste :
- Version PHP (>= 7.0)
- Extensions requises et optionnelles
- Configuration PHP
- Permissions système
- État de l'installation

### 3. `.htaccess`
Fichier de protection qui doit être activé après l'installation pour bloquer l'accès au dossier setup.

## 📊 Tests effectués durant l'installation

### Prérequis système (Étape 1)
- ✅ **Version PHP** : Vérifie PHP >= 7.0
- ✅ **Extensions requises** :
  - `mbstring` : Gestion des caractères multi-octets (UTF-8)
  - `json` : Support JSON pour les sauvegardes
  - `session` : Gestion des sessions PHP
- ⚠️ **Extensions optionnelles** :
  - `curl` : Pour récupérer les titres YouTube (fallback disponible)
- ✅ **Configuration PHP** :
  - `allow_url_fopen` : Requis pour les APIs YouTube
- ✅ **Permissions** : Vérification des droits d'écriture

### Création des dossiers (Étape 2)
L'installation crée automatiquement :
```
youtube-chapters-studio/
├── chapters_data/     # Stockage des projets JSON (chmod 777)
├── elus/             # Base de données des élus (chmod 755)
├── tests/            # Suite de tests (chmod 755)
└── scripts/          # Scripts de maintenance (chmod 755)
```

### Configuration des permissions (Étape 3)
- `chapters_data/` : 777 (lecture/écriture complète)
- `elus/` : 755 (lecture seule)
- `tests/` : 755 (lecture/exécution)
- `scripts/` : 755 (lecture/exécution)

### Sécurisation automatique (Étape 4) 🔒

L'installation crée automatiquement tous les fichiers `.htaccess` nécessaires :

#### `chapters_data/.htaccess`
```apache
# Interdire tout accès aux fichiers JSON
Order Deny,Allow
Deny from all

# Désactiver l'indexation du répertoire
Options -Indexes

# Interdire l'exécution de scripts
<FilesMatch "\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$">
    Deny from all
</FilesMatch>

# Interdire l'accès aux fichiers sensibles
<FilesMatch "\.(json|log|sql|db)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
```

#### `tests/.htaccess` (v2.0.0 - Authentification PHP)
```apache
# Configuration pour permettre l'accès distant avec authentification
# L'authentification est gérée par PHP (test-auth.php)

# Autoriser tous les accès (l'authentification sera gérée par PHP)
Order Allow,Deny
Allow from all

# Empêcher l'indexation du répertoire
Options -Indexes

# Protection contre l'exécution de fichiers non autorisés
<FilesMatch "\.(sh|sql|db|env|log)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# Headers de sécurité
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

**Note importante v2.0.0** : Le système crée aussi `test-auth.php` avec un mot de passe temporaire. **Changez-le immédiatement !**

#### `scripts/.htaccess`
```apache
# Interdire tout accès web aux scripts
Order Deny,Allow
Deny from all

# Ces scripts doivent être exécutés uniquement en CLI
Options -Indexes
```

#### `elus/.htaccess`
```apache
# Protéger les fichiers CSV
<FilesMatch "\.(csv|txt)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Empêcher l'indexation
Options -Indexes

# Interdire l'exécution de scripts
<FilesMatch "\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
```

#### `setup/.htaccess`
```apache
# Zone d'installation - À sécuriser après installation
# Décommentez les lignes suivantes après l'installation :
# Order Deny,Allow
# Deny from all

# Empêcher l'indexation
Options -Indexes
```

### Tests de fonctionnement (Étape 5)
- **Test d'écriture** : Création/suppression d'un fichier test dans `chapters_data/`
- **Test de session** : Vérification du bon fonctionnement des sessions PHP
- **Test CSRF** : Génération de tokens de sécurité
- **Création du fichier `.installed`** : Marque l'installation comme terminée

## 🎯 Processus d'installation

### 1. Première installation
1. Accédez à `/setup/` dans votre navigateur
2. Suivez les 6 étapes guidées
3. L'installation crée automatiquement :
   - Les dossiers nécessaires avec les bonnes permissions
   - **Tous les fichiers .htaccess de sécurité**
   - **Le fichier test-auth.php avec mot de passe temporaire** (v2.0.0)
   - Le fichier `.installed` qui empêche les réinstallations accidentelles

### 2. Configuration post-installation (v2.0.0)

**IMPORTANT** : Après l'installation, configurez immédiatement les tests :

1. **Changez le mot de passe des tests** :
   - Éditez `tests/test-auth.php`
   - Remplacez le mot de passe temporaire
   
2. **Ou utilisez le script de configuration** :
   ```bash
   cd tests/
   php setup-auth.php
   ```

### 3. Réinstallation
Si vous devez réinstaller :
1. **Option automatique** : Utilisez le bouton "Réinitialiser l'installation"
2. **Option manuelle** : Supprimez le fichier `.installed` à la racine

### 4. Actions de réparation
Le système peut corriger automatiquement :
- Dossiers manquants
- Permissions incorrectes
- **Fichiers .htaccess absents ou incorrects**

Si un fichier .htaccess existe déjà, le système :
- Vérifie si le contenu est différent
- Crée une sauvegarde avec timestamp si besoin (ex: `.htaccess.backup.20250110143000`)
- Met à jour avec le nouveau contenu sécurisé

## 🔒 Sécurisation post-installation

**IMPORTANT** : Après l'installation réussie :

### Option 1 : Modifier `.htaccess`
Éditez `setup/.htaccess` et décommentez les lignes :
```apache
Order Deny,Allow
Deny from all
```

### Option 2 : Supprimer le dossier
```bash
rm -rf setup/
```

### Option 3 : Configuration des tests (v2.0.0)
1. **Changez le mot de passe** dans `tests/test-auth.php`
2. **Testez l'accès local** : http://localhost/youtube-chapters-studio/tests/
3. **Testez l'accès distant** avec le nouveau mot de passe
4. **Activez HTTPS** pour l'accès distant aux tests

## 📁 Structure créée

Après l'installation, votre arborescence ressemble à :
```
youtube-chapters-studio/
├── .installed                 # Marqueur d'installation (JSON)
├── chapters_data/            # Dossier des projets
│   └── .htaccess            # Protection totale (créé automatiquement)
├── elus/                    # Dossier pour elus.csv
│   └── .htaccess           # Protection CSV (créé automatiquement)
├── tests/                   # Suite de tests
│   ├── test-auth.php       # Authentification (créé automatiquement v2.0.0)
│   └── .htaccess           # Accès avec auth PHP (créé automatiquement)
└── scripts/                 # Scripts de maintenance
    └── .htaccess           # Protection totale (créé automatiquement)
```

## 🔧 Dépannage

### "Installation déjà effectuée"
- Utilisez le bouton de réinitialisation
- Ou supprimez manuellement `.installed`

### Erreurs de permissions
- Linux/Mac : `chmod 777 chapters_data/`
- Windows : Propriétés → Sécurité → Modifier

### Fichiers .htaccess non créés
- Vérifiez que PHP peut écrire dans les dossiers
- Créez-les manuellement en copiant les contenus ci-dessus
- Vérifiez que votre serveur supporte les fichiers .htaccess

### test-auth.php non créé (v2.0.0)
- Téléchargez le fichier depuis le repository
- Ou créez-le manuellement avec le code fourni
- **IMPORTANT** : Changez le mot de passe par défaut

### Extensions manquantes
- Contactez votre hébergeur
- Ou installez via PHP : `apt-get install php-mbstring`

## 📊 Fichier `.installed`

Contient les informations d'installation :
```json
{
    "version": "2.0.0",
    "date": "2025-01-10 14:30:00",
    "php_version": "8.1.0"
}
```

## ⚡ Mode développement

Pour forcer l'accès à l'installation :
- Ajoutez `?force=1` à l'URL
- Ou supprimez temporairement `.installed`

## 🔍 Vérification de la sécurité

Après l'installation, vérifiez que :
1. `chapters_data/` n'est pas accessible directement via le web
2. `/tests/` demande un mot de passe en accès distant
3. `/scripts/` n'est pas accessible via le web
4. Les fichiers CSV dans `/elus/` ne sont pas téléchargeables

Testez en essayant d'accéder à :
- `http://votre-domaine.com/chapters_data/test.json` → Doit afficher "Forbidden"
- `http://votre-domaine.com/scripts/update-titles.php` → Doit afficher "Forbidden"
- `http://votre-domaine.com/elus/elus.csv` → Doit afficher "Forbidden"
- `http://votre-domaine.com/tests/` → Doit demander un mot de passe (si accès distant)

## 🆘 Support

Si l'installation échoue :
1. Exécutez `/setup/check-installation.php` pour un diagnostic détaillé
2. Consultez `/tests/test-paths.php` pour plus d'informations
3. Vérifiez les logs d'erreur PHP de votre serveur
4. Consultez la documentation complète sur GitHub

## 🔐 Sécurité des tests (v2.0.0)

La version 2.0.0 introduit un système d'authentification pour les tests :

### Accès local vs distant
- **Local (localhost)** : Accès automatique sans mot de passe
- **Distant** : Authentification requise

### Configuration recommandée
1. **Mot de passe fort** : Au moins 12 caractères
2. **HTTPS obligatoire** : Pour l'accès distant
3. **Logs d'accès** : Surveillez les tentatives de connexion
4. **Timeout de session** : 1 heure par défaut

### Personnalisation
Dans `test-auth.php`, vous pouvez :
- Modifier le timeout de session
- Ajouter une liste blanche d'IPs
- Activer les logs d'accès
- Configurer des notifications

Cette nouvelle approche permet de garder la facilité d'accès en développement local tout en sécurisant l'accès distant.