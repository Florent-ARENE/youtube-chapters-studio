/**
 * Chapter Studio - JavaScript principal
 * Version 2.0.0 - Ajout support Microsoft Stream
 * 
 * CHANGELOG v2.0.0 :
 * - Ajout variable videoType pour détecter YouTube/Stream
 * - Modification captureCurrentTime() pour gérer Stream (saisie manuelle)
 * - Ajout updateGlobalVariables() pour initialisation Stream
 * - Reste du code YouTube INCHANGÉ
 */

// Test de chargement du script
console.log('app.js chargé avec succès');

// Variables globales depuis appConfig
let chapters = window.appConfig ? window.appConfig.chapters || [] : [];
let elus = window.appConfig ? window.appConfig.elus || [] : [];
let videoType = window.appConfig ? window.appConfig.videoType || 'youtube' : 'youtube'; // NOUVEAU v2.0.0
let streamData = window.appConfig ? window.appConfig.streamData || null : null; // NOUVEAU v2.0.0
let player;
let playerReady = false;
let editingIndex = -1;
let currentChapterType = 'chapitre';
let currentProjectId = window.appConfig ? window.appConfig.projectId || '' : '';
let currentVideoId = window.appConfig ? window.appConfig.videoId || '' : '';
let currentVideoTitle = window.appConfig ? window.appConfig.videoTitle || '' : '';
let csrfToken = window.appConfig ? window.appConfig.csrfToken : '';
let maxChapters = window.appConfig ? window.appConfig.maxChapters : 500;
let maxTitleLength = window.appConfig ? window.appConfig.maxTitleLength : 200;
let saveTimeout = null;
let selectedElu = null;

// Debug: Afficher l'état initial
console.log('=== app.js initialisé ===');
console.log('appConfig existe:', typeof window.appConfig !== 'undefined');
console.log('videoType:', videoType); // NOUVEAU v2.0.0
console.log('currentVideoId:', currentVideoId);
console.log('currentProjectId:', currentProjectId);
console.log('csrfToken existe:', csrfToken ? 'oui' : 'non');
console.log('Nombre de chapitres:', chapters.length);

// NOUVEAU v2.0.0 : Fonction pour mettre à jour les variables globales (utilisé pour Stream)
window.updateGlobalVariables = function(newConfig) {
    console.log('=== Mise à jour des variables globales ===');
    if (newConfig) {
        videoType = newConfig.videoType || 'youtube';
        chapters = newConfig.chapters || [];
        elus = newConfig.elus || [];
        currentProjectId = newConfig.projectId || '';
        currentVideoId = newConfig.videoId || '';
        currentVideoTitle = newConfig.videoTitle || '';
        csrfToken = newConfig.csrfToken || csrfToken;
        maxChapters = newConfig.maxChapters || maxChapters;
        maxTitleLength = newConfig.maxTitleLength || maxTitleLength;
        streamData = newConfig.streamData || null;
        
        console.log('Variables mises à jour:');
        console.log('- videoType:', videoType);
        console.log('- currentVideoId:', currentVideoId);
        console.log('- currentProjectId:', currentProjectId);
        console.log('- chapters.length:', chapters.length);
        console.log('- elus.length:', elus.length);
        console.log('- csrfToken:', csrfToken ? 'présent' : 'absent');
        
        // Mettre à jour l'interface si nécessaire
        if (chapters.length > 0) {
            updateChaptersList();
            updateExport();
        }
    }
};

// Fonction de validation et nettoyage
function sanitizeInput(input) {
    if (typeof input !== 'string') return '';
    return input.replace(/[<>]/g, '').trim();
}

// Validation de titre
function validateTitle(title) {
    if (!title || title.length === 0) return false;
    if (title.length > maxTitleLength) return false;
    if (/<|>/.test(title)) return false;
    return true;
}

// Fonction de sauvegarde automatique
function autoSave() {
    if (saveTimeout) {
        clearTimeout(saveTimeout);
    }
    
    saveTimeout = setTimeout(() => {
        saveChapters();
    }, 500);
}

// MODIFIÉ v2.0.0 : Fonction de sauvegarde AJAX avec support Stream
function saveChapters() {
    if (!currentVideoId || chapters.length > maxChapters) return;
    
    console.log('Sauvegarde en cours...', {
        videoType: videoType, // NOUVEAU v2.0.0
        videoId: currentVideoId,
        projectId: currentProjectId,
        chaptersCount: chapters.length
    });
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'ajax-handler.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onload = function() {
        console.log('Réponse reçue:', xhr.status, xhr.responseText);
        if (xhr.status === 200) {
            try {
                const data = JSON.parse(xhr.responseText);
                if (data.success) {
                    if (!currentProjectId && data.project_id) {
                        currentProjectId = data.project_id;
                        window.history.replaceState({}, '', `index.php?p=${data.project_id}`);
                        updateShareLinks(data.share_url, data.embed_url);
                    }
                    showSaveNotification('Sauvegardé');
                } else {
                    console.error('Erreur de sauvegarde:', data.error);
                    showSaveNotification(data.error || 'Erreur de sauvegarde', 'error');
                }
            } catch (e) {
                console.error('Erreur parsing JSON:', e);
                showSaveNotification('Erreur de réponse', 'error');
            }
        } else {
            console.error('Erreur HTTP:', xhr.status);
            showSaveNotification('Erreur de connexion', 'error');
        }
    };
    
    xhr.onerror = function() {
        console.error('Erreur réseau');
        showSaveNotification('Erreur réseau', 'error');
    };
    
    // MODIFIÉ v2.0.0 : Ajout du type de vidéo et stream_data
    const params = new URLSearchParams({
        action: 'save_chapters',
        csrf_token: csrfToken,
        video_type: videoType, // NOUVEAU v2.0.0
        video_id: currentVideoId,
        video_title: currentVideoTitle,
        chapters: JSON.stringify(chapters),
        project_id: currentProjectId || ''
    });
    
    // NOUVEAU v2.0.0 : Ajouter streamData si vidéo Stream
    if (videoType === 'stream' && streamData) {
        params.append('stream_data', JSON.stringify(streamData));
    }
    
    xhr.send(params.toString());
}

// Notification de sauvegarde
function showSaveNotification(message, type = 'success') {
    const notification = document.getElementById('save-notification');
    if (!notification) return;
    
    notification.textContent = message;
    notification.className = `save-notification ${type}`;
    notification.style.display = 'block';
    
    setTimeout(() => {
        notification.style.display = 'none';
    }, 2000);
}

// Mise à jour des liens de partage
function updateShareLinks(shareUrl, embedUrl) {
    const shareSection = document.querySelector('.share-section');
    if (!shareSection) {
        const exportSection = document.querySelector('.export-section');
        if (!exportSection) return;
        
        const newShareSection = document.createElement('div');
        newShareSection.className = 'share-section';
        newShareSection.innerHTML = `
            <h3>🔗 Liens de partage</h3>
            <div class="form-group">
                <label>Lien d'édition (pour modifier les chapitres)</label>
                <div class="share-group">
                    <input type="text" class="share-input flex-1" value="${shareUrl}" readonly onclick="this.select()">
                    <button type="button" class="btn btn-secondary" onclick="copyProjectUrl('${shareUrl}')">📋 Copier</button>
                </div>
                <small class="share-link-info">
                    <a href="${shareUrl}" target="_blank">Ouvrir dans un nouvel onglet →</a>
                </small>
            </div>
            <div class="form-group">
                <label>Code d'intégration (iframe)</label>
                <div class="share-group">
                    <textarea class="share-input flex-1" rows="3" readonly onclick="this.select()"><iframe src="${embedUrl}" width="100%" height="600" frameborder="0" allowfullscreen></iframe></textarea>
                    <button type="button" class="btn btn-secondary" onclick="copyEmbedCode()">📋 Copier</button>
                </div>
            </div>
            <button class="btn btn-secondary" onclick="window.open('${embedUrl}', '_blank')">
                👁️ Prévisualiser l'iframe
            </button>
        `;
        exportSection.parentNode.insertBefore(newShareSection, exportSection);
    }
}

// Initialisation de l'API YouTube - DOIT être dans le scope global
window.onYouTubeIframeAPIReady = function() {
    console.log('API YouTube prête, initialisation du player...');
    if (document.getElementById('youtube-player')) {
        player = new YT.Player('youtube-player', {
            events: {
                'onReady': onPlayerReady,
                'onStateChange': onPlayerStateChange,
                'onError': onPlayerError
            }
        });
    }
};

function onPlayerReady(event) {
    playerReady = true;
    console.log('YouTube Player prêt');
}

function onPlayerStateChange(event) {
    // Pour debug si nécessaire
    const states = {
        '-1': 'non démarré',
        '0': 'terminé',
        '1': 'en lecture',
        '2': 'en pause',
        '3': 'mise en mémoire tampon',
        '5': 'vidéo en file'
    };
    console.log('État du player:', states[event.data] || 'inconnu');
}

function onPlayerError(event) {
    console.error('Erreur YouTube Player:', event.data);
}

// Charger l'API YouTube si nécessaire
function loadYouTubeAPI() {
    // MODIFIÉ v2.0.0 : Charger uniquement si YouTube
    if (document.getElementById('youtube-player') && videoType === 'youtube' && typeof YT === 'undefined') {
        console.log('Chargement de l\'API YouTube...');
        const tag = document.createElement('script');
        tag.src = "https://www.youtube.com/iframe_api";
        const firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    }
}

// Fonctions utilitaires
function formatTime(totalSeconds) {
    totalSeconds = Math.max(0, Math.floor(totalSeconds));
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    
    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
}

function parseTime(hours, minutes, seconds) {
    return parseInt(hours || 0) * 3600 + parseInt(minutes || 0) * 60 + parseInt(seconds || 0);
}

// MODIFIÉ v2.0.0 : Gestion du temps avec support Stream
window.captureCurrentTime = function() {
    console.log('Capture du temps - videoType:', videoType, 'playerReady:', playerReady, 'player:', player);
    
    // NOUVEAU v2.0.0 : Pour Stream, pas de capture auto
    if (videoType === 'stream') {
        alert('⚠️ La capture automatique n\'est pas disponible pour Microsoft Stream.\n\nVeuillez saisir manuellement le timestamp en regardant la vidéo.');
        document.getElementById('hours').focus();
        return;
    }
    
    // Code YouTube original (INCHANGÉ)
    if (!playerReady) {
        alert('Le lecteur YouTube n\'est pas encore prêt. Attendez quelques secondes.');
        return;
    }
    
    if (!player) {
        alert('Le lecteur YouTube n\'est pas initialisé.');
        return;
    }
    
    if (!player.getCurrentTime || typeof player.getCurrentTime !== 'function') {
        alert('La méthode getCurrentTime n\'est pas disponible.');
        console.error('player.getCurrentTime n\'est pas une fonction');
        return;
    }
    
    try {
        const currentTime = Math.floor(player.getCurrentTime());
        const hours = Math.floor(currentTime / 3600);
        const minutes = Math.floor((currentTime % 3600) / 60);
        const seconds = currentTime % 60;
        
        document.getElementById('hours').value = hours.toString().padStart(2, '0');
        document.getElementById('minutes').value = minutes.toString().padStart(2, '0');
        document.getElementById('seconds').value = seconds.toString().padStart(2, '0');
        
        console.log('Temps capturé:', currentTime, 'secondes');
    } catch (error) {
        console.error('Erreur lors de la capture du temps:', error);
        alert('Erreur lors de la capture du temps. Vérifiez la console.');
    }
}

window.adjustTime = function(field, increment) {
    const input = document.getElementById(field);
    if (!input) return;
    
    let value = parseInt(input.value || 0) + increment;
    
    if (field === 'hours') {
        value = Math.max(0, Math.min(99, value));
    } else {
        value = Math.max(0, Math.min(59, value));
    }
    
    input.value = value.toString().padStart(2, '0');
}

// Sélection du type de chapitre
window.selectChapterType = function(type) {
    if (!['chapitre', 'elu', 'vote'].includes(type)) return;
    
    currentChapterType = type;
    const chapterTypeInput = document.getElementById('chapter-type');
    if (chapterTypeInput) chapterTypeInput.value = type;
    
    // Mise à jour des boutons
    document.querySelectorAll('.type-btn').forEach(btn => btn.classList.remove('active'));
    const activeBtn = document.getElementById(`type-${type}`);
    if (activeBtn) activeBtn.classList.add('active');
    
    // Affichage des champs appropriés
    const chapitreFields = document.getElementById('chapitre-fields');
    const eluFields = document.getElementById('elu-fields');
    const voteFields = document.getElementById('vote-fields');
    
    if (chapitreFields) chapitreFields.style.display = 'none';
    if (eluFields) eluFields.style.display = 'none';
    if (voteFields) voteFields.style.display = 'none';
    
    if (type === 'chapitre' && chapitreFields) {
        chapitreFields.style.display = 'block';
    } else if (type === 'elu' && eluFields) {
        eluFields.style.display = 'block';
    } else if (type === 'vote' && voteFields) {
        voteFields.style.display = 'block';
    }
}

// Autocomplétion des élus
function setupEluSearch() {
    const eluSearch = document.getElementById('elu-search');
    if (!eluSearch) return;
    
    eluSearch.addEventListener('input', function() {
        const search = sanitizeInput(this.value).toLowerCase();
        const suggestions = document.getElementById('elu-suggestions');
        
        if (!suggestions) return;
        
        if (search.length < 2) {
            suggestions.style.display = 'none';
            return;
        }
        
        const matches = elus.filter(elu => 
            elu.nom && elu.nom.toLowerCase().includes(search)
        ).slice(0, 10);
        
        if (matches.length > 0) {
            suggestions.innerHTML = '';
            
            matches.forEach(elu => {
                const suggestionDiv = document.createElement('div');
                suggestionDiv.className = 'suggestion-item';
                suggestionDiv.innerHTML = `
                    <div class="suggestion-name">${elu.nom}</div>
                    <div class="suggestion-info">${elu.fonction || ''}</div>
                `;
                
                // Utiliser addEventListener au lieu de onclick inline
                suggestionDiv.addEventListener('click', function() {
                    selectElu(elu);
                });
                
                suggestions.appendChild(suggestionDiv);
            });
            
            suggestions.style.display = 'block';
        } else {
            suggestions.style.display = 'none';
        }
    });
}

window.selectElu = function(elu) {
    selectedElu = elu;
    const eluSearch = document.getElementById('elu-search');
    const selectedEluData = document.getElementById('selected-elu-data');
    const suggestions = document.getElementById('elu-suggestions');
    
    if (eluSearch) eluSearch.value = elu.nom;
    if (selectedEluData) selectedEluData.value = JSON.stringify(elu);
    if (suggestions) suggestions.style.display = 'none';
}

// Fermer les suggestions en cliquant ailleurs
document.addEventListener('click', function(e) {
    if (!e.target.closest('.autocomplete-container')) {
        const suggestions = document.getElementById('elu-suggestions');
        if (suggestions) suggestions.style.display = 'none';
    }
});

// Gestion des chapitres
window.editChapter = function(index) {
    if (index < 0 || index >= chapters.length) return;
    
    const chapter = chapters[index];
    editingIndex = index;
    
    const hours = Math.floor(chapter.time / 3600);
    const minutes = Math.floor((chapter.time % 3600) / 60);
    const seconds = chapter.time % 60;
    
    const hoursInput = document.getElementById('hours');
    const minutesInput = document.getElementById('minutes');
    const secondsInput = document.getElementById('seconds');
    
    if (hoursInput) hoursInput.value = hours.toString().padStart(2, '0');
    if (minutesInput) minutesInput.value = minutes.toString().padStart(2, '0');
    if (secondsInput) secondsInput.value = seconds.toString().padStart(2, '0');
    
    if (chapter.type === 'elu') {
        selectChapterType('elu');
        if (chapter.elu) {
            const eluSearch = document.getElementById('elu-search');
            const selectedEluData = document.getElementById('selected-elu-data');
            const showEluInfo = document.getElementById('show-elu-info');
            
            if (eluSearch) eluSearch.value = chapter.elu.nom;
            if (selectedEluData) selectedEluData.value = JSON.stringify(chapter.elu);
            selectedElu = chapter.elu;
            if (showEluInfo) showEluInfo.checked = chapter.showInfo || false;
        }
    } else if (chapter.type === 'vote') {
        selectChapterType('vote');
        const voteTitle = document.getElementById('vote-title');
        if (voteTitle) voteTitle.value = chapter.title;
    } else {
        selectChapterType('chapitre');
        const chapterTitle = document.getElementById('chapter-title');
        if (chapterTitle) chapterTitle.value = chapter.title;
    }
    
    const actionButton = document.getElementById('action-button');
    const cancelButton = document.getElementById('cancel-button');
    const editingIndicator = document.getElementById('editing-indicator');
    const editingIndexInput = document.getElementById('editing-index');
    
    if (actionButton) actionButton.textContent = 'Mettre à jour';
    if (cancelButton) cancelButton.classList.remove('d-none');
    if (editingIndicator) editingIndicator.style.display = 'block';
    if (editingIndexInput) editingIndexInput.value = index;
    
    updateChaptersList();
    
    const chapterForm = document.querySelector('.chapter-form');
    if (chapterForm) chapterForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

window.cancelEdit = function() {
    editingIndex = -1;
    
    const hoursInput = document.getElementById('hours');
    const minutesInput = document.getElementById('minutes');
    const secondsInput = document.getElementById('seconds');
    const chapterTitle = document.getElementById('chapter-title');
    const voteTitle = document.getElementById('vote-title');
    const eluSearch = document.getElementById('elu-search');
    const selectedEluData = document.getElementById('selected-elu-data');
    const actionButton = document.getElementById('action-button');
    const cancelButton = document.getElementById('cancel-button');
    const editingIndicator = document.getElementById('editing-indicator');
    const editingIndexInput = document.getElementById('editing-index');
    
    if (hoursInput) hoursInput.value = '';
    if (minutesInput) minutesInput.value = '';
    if (secondsInput) secondsInput.value = '';
    if (chapterTitle) chapterTitle.value = '';
    if (voteTitle) voteTitle.value = '';
    if (eluSearch) eluSearch.value = '';
    if (selectedEluData) selectedEluData.value = '';
    selectedElu = null;
    
    if (actionButton) actionButton.textContent = 'Ajouter le chapitre';
    if (cancelButton) cancelButton.classList.add('d-none');
    if (editingIndicator) editingIndicator.style.display = 'none';
    if (editingIndexInput) editingIndexInput.value = '-1';
    
    updateChaptersList();
}

window.addOrUpdateChapter = function() {
    if (chapters.length >= maxChapters && editingIndex < 0) {
        alert(`Limite de ${maxChapters} chapitres atteinte`);
        return;
    }
    
    const hours = document.getElementById('hours')?.value || '0';
    const minutes = document.getElementById('minutes')?.value || '0';
    const seconds = document.getElementById('seconds')?.value || '0';
    const totalSeconds = parseTime(hours, minutes, seconds);
    
    let chapterData = {
        time: totalSeconds
    };
    
    if (currentChapterType === 'elu') {
        const eluData = document.getElementById('selected-elu-data')?.value;
        if (!eluData) {
            alert('Veuillez sélectionner un élu');
            return;
        }
        
        chapterData.type = 'elu';
        chapterData.elu = JSON.parse(eluData);
        chapterData.title = chapterData.elu.nom;
        chapterData.showInfo = document.getElementById('show-elu-info')?.checked || false;
        
        const isFirstAppearance = !chapters.some((ch, idx) => 
            idx !== editingIndex && 
            ch.type === 'elu' && 
            ch.elu && 
            ch.elu.nom === chapterData.elu.nom
        );
        if (isFirstAppearance) {
            chapterData.showInfo = true;
        }
    } else if (currentChapterType === 'vote') {
        const title = sanitizeInput(document.getElementById('vote-title')?.value || '');
        if (!validateTitle(title)) {
            alert('Titre invalide (1-' + maxTitleLength + ' caractères, pas de < ou >)');
            return;
        }
        chapterData.type = 'vote';
        chapterData.title = title;
    } else {
        const title = sanitizeInput(document.getElementById('chapter-title')?.value || '');
        if (!validateTitle(title)) {
            alert('Titre invalide (1-' + maxTitleLength + ' caractères, pas de < ou >)');
            return;
        }
        chapterData.title = title;
    }
    
    if (editingIndex >= 0) {
        chapters[editingIndex] = chapterData;
        cancelEdit();
    } else {
        chapters.push(chapterData);
        
        // Reset fields
        const hoursInput = document.getElementById('hours');
        const minutesInput = document.getElementById('minutes');
        const secondsInput = document.getElementById('seconds');
        const chapterTitle = document.getElementById('chapter-title');
        const voteTitle = document.getElementById('vote-title');
        const eluSearch = document.getElementById('elu-search');
        const selectedEluData = document.getElementById('selected-elu-data');
        
        if (hoursInput) hoursInput.value = '';
        if (minutesInput) minutesInput.value = '';
        if (secondsInput) secondsInput.value = '';
        if (chapterTitle) chapterTitle.value = '';
        if (voteTitle) voteTitle.value = '';
        if (eluSearch) eluSearch.value = '';
        if (selectedEluData) selectedEluData.value = '';
        selectedElu = null;
    }

    chapters.sort((a, b) => a.time - b.time);

    updateChaptersList();
    updateExport();
    autoSave();
}

window.deleteChapter = function(index) {
    if (index < 0 || index >= chapters.length) return;
    
    if (confirm('Êtes-vous sûr de vouloir supprimer ce chapitre ?')) {
        chapters.splice(index, 1);
        if (editingIndex === index) {
            cancelEdit();
        }
        updateChaptersList();
        updateExport();
        autoSave();
    }
}

// CORRIGÉ v2.0.2 : Navigation avec autoplay Stream
window.goToTime = function(seconds) {
    console.log('goToTime appelé:', seconds, 'secondes - videoType:', videoType);
    
    if (videoType === 'youtube') {
        // Navigation YouTube (code original)
        if (playerReady && player && player.seekTo) {
            player.seekTo(seconds, true);
            if (player.playVideo) {
                player.playVideo();
            }
        } else {
            console.warn('Player YouTube non prêt');
        }
    } else if (videoType === 'stream') {
        // Navigation Stream avec autoplay
        const iframe = document.getElementById('stream-player');
        if (!iframe) {
            console.error('Iframe stream-player non trouvé');
            return;
        }
        
        if (!streamData) {
            console.error('streamData non défini');
            return;
        }
        
        console.log('streamData disponible:', streamData);
        
        // Construire l'objet de navigation
        const navObj = {
            playbackOptions: {
                startTimeInSeconds: seconds,
                timestampedLinkReferrerInfo: {
                    scenario: "ChapterShare",
                    additionalInfo: { isSharedChapterAuto: false }
                }
            },
            referralInfo: {
                referralApp: "StreamWebApp",
                referralView: "ShareChapterLink",
                referralAppPlatform: "Web",
                referralMode: "view"
            }
        };
        
        // Encoder en JSON puis Base64
        const navJson = JSON.stringify(navObj);
        const navEncoded = btoa(navJson);
        
        // NOUVEAU v2.0.2 : Paramètre embed pour l'autoplay
        const embedParam = encodeURIComponent('{"af":true,"ust":true}');
        
        // Construire la nouvelle URL embed.aspx avec nav + embed
        let newUrl = streamData.embed_url;
        
        // Si embed_url contient déjà des paramètres, ajouter &nav=, sinon ?nav=
        if (newUrl.includes('?')) {
            newUrl += '&nav=' + encodeURIComponent(navEncoded);
        } else {
            newUrl += '?nav=' + encodeURIComponent(navEncoded);
        }
        
        // NOUVEAU v2.0.2 : Ajouter le paramètre embed pour l'autoplay
        newUrl += '&embed=' + embedParam;
        
        // Ajouter &ga=1 si ce n'est pas déjà présent
        if (!newUrl.includes('ga=')) {
            newUrl += '&ga=1';
        }
        
        console.log('Navigation Stream vers:', seconds, 's');
        console.log('Nouvelle URL iframe:', newUrl);
        
        // Recharger l'iframe avec la nouvelle URL
        iframe.src = newUrl;
    }
}

// Mise à jour de l'interface
function updateChaptersList() {
    const listElement = document.getElementById('chapters-list');
    const countElement = document.getElementById('chapter-count');
    
    if (!listElement) return;
    
    listElement.innerHTML = '';
    if (countElement) countElement.textContent = `${chapters.length} chapitre(s)`;

    chapters.forEach((chapter, index) => {
        const chapterDiv = document.createElement('div');
        chapterDiv.className = 'chapter-item';
        if (index === editingIndex) {
            chapterDiv.classList.add('edit-mode');
        }
        if (chapter.type === 'elu') {
            chapterDiv.classList.add('chapter-elu');
        } else if (chapter.type === 'vote') {
            chapterDiv.classList.add('chapter-vote');
        }
        
        let titleHtml = '';
        if (chapter.type === 'elu' && chapter.elu) {
            titleHtml = `
                <span class="chapter-title cursor-pointer" onclick="editChapter(${index})">
                    <span class="elu-icon">👤</span> ${chapter.elu.nom}
                </span>`;
            if (chapter.showInfo && chapter.elu.fonction) {
                titleHtml += `<div class="elu-info">${chapter.elu.fonction}</div>`;
            }
        } else if (chapter.type === 'vote') {
            titleHtml = `
                <span class="chapter-title cursor-pointer" onclick="editChapter(${index})">
                    <span class="vote-icon">🗳️</span> ${chapter.title}
                </span>`;
        } else {
            titleHtml = `
                <span class="chapter-title cursor-pointer" onclick="editChapter(${index})">
                    ${chapter.title}
                </span>`;
        }
        
        chapterDiv.innerHTML = `
            <span class="chapter-time cursor-pointer" onclick="goToTime(${chapter.time})">
                ${formatTime(chapter.time)}
            </span>
            ${titleHtml}
            <div class="chapter-actions">
                <button class="btn btn-icon btn-secondary" onclick="editChapter(${index})">✏️</button>
                <button class="btn btn-icon btn-secondary" onclick="deleteChapter(${index})">🗑️</button>
            </div>
        `;
        listElement.appendChild(chapterDiv);
    });
}

function updateExport() {
    const exportText = document.getElementById('export-text');
    if (!exportText) return;
    
    let text = 'Chapitres :\n';
    
    chapters.forEach(chapter => {
        text += `${formatTime(chapter.time)} ${chapter.title || chapter.elu?.nom || ''}\n`;
    });

    exportText.value = text;
}

// Module de décalage temporel
window.toggleTimeShift = function() {
    console.log('toggleTimeShift appelé');
    const module = document.getElementById('time-shift-module');
    if (!module) {
        console.error('Module time-shift-module non trouvé');
        return;
    }
    
    const isVisible = module.style.display !== 'none';
    
    if (!isVisible) {
        module.style.display = 'block';
        updateShiftChapterSelect();
    } else {
        module.style.display = 'none';
        const preview = document.getElementById('shift-preview');
        if (preview) preview.style.display = 'none';
    }
}

window.toggleShiftMode = function() {
    const modeInput = document.querySelector('input[name="shift-mode"]:checked');
    if (!modeInput) return;
    
    const mode = modeInput.value;
    const fromChapterDiv = document.getElementById('shift-from-chapter');
    
    if (!fromChapterDiv) return;
    
    if (mode === 'from') {
        fromChapterDiv.style.display = 'block';
        updateShiftChapterSelect();
    } else {
        fromChapterDiv.style.display = 'none';
    }
}

function updateShiftChapterSelect() {
    const select = document.getElementById('shift-start-chapter');
    if (!select) return;
    
    select.innerHTML = '';
    
    chapters.forEach((chapter, index) => {
        const option = document.createElement('option');
        option.value = index;
        option.textContent = `${formatTime(chapter.time)} - ${chapter.title || chapter.elu?.nom || ''}`;
        select.appendChild(option);
    });
}

window.adjustShiftTime = function(amount) {
    console.log('adjustShiftTime appelé avec', amount);
    const input = document.getElementById('shift-seconds');
    if (!input) {
        console.error('Input shift-seconds non trouvé');
        return;
    }
    
    const currentValue = parseInt(input.value) || 0;
    const newValue = Math.max(-86400, Math.min(86400, currentValue + amount));
    input.value = newValue;
}

window.previewTimeShift = function() {
    console.log('previewTimeShift appelé');
    const shiftSecondsInput = document.getElementById('shift-seconds');
    const modeInput = document.querySelector('input[name="shift-mode"]:checked');
    const startChapterSelect = document.getElementById('shift-start-chapter');
    
    if (!shiftSecondsInput || !modeInput) {
        console.error('Éléments manquants pour le preview');
        return;
    }
    
    const shiftSeconds = parseInt(shiftSecondsInput.value) || 0;
    const mode = modeInput.value;
    const startIndex = mode === 'from' && startChapterSelect ? parseInt(startChapterSelect.value) : 0;
    
    if (shiftSeconds === 0) {
        alert('Veuillez entrer un décalage différent de 0');
        return;
    }
    
    const preview = document.getElementById('shift-preview');
    const previewContent = document.getElementById('shift-preview-content');
    
    if (!preview || !previewContent) {
        console.error('Éléments de preview non trouvés');
        return;
    }
    
    previewContent.innerHTML = '';
    
    let hasChanges = false;
    
    chapters.forEach((chapter, index) => {
        if (index >= startIndex) {
            const oldTime = chapter.time;
            const newTime = Math.max(0, oldTime + shiftSeconds);
            
            if (newTime !== oldTime) {
                hasChanges = true;
                const item = document.createElement('div');
                item.className = 'shift-preview-item';
                item.innerHTML = `
                    <span>${chapter.title || chapter.elu?.nom || ''}</span>
                    <span>
                        <span class="shift-old-time">${formatTime(oldTime)}</span>
                        <span class="shift-arrow">→</span>
                        <span class="shift-new-time">${formatTime(newTime)}</span>
                    </span>
                `;
                previewContent.appendChild(item);
            }
        }
    });
    
    if (hasChanges) {
        preview.style.display = 'block';
    } else {
        alert('Aucun changement ne sera appliqué avec ces paramètres');
    }
}

window.applyTimeShift = function() {
    console.log('applyTimeShift appelé');
    const shiftSecondsInput = document.getElementById('shift-seconds');
    const modeInput = document.querySelector('input[name="shift-mode"]:checked');
    const startChapterSelect = document.getElementById('shift-start-chapter');
    
    if (!shiftSecondsInput || !modeInput) {
        console.error('Éléments manquants pour appliquer le décalage');
        return;
    }
    
    const shiftSeconds = parseInt(shiftSecondsInput.value) || 0;
    const mode = modeInput.value;
    const startIndex = mode === 'from' && startChapterSelect ? parseInt(startChapterSelect.value) : 0;
    
    if (shiftSeconds === 0) {
        alert('Veuillez entrer un décalage différent de 0');
        return;
    }
    
    if (!confirm(`Êtes-vous sûr de vouloir décaler ${mode === 'all' ? 'tous les chapitres' : 'les chapitres à partir de l\'index ' + startIndex} de ${shiftSeconds} secondes ?`)) {
        return;
    }
    
    let modified = false;
    chapters = chapters.map((chapter, index) => {
        if (index >= startIndex) {
            const newTime = Math.max(0, chapter.time + shiftSeconds);
            if (newTime !== chapter.time) {
                modified = true;
                return { ...chapter, time: newTime };
            }
        }
        return chapter;
    });
    
    if (modified) {
        chapters.sort((a, b) => a.time - b.time);
        
        updateChaptersList();
        updateExport();
        autoSave();
        
        toggleTimeShift();
        
        // Reset values
        if (shiftSecondsInput) shiftSecondsInput.value = '-5';
        const allModeInput = document.querySelector('input[name="shift-mode"][value="all"]');
        if (allModeInput) allModeInput.checked = true;
        toggleShiftMode();
        
        showSaveNotification('Décalage appliqué et sauvegardé');
    } else {
        alert('Aucun changement n\'a été appliqué');
    }
}

// Fonctions de copie
window.copyProjectUrl = function(url) {
    const tempInput = document.createElement('input');
    tempInput.value = url;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
    
    if (event && event.target) {
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '✅';
        button.style.background = '#00ff00';
        button.style.color = '#000';
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.style.background = '';
            button.style.color = '';
        }, 1500);
    }
}

window.copyEmbedCode = function() {
    const textarea = document.querySelector('.share-section textarea');
    if (!textarea) return;
    
    textarea.select();
    document.execCommand('copy');
    
    if (event && event.target) {
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '✅ Copié !';
        button.style.background = '#00ff00';
        button.style.color = '#000';
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.style.background = '';
            button.style.color = '';
        }, 1500);
    }
}

window.copyToClipboard = function() {
    const exportText = document.getElementById('export-text');
    if (!exportText) return;
    
    exportText.select();
    document.execCommand('copy');
    
    if (event && event.target) {
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '✅ Copié !';
        button.style.background = '#00ff00';
        button.style.color = '#000';
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.style.background = '';
            button.style.color = '';
        }, 1500);
    }
}

// Compteurs de caractères
function setupCharCounters() {
    const chapterTitle = document.getElementById('chapter-title');
    const voteTitle = document.getElementById('vote-title');
    
    if (chapterTitle) {
        chapterTitle.addEventListener('input', function() {
            const count = this.value.length;
            const counter = document.getElementById('chapter-title-count');
            if (counter) counter.textContent = count;
        });
    }
    
    if (voteTitle) {
        voteTitle.addEventListener('input', function() {
            const count = this.value.length;
            const counter = document.getElementById('vote-title-count');
            if (counter) counter.textContent = count;
        });
    }
}

// Événements pour les champs de temps
function setupTimeFields() {
    ['hours', 'minutes', 'seconds'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (id === 'hours') {
                    if (value !== '' && parseInt(value) > 99) value = '99';
                } else {
                    if (value !== '' && parseInt(value) > 59) value = '59';
                }
                
                e.target.value = value;
            });

            element.addEventListener('focus', function(e) {
                if (e.target.value === '00') {
                    e.target.value = '';
                }
            });

            element.addEventListener('blur', function(e) {
                if (e.target.value === '') {
                    e.target.value = '00';
                }
            });
        }
    });
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM chargé, initialisation...');
    
    // Charger l'API YouTube si nécessaire
    loadYouTubeAPI();
    
    // Setup des event listeners
    setupEluSearch();
    setupCharCounters();
    setupTimeFields();
    
    // Mise à jour initiale de l'interface
    if (chapters.length > 0) {
        updateChaptersList();
        updateExport();
    }
    
    // Initialiser le type de chapitre par défaut
    selectChapterType('chapitre');
    
    console.log('Initialisation terminée');
});