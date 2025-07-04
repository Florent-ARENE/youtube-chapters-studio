# 📺 YouTube Chapters Creator

Une application web PHP permettant de créer, gérer et partager facilement des chapitres pour vos vidéos YouTube.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-%3E%3D7.0-777BB4.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## 🎯 Fonctionnalités principales

- ✅ **Création de chapitres** : Interface intuitive pour ajouter des chapitres avec timestamps
- ⏱️ **Capture automatique** : Récupérez le temps actuel de la vidéo en un clic
- 💾 **Sauvegarde persistante** : Stockage des projets avec ID unique
- 🔗 **Partage facile** : Liens partageables et code d'intégration iframe
- 📱 **Interface responsive** : Fonctionne sur tous les appareils
- 🖼️ **Miniatures YouTube** : Affichage automatique des miniatures des vidéos
- 📋 **Export optimisé** : Format prêt pour la description YouTube

## 🚀 Installation

### Prérequis

- PHP 7.0 ou supérieur
- Serveur web (Apache, Nginx, etc.)
- Permissions d'écriture pour le dossier de l'application

### Étapes d'installation

1. **Clonez ou téléchargez le projet**
   ```bash
   git clone https://github.com/votre-repo/youtube-chapters-creator.git
   cd youtube-chapters-creator
   ```

2. **Configurez votre serveur web**
   - Pointez le document root vers le dossier du projet
   - Assurez-vous que PHP est activé

3. **Vérifiez les permissions**
   ```bash
   chmod 777 chapters_data/
   ```

4. **Accédez à l'application**
   ```
   http://votre-domaine.com/Youtube/
   ```

## 📁 Structure du projet

```
Youtube/
├── index.php              # Interface principale
├── styles.css             # Styles de l'interface principale
├── viewer.php             # Interface de visualisation (généré automatiquement)
├── viewer-styles.css      # Styles du viewer
├── chapters_data/         # Stockage des projets (JSON)
│   ├── abc12345.json
│   ├── def67890.json
│   └── ...
└── README.md              # Ce fichier
```

## 🎮 Utilisation

### 1. Créer un nouveau projet

1. Collez l'URL de votre vidéo YouTube dans le champ prévu
2. Cliquez sur "Charger la vidéo"
3. La vidéo s'affiche avec l'interface de création

### 2. Ajouter des chapitres

**Méthode 1 : Capture automatique**
- Lancez la vidéo et mettez en pause au moment souhaité
- Cliquez sur "⏱️ Capturer le temps actuel de la vidéo"
- Ajoutez le titre du chapitre
- Cliquez sur "Ajouter le chapitre"

**Méthode 2 : Saisie manuelle**
- Entrez le temps manuellement (HH:MM:SS)
- Utilisez les flèches ▲▼ pour ajuster
- Ajoutez le titre et validez

### 3. Gérer les chapitres

- **Éditer** : Cliquez sur un chapitre pour le modifier
- **Supprimer** : Utilisez le bouton 🗑️
- **Naviguer** : Cliquez sur le timestamp pour aller à ce moment
- **Trier** : Les chapitres sont triés automatiquement par temps

### 4. Sauvegarder et partager

1. Cliquez sur "💾 Sauvegarder les chapitres"
2. Copiez le lien de partage ou le code iframe
3. L'export pour YouTube est disponible en bas de page

## 🔗 Types d'URLs

### URL d'édition
```
http://votre-domaine.com/Youtube/index.php?p=XXXXXXXX
```
Permet de modifier les chapitres d'un projet existant

### URL de visualisation
```
http://votre-domaine.com/Youtube/viewer.php?p=XXXXXXXX
```
Interface épurée pour la consultation uniquement

### Code d'intégration
```html
<iframe src="http://votre-domaine.com/Youtube/viewer.php?p=XXXXXXXX" 
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
  "chapters": [
    {
      "time": 0,
      "title": "Introduction"
    },
    {
      "time": 120,
      "title": "Chapitre 2"
    }
  ],
  "created_at": "2025-01-10 14:30:00",
  "updated_at": "2025-01-10 15:45:00"
}
```

### API YouTube

L'application utilise :
- YouTube Player API pour la navigation
- URLs standardisées pour les miniatures
- Embed responsive avec paramètres optimisés

## 📋 Format d'export

Le format généré est compatible avec YouTube :
```
Chapitres :
0:00 Introduction
2:00 Chapitre 2
5:30 Conclusion
```

## 🔒 Sécurité

- Validation des URLs YouTube
- Protection XSS avec `htmlspecialchars()`
- ID de projet générés aléatoirement
- Pas d'authentification requise (à implémenter selon besoins)

## 🐛 Résolution des problèmes

### La vidéo ne se charge pas
- Vérifiez que l'URL YouTube est valide
- Assurez-vous que la vidéo n'est pas privée

### Les chapitres ne se sauvegardent pas
- Vérifiez les permissions du dossier `chapters_data/`
- Assurez-vous que PHP peut écrire des fichiers

### Les miniatures ne s'affichent pas
- Certaines vidéos n'ont pas de miniatures personnalisées
- Un emoji 🎬 s'affiche à la place

## 🚀 Améliorations futures

- [ ] Système d'authentification utilisateur
- [ ] Import de chapitres depuis une description YouTube
- [ ] Export vers d'autres formats (SRT, VTT)
- [ ] Support multi-langues
- [ ] Mode sombre/clair
- [ ] Raccourcis clavier
- [ ] Prévisualisation en temps réel
- [ ] Intégration avec l'API YouTube Data

## 📝 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 👥 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
1. Fork le projet
2. Créer une branche (`git checkout -b feature/AmazingFeature`)
3. Commit vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push sur la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 🙏 Remerciements

- YouTube pour l'API Player
- La communauté PHP
- Tous les contributeurs

---

Créé avec ❤️ pour simplifier la création de chapitres YouTube
