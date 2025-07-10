<?php
/**
 * Helper pour récupérer le token CSRF
 * Utilisé par les tests JavaScript
 */

session_start();

// Générer un token CSRF si nécessaire
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Headers pour CORS si nécessaire (tests locaux)
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: text/plain; charset=utf-8');

// Retourner uniquement le token
echo $_SESSION['csrf_token'];