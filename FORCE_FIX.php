// ============================================
// FORCE_FIX.php - Script de correction FORCÉE
// ============================================
<?php
/**
 * Ce script FORCE la correction de l'encodage en :
 * 1. Sauvegardant les données
 * 2. SUPPRIMANT complètement la table
 * 3. La recréant avec le BON encodage
 * 4. Restaurant les données
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'guest_management');
define('DB_USER', 'root');
define('DB_PASS', '');

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>🔧 Correction FORCÉE de l'encodage</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        h1 { color: #667eea; margin-bottom: 20px; }
        h2 { color: #2d3748; margin: 30px 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0; }
        .box { padding: 15px; border-radius: 8px; margin: 15px 0; }
        .success { background: #c6f6d5; color: #22543d; border-left: 4px solid #48bb78; }
        .error { background: #fed7d7; color: #742a2a; border-left: 4px solid #f56565; }
        .warning { background: #feebc8; color: #7c2d12; border-left: 4px solid #ed8936; }
        .info { background: #bee3f8; color: #2c5282; border-left: 4px solid #4299e1; }
        pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            margin: 10px 5px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-danger { background: #f56565; color: white; }
        .btn-success { background: #48bb78; color: white; }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }
        .progress { margin: 20px 0; }
        .step { padding: 10px; margin: 5px 0; border-left: 4px solid #cbd5e0; background: #f7fafc; }
        .step.active { border-left-color: #667eea; background: #edf2f7; }
        .step.done { border-left-color: #48bb78; background: #f0fff4; }
        ul { margin-left: 20px; }
        li { margin: 8px 0; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔧 Correction FORCÉE de l'encodage MySQL</h1>";

try {
    // Connexion
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    
    echo "<div class='box success'><strong>✅ Connexion établie</strong></div>";
    
    // Diagnostic initial
    echo "<h2>📊 Diagnostic initial</h2>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM invites WHERE Field = 'statut'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='box warning'>";
    echo "<strong>⚠️ Problème détecté :</strong><br>";
    echo "Type actuel : <code>" . htmlspecialchars($column['Type']) . "</code><br>";
    echo "Les caractères accentués sont mal encodés (confirmÃ© au lieu de confirmé)";
    echo "</div>";
    
    // Compter les invités
    $stmt = $pdo->query("SELECT COUNT(*) FROM invites");
    $totalGuests = $stmt->fetchColumn();
    
    echo "<div class='box info'>";
    echo "<strong>📦 Données à préserver :</strong> $totalGuests invité(s)";
    echo "</div>";
    
    if (!isset($_POST['execute'])) {
        // Afficher la confirmation
        echo "<div class='box warning'>";
        echo "<h3>⚠️ ATTENTION - Action irréversible</h3>";
        echo "<p>Ce script va :</p>";
        echo "<ul>";
        echo "<li>Sauvegarder les $totalGuests invités en mémoire</li>";
        echo "<li><strong>SUPPRIMER</strong> complètement la table 'invites'</li>";
        echo "<li>RECRÉER la table avec le bon encodage UTF-8</li>";
        echo "<li>RESTAURER tous les invités avec les statuts corrigés</li>";
        echo "</ul>";
        echo "<p><strong>Temps estimé :</strong> 5-10 secondes</p>";
        echo "</div>";
        
        echo "<form method='POST'>";
        echo "<input type='hidden' name='execute' value='1'>";
        echo "<button type='submit' class='btn btn-danger'>🚀 LANCER LA CORRECTION</button>";
        echo "<a href='debug_status.php' class='btn btn-primary'>↩️ Retour au diagnostic</a>";
        echo "</form>";
        
    } else {
        // EXÉCUTION DE LA CORRECTION
        echo "<div class='progress'>";
        
        // ÉTAPE 1 : Sauvegarde
        echo "<div class='step done'>";
        echo "<strong>📦 ÉTAPE 1/5 : Sauvegarde des données</strong><br>";
        $stmt = $pdo->query("SELECT * FROM invites ORDER BY id");
        $backup = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✅ $totalGuests invité(s) sauvegardé(s) en mémoire";
        echo "</div>";
        
        // ÉTAPE 2 : Suppression
        echo "<div class='step done'>";
        echo "<strong>🗑️ ÉTAPE 2/5 : Suppression de l'ancienne table</strong><br>";
        $pdo->exec("DROP TABLE IF EXISTS invites");
        echo "✅ Table 'invites' supprimée";
        echo "</div>";
        
        // ÉTAPE 3 : Recréation
        echo "<div class='step done'>";
        echo "<strong>🏗️ ÉTAPE 3/5 : Création de la nouvelle table</strong><br>";
        
        $createSQL = "CREATE TABLE invites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom_original VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            nom_affiche VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            telephone VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            numero_table VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            numero_envoi VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            statut ENUM('confirmé', 'décliné', 'en attente') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'en attente',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_statut (statut),
            INDEX idx_nom_affiche (nom_affiche)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($createSQL);
        echo "✅ Nouvelle table créée avec UTF-8 correct<br>";
        
        // Vérifier
        $stmt = $pdo->query("SHOW COLUMNS FROM invites WHERE Field = 'statut'");
        $newColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<small>Nouveau type : <code>" . htmlspecialchars($newColumn['Type']) . "</code></small>";
        echo "</div>";
        
        // ÉTAPE 4 : Restauration
        echo "<div class='step done'>";
        echo "<strong>💾 ÉTAPE 4/5 : Restauration des données</strong><br>";
        
        $stmt = $pdo->prepare("INSERT INTO invites 
            (id, nom_original, nom_affiche, telephone, numero_table, numero_envoi, statut, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $restored = 0;
        $confirmes = 0;
        $declines = 0;
        $attente = 0;
        
        foreach ($backup as $guest) {
            // Nettoyer le statut
            $rawStatus = $guest['statut'];
            $cleanStatus = 'en attente'; // Par défaut
            
            // Détecter le statut malgré le mauvais encodage
            if (preg_match('/confirm/i', $rawStatus)) {
                $cleanStatus = 'confirmé';
                $confirmes++;
            } elseif (preg_match('/d[éÃ©e]clin/i', $rawStatus)) {
                $cleanStatus = 'décliné';
                $declines++;
            } else {
                $attente++;
            }
            
            $stmt->execute([
                $guest['id'],
                $guest['nom_original'],
                $guest['nom_affiche'],
                $guest['telephone'],
                $guest['numero_table'],
                $guest['numero_envoi'],
                $cleanStatus,
                $guest['created_at']
            ]);
            
            $restored++;
        }
        
        echo "✅ $restored invité(s) restauré(s)<br>";
        echo "<small>📊 Répartition : $confirmes confirmés, $declines déclinés, $attente en attente</small>";
        echo "</div>";
        
        // ÉTAPE 5 : Vérification
        echo "<div class='step done'>";
        echo "<strong>✅ ÉTAPE 5/5 : Vérification finale</strong><br>";
        
        // Test d'insertion
        $testSQL = "INSERT INTO invites (nom_original, nom_affiche, telephone, statut) 
                    VALUES ('Test UTF8', 'Test UTF8', '0000', 'confirmé')";
        $pdo->exec($testSQL);
        $testId = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("SELECT statut FROM invites WHERE id = ?");
        $stmt->execute([$testId]);
        $testStatus = $stmt->fetchColumn();
        
        $expectedBytes = '636f6e6669726dc3a9';
        $actualBytes = bin2hex($testStatus);
        
        if ($testStatus === 'confirmé' && $actualBytes === $expectedBytes) {
            echo "✅ Test réussi : le statut 'confirmé' est parfaitement encodé<br>";
            echo "<small>Bytes vérifiés : $actualBytes (attendu : $expectedBytes) ✓</small>";
        } else {
            echo "⚠️ Test : statut='$testStatus', bytes=$actualBytes";
        }
        
        // Nettoyer le test
        $pdo->exec("DELETE FROM invites WHERE id = $testId");
        
        echo "</div>";
        echo "</div>"; // fin progress
        
        // RÉSULTAT FINAL
        echo "<div class='box success'>";
        echo "<h2>🎉 CORRECTION TERMINÉE AVEC SUCCÈS !</h2>";
        echo "<ul>";
        echo "<li><strong>$restored</strong> invités restaurés</li>";
        echo "<li><strong>$confirmes</strong> confirmés</li>";
        echo "<li><strong>$declines</strong> déclinés</li>";
        echo "<li><strong>$attente</strong> en attente</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<h2>🧪 Vérification détaillée</h2>";
        
        // Afficher quelques exemples
        $stmt = $pdo->query("SELECT id, nom_affiche, statut FROM invites LIMIT 5");
        $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #667eea; color: white;'>";
        echo "<th>ID</th><th>Nom</th><th>Statut</th><th>Bytes</th></tr>";
        
        foreach ($examples as $ex) {
            $bytes = bin2hex($ex['statut']);
            $bgColor = '#f7fafc';
            if ($ex['statut'] === 'confirmé') $bgColor = '#c6f6d5';
            if ($ex['statut'] === 'décliné') $bgColor = '#fed7d7';
            
            echo "<tr style='background: $bgColor;'>";
            echo "<td>{$ex['id']}</td>";
            echo "<td>{$ex['nom_affiche']}</td>";
            echo "<td><strong>{$ex['statut']}</strong></td>";
            echo "<td><code>$bytes</code></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Statistiques finales
        $stmt = $pdo->query("SELECT statut, COUNT(*) as count FROM invites GROUP BY statut");
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>📊 Répartition finale des statuts :</h3>";
        echo "<ul>";
        foreach ($stats as $stat) {
            $icon = $stat['statut'] === 'confirmé' ? '✅' : ($stat['statut'] === 'décliné' ? '❌' : '⏳');
            echo "<li>$icon <strong>{$stat['statut']}</strong> : {$stat['count']} invité(s)</li>";
        }
        echo "</ul>";
        
        echo "<div class='box info'>";
        echo "<h3>🎯 Prochaines étapes :</h3>";
        echo "<ol>";
        echo "<li>Testez avec le diagnostic : <a href='debug_status.php' class='btn btn-primary'>🔍 Debug Status</a></li>";
        echo "<li>Retournez à l'application : <a href='index.php' class='btn btn-success'>🏠 Application</a></li>";
        echo "<li>Testez la modification d'un statut dans l'interface</li>";
        echo "<li>Les badges devraient maintenant s'afficher correctement</li>";
        echo "<li>Supprimez ce fichier pour la sécurité : <code>rm FORCE_FIX.php</code></li>";
        echo "</ol>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='box error'>";
    echo "<h3>❌ ERREUR</h3>";
    echo "<p><strong>Message :</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</div></body></html>";
?>