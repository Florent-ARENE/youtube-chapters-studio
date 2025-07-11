# 📺 YouTube Chapters Studio

Une application web PHP permettant de créer, gérer et partager facilement des chapitres pour vos vidéos YouTube, avec support des interventions d'élus et des votes.

[![GitHub](https://img.shields.io/badge/GitHub-youtube--chapters--studio-181717.svg?logo=github)](https://github.com/Florent-ARENE/youtube-chapters-studio)
![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-%3E%3D7.0-777BB4.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## 🌐 Liens utiles

- **Repository GitHub** : [https://github.com/Florent-ARENE/youtube-chapters-studio](https://github.com/Florent-ARENE/youtube-chapters-studio)
- **Signaler un bug** : [Issues](https://github.com/Florent-ARENE/youtube-chapters-studio/issues)
- **Proposer une fonctionnalité** : [Discussions](https://github.com/Florent-ARENE/youtube-chapters-studio/discussions)

## 🎯 Fonctionnalités principales

- ✅ **Création de chapitres** : Interface intuitive pour ajouter des chapitres avec timestamps
- 👤 **Interventions d'élus** : Intégration d'une base de données d'élus avec autocomplétion
- 🗳️ **Moments de vote** : Marquez les moments de vote avec un style distinctif
- ⚡ **Sauvegarde automatique** : Plus besoin de cliquer sur "Sauvegarder", tout est automatique
- ⏱️ **Capture automatique** : Récupérez le temps actuel de la vidéo en un clic
- 🔄 **Décalage temporel** : Ajustez tous vos chapitres après montage de la vidéo
- 💾 **Sauvegarde persistante** : Stockage des projets avec ID unique
- 🔗 **Partage facile** : Liens partageables et code d'intégration iframe
- 📱 **Interface responsive** : Fonctionne sur tous les appareils
- 🖼️ **Miniatures YouTube** : Affichage automatique des miniatures des vidéos
- 📝 **Titres des vidéos** : Récupération automatique des titres YouTube
- 📋 **Export optimisé** : Format prêt pour la description YouTube
- 📂 **Gestion de projets** : Liste visuelle de tous vos projets avec miniatures et titres
- 🎨 **Distinction visuelle** : Trois types de contenus avec codes couleur distincts
- 🔒 **Sécurité renforcée** : Protection CSRF, XSS, validation des entrées
- 🧪 **Suite de tests complète** : Tests automatisés avec authentification sécurisée

## 🆕 Nouveautés v2.0.0

### Réorganisation complète de la structure (v2.0.0)
- **Dossier `/setup/`** : Installation guidée en 6 étapes avec vérification automatique
- **Dossier `/tests/`** : Suite de tests avec authentification pour accès distant
- **Dossier `/scripts/`** : Scripts de maintenance et d'administration
- **Sécurité améliorée** : Fichiers sensibles protégés, protection par .htaccess
- **Installation simplifiée** : Plus besoin de créer manuellement les dossiers

### Système d'authentification pour les tests
- 🔐 **Accès sécurisé** : Les tests sont protégés par mot de passe
- 🏠 **Accès local automatique** : Pas de mot de passe depuis localhost
- 🌐 **Accès distant** : Authentification requise avec session sécurisée
- ⏱️ **Sessions temporaires** : Timeout configurable (1 heure par défaut)

### Module de décalage temporel
- **Nouveau bouton "Décaler"** : Dans l'en-tête des chapitres
- **Décalage flexible** : Ajustez tous les timestamps après montage
- **Deux modes disponibles** :
  - Décaler tous les chapitres
  - Décaler à partir d'un chapitre spécifique
- **Aperçu avant application** : Visualisez les changements
- **Raccourcis pratiques** : Boutons -10s, -5s, +5s, +10s
- **Sauvegarde automatique** : Les changements sont sauvegardés instantanément

### Cas d'usage typique
Idéal après avoir coupé le début d'un live YouTube :
1. Vous supprimez 2 minutes au début dans YouTube Studio
2. Ouvrez votre projet de chapitres
3. Cliquez sur "⏱️ Décaler"
4. Entrez -120 secondes
5. Prévisualisez et appliquez
6. Tous vos chapitres sont ajustés automatiquement !

## 📸 Captures d'écran

### Interface principale
- Liste des projets avec miniatures et titres
- Interface d'édition avec player YouTube intégré
- Panneau de création de chapitres avec sélecteur de type (chapitre/élu/vote)
- Autocomplétion pour la recherche d'élus

### Fonctionnalités visuelles
- **Miniatures YouTube** : Chaque projet affiche la miniature de la vidéo
- **Titres automatiques** : Les titres des vidéos sont récupérés via l'API YouTube
- **Effet hover** : Animation au survol des cartes de projet
- **Badge de chapitres** : Nombre de chapitres affiché sur la miniature
- **Distinction des types** : Fond gris (chapitres), bleu (élus), gold (votes)

## 🚀 Installation

### Prérequis

- PHP 7.0 ou supérieur
- Serveur web (Apache, Nginx, etc.)
- Extensions PHP : mbstring, json, session
- Extension optionnelle : cURL (pour récupération des titres)
- Configuration : allow_url_fopen activé

### Installation automatique (recommandée)

1. **Clonez ou téléchargez le projet**
   ```bash
   git clone https://github.com/Florent-ARENE/youtube-chapters-studio.git
   cd youtube-chapters-studio
   ```

2. **Accédez à l'installateur**
   ```
   http://votre-domaine.com/youtube-chapters-studio/setup/
   ```

3. **Suivez les 6 étapes guidées**
   - Vérification des prérequis
   - Création automatique des dossiers
   - Configuration des permissions
   - **Création automatique des fichiers .htaccess de sécurité**
   - Tests de fonctionnement
   - Finalisation

4. **Sécurisez l'installation**
   - Modifiez `setup/.htaccess` pour bloquer l'accès
   - Ou supprimez complètement le dossier `setup/`

5. **Configurez les tests (nouveau dans v2.0.0)**
   - Modifiez le mot de passe dans `tests/test-auth.php`
   - Ou exécutez `php tests/setup-auth.php` pour une configuration guidée

6. **Ajoutez votre base d'élus**
   ```bash
   # Placez votre fichier CSV dans le dossier elus/
   cp votre-fichier-elus.csv elus/elus.csv
   ```

### Installation manuelle

Si l'installation automatique échoue :

1. **Créez les dossiers nécessaires**
   ```bash
   mkdir -p chapters_data elus tests scripts
   chmod 777 chapters_data
   chmod 755 elus tests scripts
   ```

2. **Créez les fichiers de sécurité .htaccess**
   
   **chapters_data/.htaccess** (Protection totale des données)
   ```apache
   # Interdire tout accès aux fichiers JSON
   Order Deny,Allow
   Deny from all
   Options -Indexes
   
   # Interdire l'exécution de scripts
   <FilesMatch "\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$">
       Deny from all
   </FilesMatch>
   ```
   
   **tests/.htaccess** (Authentification gérée par PHP)
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
   
   **scripts/.htaccess** (Protection des scripts CLI)
   ```apache
   # Interdire tout accès web aux scripts
   Order Deny,Allow
   Deny from all
   Options -Indexes
   ```
   
   **elus/.htaccess** (Protection des fichiers CSV)
   ```apache
   # Protéger les fichiers CSV
   <FilesMatch "\.(csv|txt)$">
       Order Allow,Deny
       Deny from all
   </FilesMatch>
   Options -Indexes
   ```

3. **Créez le fichier test-auth.php**
   - Copiez le fichier depuis le repository
   - Modifiez le mot de passe par défaut

4. **Vérifiez l'installation**
   ```
   http://votre-domaine.com/youtube-chapters-studio/setup/check-installation.php
   ```

## 📁 Structure du projet

```
youtube-chapters-studio/
├── index.php                  # Interface principale
├── viewer.php                 # Interface de visualisation (généré automatiquement)
├── ajax-handler.php           # Gestionnaire AJAX sécurisé
├── config.php                 # Configuration et sécurité
├── functions.php              # Fonctions métier
├── chapter-form.php           # Formulaire de création de chapitres
├── app.js                     # JavaScript principal
├── styles.css                 # Styles de l'interface principale
├── viewer-styles.css          # Styles du viewer
├── README.md                  # Ce fichier
├── SECURITY.md                # Documentation sécurité
├── .installed                 # Marqueur d'installation (créé automatiquement)
│
├── 📁 setup/                  # Installation et configuration
│   ├── index.php              # Interface d'installation guidée
│   ├── check-installation.php # Vérification détaillée des prérequis
│   ├── README.md              # Documentation de l'installation
│   └── .htaccess              # Protection (à activer après installation)
│
├── 📁 tests/                  # Suite de tests avec authentification
│   ├── index.php              # Dashboard interactif des tests
│   ├── test-auth.php          # Système d'authentification (à créer)
│   ├── test-ajax.php          # Test AJAX et sauvegarde automatique
│   ├── test-youtube.php       # Test API YouTube (titres + player)
│   ├── test-javascript.php    # Test initialisation JavaScript
│   ├── test-paths.php         # Test chemins et permissions
│   ├── get-csrf-token.php     # Helper CSRF pour les tests
│   ├── README.md              # Documentation des tests
│   └── .htaccess              # Authentification PHP
│
├── 📁 scripts/                # Scripts de maintenance
│   ├── update-titles.php      # Mise à jour des titres manquants
│   ├── README.md              # Documentation des scripts
│   └── .htaccess              # Protection totale
│
├── 📁 chapters_data/          # Stockage des projets JSON
│   ├── abc12345.json          # Exemple de projet
│   ├── def67890.json          # Exemple de projet
│   └── .htaccess              # Protection totale
│
└── 📁 elus/                   # Base de données des élus
    ├── elus.csv               # Liste des élus (à ajouter)
    └── .htaccess              # Protection CSV
```

## 🎮 Utilisation

### 1. Créer un nouveau projet

1. Collez l'URL de votre vidéo YouTube dans le champ prévu
2. Cliquez sur "Charger la vidéo"
3. La vidéo s'affiche avec l'interface de création
4. Le titre de la vidéo est récupéré automatiquement

### 2. Ajouter des chapitres

#### Choix du type de chapitre
- **📑 Chapitre** : Pour les sections thématiques (Introduction, Conclusion, etc.)
- **👤 Élu** : Pour marquer les prises de parole des élus
- **🗳️ Vote** : Pour marquer les moments de vote

#### Méthode 1 : Capture automatique
- Lancez la vidéo et mettez en pause au moment souhaité
- Cliquez sur "⏱️ Capturer le temps actuel de la vidéo"
- Choisissez le type de chapitre
- Pour un chapitre : ajoutez le titre
- Pour un élu : recherchez et sélectionnez l'élu
- Pour un vote : décrivez le vote (ex: "Vote du budget 2025")
- Cliquez sur "Ajouter le chapitre"
- **La sauvegarde est automatique** ✨

#### Méthode 2 : Saisie manuelle
- Entrez le temps manuellement (HH:MM:SS)
- Utilisez les flèches ▲▼ pour ajuster
- Procédez comme pour la méthode 1

### 3. Utiliser la recherche d'élus

1. **Sélectionnez "Élu"**
2. **Tapez au moins 2 lettres** du nom de l'élu
3. **Sélectionnez dans la liste** qui apparaît automatiquement
4. **Option "Afficher les fonctions"** : 
   - Coché par défaut pour la première intervention
   - Affiche le mandat et la fonction sous le nom

### 4. Gérer les chapitres

- **Éditer** : Cliquez sur un chapitre pour le modifier
- **Supprimer** : Utilisez le bouton 🗑️
- **Naviguer** : Cliquez sur le timestamp pour aller à ce moment
- **Trier** : Les chapitres sont triés automatiquement par temps
- **Identifier** : 
  - Les chapitres standards ont un fond gris
  - Les élus ont un fond bleu (#426a92) et l'icône 👤
  - Les votes ont un fond gold (#827824) et l'icône 🗳️

### 5. Sauvegarde automatique

- **Automatique** : Chaque modification est sauvegardée instantanément
- **Notification** : Un message "Sauvegardé" apparaît brièvement
- **URL mise à jour** : L'ID du projet s'ajoute automatiquement à l'URL
- **Pas d'interruption** : Continuez à travailler sans vous soucier de sauvegarder

### 6. Gérer vos projets

- **Liste visuelle** : Tous vos projets avec miniatures et titres
- **Recherche rapide** : Identifiez vos vidéos grâce aux miniatures
- **Actions rapides** : Éditer, Voir ou Copier le lien
- **Tri chronologique** : Projets récents en premier

### 7. Décaler les chapitres dans le temps

Cette fonctionnalité est utile après avoir édité votre vidéo (par exemple, supprimé le début d'un live).

1. **Ouvrir le module** : Cliquez sur "⏱️ Décaler" en haut à droite
2. **Choisir le décalage** :
   - Entrez le nombre de secondes (négatif pour reculer, positif pour avancer)
   - Utilisez les boutons rapides : -10s, -5s, +5s, +10s
3. **Sélectionner la portée** :
   - "Tous les chapitres" : décale l'ensemble des chapitres
   - "À partir d'un chapitre" : décale uniquement à partir du chapitre sélectionné
4. **Prévisualiser** : Cliquez sur "👁️ Aperçu" pour voir les changements
5. **Appliquer** : Cliquez sur "✅ Appliquer le décalage"

**Exemple pratique** : Vous avez supprimé 2 minutes au début de votre live
- Entrez `-120` secondes
- Tous vos chapitres seront reculés de 2 minutes
- Les timestamps négatifs sont automatiquement ajustés à 0:00

### 8. Partager et exporter

- **Lien d'édition** : Pour permettre les modifications
- **Code iframe** : Pour intégrer sur un site web
- **Export YouTube** : Format prêt pour la description de votre vidéo

## 🧪 Tests et vérification

### Suite de tests intégrée avec authentification

Accédez à `/tests/` pour :
- **Dashboard interactif** : Vue d'ensemble de tous les tests
- **Test des chemins** : Vérification des fichiers et permissions
- **Test AJAX** : Validation de la sauvegarde automatique
- **Test YouTube** : API et player
- **Test JavaScript** : Variables et fonctions

### Accès aux tests
- **Local** : Accès automatique depuis localhost
- **Distant** : Authentification par mot de passe requise
- **Configuration** : Modifiez le mot de passe dans `tests/test-auth.php`

### Vérification rapide

```bash
# Vérifier l'installation
http://votre-domaine.com/setup/check-installation.php

# Dashboard des tests (authentification requise si accès distant)
http://votre-domaine.com/youtube-chapters-studio/tests/
```

## 📊 Format du fichier elus.csv

Le fichier CSV doit être structuré avec des points-virgules (;) comme séparateurs :

```csv
nom;majo;groupe;description;fonction
Jean-Luc GLEYZE;Majorité départementale;Groupe Socialistes & Apparentés;Président du Département;Conseiller départemental du canton de Pessac-2
Marie DUPONT;Opposition;Groupe Gironde Avenir;Vice-Présidente;Conseillère départementale du canton de Bordeaux-1
```

**Colonnes utilisées** :
- `nom` : Nom complet de l'élu (obligatoire)
- `fonction` : Mandat et canton (affiché sous le nom)
- Les autres colonnes sont conservées pour usage futur

**Encodage** : Windows-1252 (CP1252) - géré automatiquement par l'application

## 🔗 Types d'URLs

### URL d'édition
```
http://votre-domaine.com/index.php?p=XXXXXXXX
```
Permet de modifier les chapitres d'un projet existant

### URL de visualisation
```
http://votre-domaine.com/viewer.php?p=XXXXXXXX
```
Interface épurée pour la consultation uniquement

### Code d'intégration
```html
<iframe src="http://votre-domaine.com/viewer.php?p=XXXXXXXX" 
        width="100%" 
        height="600" 
        frameborder="0" 
        allowfullscreen>
</iframe>
```

## 🎨 Personnalisation

### Modifier les couleurs

Dans `styles.css`, vous pouvez personnaliser :
- Couleur principale : `#ff0000` (rouge YouTube)
- Fond sombre : `#0f0f0f`, `#1a1a1a`, `#2a2a2a`
- Couleur de succès : `#00ff00`
- **Couleur des élus** : `#426a92` (fond) et `#aed3ff` (bordure)
- **Couleur des votes** : `#827824` (fond) et `#ffd700` (bordure)

### Personnaliser l'apparence des types de chapitres

```css
/* Dans styles.css et viewer-styles.css */

/* Chapitres standards */
.chapter-item {
    background: #2a2a2a;
}

/* Interventions d'élus */
.chapter-item.chapter-elu {
    background: #426a92;  /* Bleu moyen */
    border-left: 4px solid #aed3ff;  /* Bleu clair */
}

/* Moments de vote */
.chapter-item.chapter-vote {
    background: #827824;  /* Gold foncé */
    border-left: 4px solid #ffd700;  /* Gold brillant */
}
```

### Adapter le layout

- Largeur maximale : `max-width: 1400px`
- Breakpoint mobile : `768px`
- Ratio vidéo : `16:9`

## 🛠️ Fonctionnalités techniques

### Stockage des données

Les projets sont stockés en JSON avec la structure suivante :
```json
{
  "video_id": "dQw4w9WgXcQ",
  "video_title": "Conseil Départemental - Séance du 10 janvier",
  "chapters": [
    {
      "time": 0,
      "title": "Introduction"
    },
    {
      "time": 60,
      "type": "elu",
      "title": "Jean-Luc GLEYZE",
      "elu": {
        "nom": "Jean-Luc GLEYZE",
        "majo": "Majorité départementale",
        "groupe": "Groupe Socialistes & Apparentés",
        "description": "Président du Département",
        "fonction": "Conseiller départemental du canton de Pessac-2"
      },
      "showInfo": true
    },
    {
      "time": 300,
      "type": "vote",
      "title": "Vote du budget 2025"
    }
  ],
  "created_at": "2025-01-10 14:30:00",
  "updated_at": "2025-01-10 15:45:00"
}
```

### APIs utilisées

L'application utilise plusieurs services pour récupérer les informations :
- **YouTube Player API** : Pour la navigation et le contrôle de la vidéo
- **YouTube oEmbed** : Pour récupérer les titres des vidéos
- **noembed.com** : Service alternatif pour les métadonnées (fallback)
- **Miniatures YouTube** : URLs standardisées (img.youtube.com)

### Sécurité implémentée

- **Protection XSS** : Sanitisation de toutes les entrées
- **Protection CSRF** : Token unique par session
- **Validation stricte** : IDs YouTube, IDs de projet, titres
- **Path Traversal** : Protection contre l'accès aux fichiers
- **Headers de sécurité** : X-Frame-Options, X-XSS-Protection
- **Limites** : 500 chapitres max, 200 caractères par titre
- **Tests sécurisés** : Authentification pour l'accès distant

### Scripts de maintenance

Le dossier `scripts/` contient des utilitaires pour la maintenance :

**update-titles.php** : Met à jour les titres manquants pour les projets existants
```bash
php scripts/update-titles.php
```

Consultez `scripts/README.md` pour plus de détails sur les scripts disponibles.

## 📋 Format d'export

Le format généré est compatible avec YouTube :
```
Chapitres :
0:00 Introduction
2:00 Jean-Luc GLEYZE
5:30 Questions du public
8:15 Vote du budget 2025
10:00 Marie DUPONT
12:30 Vote sur la motion
15:00 Conclusion
```

Les noms des élus et les titres des votes sont utilisés directement comme titres de chapitres pour l'export YouTube.

## 🔒 Sécurité

### Mesures implémentées
- Validation des URLs YouTube
- Protection XSS avec `htmlspecialchars()`
- Token CSRF pour toutes les requêtes POST
- ID de projet générés aléatoirement
- Validation des données des élus
- Protection des dossiers par .htaccess
- Conversion sécurisée de l'encodage du CSV
- **Tests avec authentification sécurisée**

### Recommandations
- Utilisez HTTPS en production
- Sauvegardez régulièrement le dossier `chapters_data/`
- Limitez l'accès au dossier `setup/` après installation
- **Changez le mot de passe par défaut dans `tests/test-auth.php`**
- Surveillez les logs pour détecter les anomalies

## 🐛 Résolution des problèmes

### La vidéo ne se charge pas
- Vérifiez que l'URL YouTube est valide
- Assurez-vous que la vidéo n'est pas privée

### Les chapitres ne se sauvegardent pas
- Vérifiez les permissions du dossier `chapters_data/`
- Assurez-vous que PHP peut écrire des fichiers
- Consultez la console du navigateur (F12)

### Les miniatures ne s'affichent pas
- Certaines vidéos n'ont pas de miniatures personnalisées
- Un emoji 🎬 s'affiche à la place

### Les titres affichent "Vidéo sans titre"
- Exécutez `php scripts/update-titles.php` pour récupérer les titres manquants
- Vérifiez que votre serveur peut accéder aux APIs externes

### La recherche d'élus ne fonctionne pas
- Vérifiez que le fichier `elus/elus.csv` existe
- Assurez-vous que l'extension PHP mbstring est activée
- Vérifiez l'encodage du fichier CSV (doit être Windows-1252)

### Les caractères accentués s'affichent mal
- L'application gère automatiquement la conversion de Windows-1252 vers UTF-8
- Si le problème persiste, vérifiez l'encodage de votre fichier CSV

### La sauvegarde automatique ne fonctionne pas
- Vérifiez que JavaScript est activé dans votre navigateur
- Assurez-vous que votre serveur accepte les requêtes AJAX
- Vérifiez les permissions du dossier `chapters_data/`
- Consultez la console du navigateur pour d'éventuelles erreurs

### Installation bloquée
- Utilisez `/setup/check-installation.php` pour un diagnostic
- Vérifiez les prérequis PHP
- Créez manuellement les dossiers si nécessaire

### Accès aux tests refusé
- Vérifiez que `test-auth.php` existe dans le dossier `tests/`
- Changez le mot de passe par défaut
- Assurez-vous que le nouveau `.htaccess` est en place

## 🚀 Améliorations futures

- [ ] Système d'authentification utilisateur
- [ ] Import de chapitres depuis une description YouTube existante
- [ ] Export vers d'autres formats (SRT, VTT)
- [ ] Support multi-langues
- [ ] Mode sombre/clair (actuellement sombre uniquement)
- [ ] Raccourcis clavier pour la navigation
- [ ] Prévisualisation en temps réel des chapitres
- [ ] Intégration avec l'API YouTube Data v3
- [ ] Recherche dans les projets
- [ ] Tags et catégories pour organiser les projets
- [ ] Statistiques d'utilisation des chapitres
- [ ] Export en masse de plusieurs projets
- [ ] Sauvegarde/restauration des projets
- [ ] Support des playlists YouTube
- [ ] **Gestion avancée des élus** :
  - [ ] Import/export de la base d'élus
  - [ ] Interface d'administration des élus
  - [ ] Photos des élus
  - [ ] Groupes politiques avec couleurs
  - [ ] Historique des mandats
- [ ] **Gestion avancée des votes** :
  - [ ] Résultats des votes (pour/contre/abstention)
  - [ ] Statistiques de participation
  - [ ] Export des résultats
  - [ ] Historique des votes par élu
- [ ] **Sécurité avancée** :
  - [ ] Gestion des permissions par utilisateur
  - [ ] Logs d'audit des modifications
  - [ ] Sauvegarde automatique externalisée

## 📝 Changelog

### Version 2.0.0 (Juillet 2025)
- 🏗️ **REFONTE MAJEURE** : Réorganisation complète de l'architecture
- 🚀 **NOUVEAU** : Installation guidée en 6 étapes (/setup/)
- 🧪 **NOUVEAU** : Suite de tests avec authentification sécurisée (/tests/)
- 🔐 **NOUVEAU** : Système d'authentification pour l'accès distant aux tests
- 📁 **NOUVEAU** : Dossier scripts/ pour les outils de maintenance
- ⏱️ **NOUVEAU** : Module de décalage temporel des chapitres
- 🔄 **NOUVEAU** : Deux modes de décalage (tous ou à partir d'un chapitre)
- 👁️ **NOUVEAU** : Aperçu des changements avant application
- ⚡ **NOUVEAU** : Boutons de raccourci pour ajustement rapide (-10s, -5s, +5s, +10s)
- 🛡️ Protection contre les timestamps négatifs (ajustés à 0:00)
- 📱 Interface responsive pour le module de décalage
- 🔒 **Sécurité renforcée** : Création automatique de tous les fichiers .htaccess
- 🔐 Protection automatique des dossiers sensibles (chapters_data/, scripts/, elus/)
- 🔑 **Tests sécurisés** : Accès local automatique, distant avec mot de passe
- 📊 Dashboard des tests avec résultats en temps réel
- 🔧 Script update-titles.php déplacé dans scripts/
- 📚 Documentation complète pour chaque module (README.md dédiés)

### Version 1.3.0 (Juillet 2025)
- ⚡ **NOUVEAU** : Sauvegarde automatique AJAX
- 🗳️ **NOUVEAU** : Type "Vote" pour marquer les moments de vote
- 🎨 **NOUVEAU** : Codes couleur distincts (bleu pour élus, gold pour votes)
- ❌ Suppression du bouton "Sauvegarder" (tout est automatique)
- 🔄 Mise à jour dynamique de l'URL avec l'ID du projet
- 📝 Renommage des types : Chapitre, Élu, Vote (plus court et clair)
- 🎯 Amélioration du flux de travail sans interruption
- 🌈 Nouvelles couleurs : Élus (#426a92/#aed3ff), Votes (#827824/#ffd700)

### Version 1.2.0 (Juillet 2025)
- 👤 **NOUVEAU** : Support des interventions d'élus
- 🔍 **NOUVEAU** : Autocomplétion pour la recherche d'élus
- 📊 **NOUVEAU** : Intégration de base de données CSV
- 🎨 **NOUVEAU** : Distinction visuelle des types de chapitres
- ℹ️ **NOUVEAU** : Affichage optionnel des fonctions des élus
- 🔧 Gestion automatique de l'encodage Windows-1252
- 📱 Amélioration de l'interface responsive

### Version 1.1.0 (Juillet 2025)
- ✨ Ajout de la récupération automatique des titres YouTube
- 🖼️ Intégration des miniatures dans la liste des projets
- 🎨 Amélioration de l'interface avec effets visuels
- 🔧 Scripts de maintenance pour les titres manquants
- 📱 Meilleure adaptation mobile

### Version 1.0.0 (Juillet 2025)
- 🎉 Version initiale
- ✅ Création et édition de chapitres
- 💾 Sauvegarde persistante
- 🔗 Système de partage
- 📋 Export pour YouTube

## 📝 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 👥 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :

1. Fork le projet sur [GitHub](https://github.com/Florent-ARENE/youtube-chapters-studio)
2. Créer une branche (`git checkout -b feature/AmazingFeature`)
3. Commit vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push sur la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

### 🐛 Signaler un bug

Vous avez trouvé un bug ? [Ouvrez une issue](https://github.com/Florent-ARENE/youtube-chapters-studio/issues) sur GitHub.

### 💡 Suggestions

Des idées d'amélioration ? [Créez une discussion](https://github.com/Florent-ARENE/youtube-chapters-studio/discussions) sur GitHub.

## 🙏 Remerciements

- YouTube pour l'API Player
- La communauté PHP
- Les collectivités territoriales pour leur confiance
- Tous les contributeurs du projet

---

Créé avec ❤️ pour simplifier la création de chapitres YouTube et faciliter l'accès aux débats démocratiques et aux moments de décision