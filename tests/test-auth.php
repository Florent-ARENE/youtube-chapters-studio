<?php
/**
 * Configuration de l'authentification pour les tests
 * À placer dans tests/test-auth.php
 */

// Configuration - CHANGEZ CE MOT DE PASSE !
define('TEST_PASSWORD', 'ChangezMoiMaintenant2025!');
define('TEST_SESSION_KEY', 'test_authenticated');
define('TEST_SESSION_TIMEOUT', 3600); // 1 heure

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie si l'utilisateur est authentifié
 */
function isTestAuthenticated() {
    // Toujours autoriser l'accès local
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
    if (in_array($clientIP, ['127.0.0.1', '::1']) || $_SERVER['HTTP_HOST'] === 'localhost') {
        return true;
    }
    
    // Vérifier la session
    if (isset($_SESSION[TEST_SESSION_KEY]) && isset($_SESSION[TEST_SESSION_KEY . '_time'])) {
        // Vérifier le timeout
        if (time() - $_SESSION[TEST_SESSION_KEY . '_time'] < TEST_SESSION_TIMEOUT) {
            // Renouveler le timeout
            $_SESSION[TEST_SESSION_KEY . '_time'] = time();
            return true;
        } else {
            // Session expirée
            unset($_SESSION[TEST_SESSION_KEY]);
            unset($_SESSION[TEST_SESSION_KEY . '_time']);
        }
    }
    
    return false;
}

/**
 * Authentifie l'utilisateur avec le mot de passe
 */
function authenticateTest($password) {
    if ($password === TEST_PASSWORD) {
        $_SESSION[TEST_SESSION_KEY] = true;
        $_SESSION[TEST_SESSION_KEY . '_time'] = time();
        return true;
    }
    return false;
}

/**
 * Déconnecte l'utilisateur
 */
function logoutTest() {
    unset($_SESSION[TEST_SESSION_KEY]);
    unset($_SESSION[TEST_SESSION_KEY . '_time']);
}

/**
 * Affiche le formulaire de connexion
 */
function showLoginForm($error = '') {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Authentification - Tests YouTube Chapters Studio</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #0f0f0f;
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
            }
            
            .login-container {
                background: #1a1a1a;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
                width: 100%;
                max-width: 400px;
            }
            
            h1 {
                color: #ff0000;
                text-align: center;
                margin-bottom: 30px;
                font-size: 1.8rem;
            }
            
            .warning {
                background: rgba(255, 0, 0, 0.1);
                border: 1px solid #ff0000;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
                text-align: center;
                font-size: 0.9rem;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            label {
                display: block;
                margin-bottom: 8px;
                color: #ccc;
                font-weight: 500;
            }
            
            input[type="password"] {
                width: 100%;
                padding: 12px 16px;
                background: #2a2a2a;
                border: 1px solid #444;
                border-radius: 8px;
                color: #fff;
                font-size: 16px;
            }
            
            input[type="password"]:focus {
                outline: none;
                border-color: #ff0000;
                box-shadow: 0 0 0 3px rgba(255, 0, 0, 0.1);
            }
            
            .btn {
                width: 100%;
                padding: 12px 24px;
                background: #ff0000;
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .btn:hover {
                background: #cc0000;
                transform: translateY(-2px);
            }
            
            .error {
                background: rgba(255, 0, 0, 0.2);
                border: 1px solid #ff0000;
                color: #ff0000;
                padding: 10px;
                border-radius: 6px;
                margin-bottom: 20px;
                text-align: center;
            }
            
            .info {
                margin-top: 20px;
                text-align: center;
                color: #666;
                font-size: 0.9rem;
            }
            
            .back-link {
                display: block;
                text-align: center;
                margin-top: 20px;
                color: #ff0000;
                text-decoration: none;
            }
            
            .back-link:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h1>🔒 Zone protégée</h1>
            
            <div class="warning">
                ⚠️ Cette zone contient des outils de test et de développement
            </div>
            
            <?php if ($error): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" 
                           id="password" 
                           name="test_password" 
                           required 
                           autofocus
                           placeholder="Entrez le mot de passe">
                </div>
                
                <button type="submit" class="btn">
                    🔓 Accéder aux tests
                </button>
            </form>
            
            <div class="info">
                Accès local automatique depuis 127.0.0.1
            </div>
            
            <a href="../" class="back-link">← Retour à l'application</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Middleware de protection
 * À appeler au début de chaque fichier de test
 */
function requireTestAuth() {
    // Gestion de la déconnexion
    if (isset($_GET['logout'])) {
        logoutTest();
        header('Location: index.php');
        exit;
    }
    
    // Gestion de l'authentification
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_password'])) {
        if (authenticateTest($_POST['test_password'])) {
            // Rediriger pour éviter le re-POST
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            showLoginForm('Mot de passe incorrect');
        }
    }
    
    // Vérifier l'authentification
    if (!isTestAuthenticated()) {
        showLoginForm();
    }
}

// Fonction helper pour afficher le statut d'authentification
function getAuthStatus() {
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
    $isLocal = in_array($clientIP, ['127.0.0.1', '::1']) || $_SERVER['HTTP_HOST'] === 'localhost';
    
    if ($isLocal) {
        return "🟢 Accès local automatique";
    } elseif (isset($_SESSION[TEST_SESSION_KEY])) {
        $remaining = TEST_SESSION_TIMEOUT - (time() - $_SESSION[TEST_SESSION_KEY . '_time']);
        $minutes = floor($remaining / 60);
        return "🔓 Authentifié (expire dans {$minutes} min)";
    } else {
        return "🔒 Non authentifié";
    }
}
?>