<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'zepa9192_guest');
define('DB_USER', 'zepa9192_zuzu');
define('DB_PASS', 'Bonjour@123');

// Créer la base de données si elle n'existe pas
try {
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer la base de données
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Utiliser la base de données
    $pdo->exec("USE " . DB_NAME);
    
    // Créer la table des invités
    $pdo->exec("CREATE TABLE IF NOT EXISTS invites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom_original VARCHAR(255) NOT NULL,
        nom_affiche VARCHAR(255) NOT NULL,
        telephone VARCHAR(50),
        numero_table VARCHAR(50),
        numero_envoi VARCHAR(50),
        statut ENUM('confirmé', 'décliné', 'en attente') DEFAULT 'en attente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_statut (statut),
        INDEX idx_nom_affiche (nom_affiche)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Créer la table des paramètres (mot de passe)
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) UNIQUE NOT NULL,
        setting_value TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>