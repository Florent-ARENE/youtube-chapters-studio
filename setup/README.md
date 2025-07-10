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

#### `tests/.htaccess`
```apache
# Autoriser uniquement l'accès local
Order Deny,Allow
Deny from all
Allow from 127.0.0.1
Allow from ::1
Allow from localhost

# Empêcher l'indexation
Options -Indexes
```

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
   - Le fichier `.installed` qui empêche les réinstallations accidentelles

### 2. Réinstallation
Si vous devez réinstaller :
1. **Option automatique** : Utilisez le bouton "Réinitialiser l'installation"
2. **Option manuelle** : Supprimez le fichier `.installed` à la racine

### 3. Actions de réparation
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
│   └── .htaccess           # Accès local uniquement (créé automatiquement)
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
2. `/tests/` n'est accessible qu'en local (127.0.0.1)
3. `/scripts/` n'est pas accessible via le web
4. Les fichiers CSV dans `/elus/` ne sont pas téléchargeables

Testez en essayant d'accéder à :
- `http://votre-domaine.com/chapters_data/test.json` → Doit afficher "Forbidden"
- `http://votre-domaine.com/scripts/update-titles.php` → Doit afficher "Forbidden"
- `http://votre-domaine.com/elus/elus.csv` → Doit afficher "Forbidden"

## 🆘 Support

Si l'installation échoue :
1. Exécutez `/setup/check-installation.php` pour un diagnostic détaillé
2. Consultez `/tests/test-paths.php` pour plus d'informations
3. Vérifiez les logs d'erreur PHP de votre serveur
4. Consultez la documentation complète sur GitHub