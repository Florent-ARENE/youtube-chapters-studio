<div class="chapter-form">
    <div class="editing-indicator" id="editing-indicator">
        Mode édition - Modifiez et cliquez sur "Mettre à jour"
    </div>
    <button type="button" class="btn btn-capture w-full" onclick="captureCurrentTime()">
        ⏱️ Capturer le temps actuel de la vidéo
    </button>
    <div class="form-group">
        <label>Temps (HH:MM:SS)</label>
        <div class="time-input">
            <div class="time-group">
                <input type="text" id="hours" placeholder="00" maxlength="2" pattern="[0-9]{0,2}">
                <div class="time-controls">
                    <button type="button" class="time-btn" onclick="adjustTime('hours', 1)">▲</button>
                    <button type="button" class="time-btn" onclick="adjustTime('hours', -1)">▼</button>
                </div>
            </div>
            <div class="time-group">
                <input type="text" id="minutes" placeholder="00" maxlength="2" pattern="[0-9]{0,2}">
                <div class="time-controls">
                    <button type="button" class="time-btn" onclick="adjustTime('minutes', 1)">▲</button>
                    <button type="button" class="time-btn" onclick="adjustTime('minutes', -1)">▼</button>
                </div>
            </div>
            <div class="time-group">
                <input type="text" id="seconds" placeholder="00" maxlength="2" pattern="[0-9]{0,2}">
                <div class="time-controls">
                    <button type="button" class="time-btn" onclick="adjustTime('seconds', 1)">▲</button>
                    <button type="button" class="time-btn" onclick="adjustTime('seconds', -1)">▼</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Choix du type de chapitre -->
    <div class="chapter-type-selector">
        <label>Type de chapitre</label>
        <div class="type-buttons">
            <button type="button" class="type-btn active" id="type-chapitre" onclick="selectChapterType('chapitre')">
                📑 Chapitre
            </button>
            <button type="button" class="type-btn" id="type-elu" onclick="selectChapterType('elu')">
                👤 Élu
            </button>
            <button type="button" class="type-btn" id="type-vote" onclick="selectChapterType('vote')">
                🗳️ Vote
            </button>
        </div>
    </div>
    
    <!-- Champs pour chapitre classique -->
    <div id="chapitre-fields">
        <div class="form-group">
            <label for="chapter-title">Titre du chapitre</label>
            <input type="text" 
                   id="chapter-title" 
                   placeholder="Introduction"
                   maxlength="<?php echo MAX_TITLE_LENGTH; ?>"
                   pattern="[^<>]{1,<?php echo MAX_TITLE_LENGTH; ?>}">
            <small class="char-count">
                <span id="chapter-title-count">0</span>/<?php echo MAX_TITLE_LENGTH; ?>
            </small>
        </div>
    </div>
    
    <!-- Champs pour élu -->
    <div id="elu-fields" style="display: none;">
        <div class="form-group">
            <label for="elu-search">Rechercher un élu</label>
            <div class="autocomplete-container">
                <input type="text" 
                       id="elu-search" 
                       placeholder="Tapez le nom de l'élu..." 
                       autocomplete="off"
                       maxlength="100">
                <div id="elu-suggestions" class="suggestions-list"></div>
            </div>
            <input type="hidden" id="selected-elu-data">
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" id="show-elu-info" checked>
                Afficher les fonctions de l'élu (première intervention)
            </label>
        </div>
    </div>
    
    <!-- Champs pour vote -->
    <div id="vote-fields" style="display: none;">
        <div class="form-group">
            <label for="vote-title">Titre du vote</label>
            <input type="text" 
                   id="vote-title" 
                   placeholder="Vote du budget 2025"
                   maxlength="<?php echo MAX_TITLE_LENGTH; ?>"
                   pattern="[^<>]{1,<?php echo MAX_TITLE_LENGTH; ?>}">
            <small class="char-count">
                <span id="vote-title-count">0</span>/<?php echo MAX_TITLE_LENGTH; ?>
            </small>
        </div>
    </div>
    
    <input type="hidden" id="editing-index" value="-1">
    <input type="hidden" id="chapter-type" value="chapitre">
    <button type="button" class="btn" id="action-button" onclick="addOrUpdateChapter()">Ajouter le chapitre</button>
    <button type="button" class="btn btn-secondary d-none ml-10" id="cancel-button" onclick="cancelEdit()">Annuler</button>
</div>