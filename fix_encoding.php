<?php
require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Correction de l'encodage</title>
    <style>
        body { 
            font-family: 'Segoe UI', sans-serif; 
            padding: 40px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .success { color: #48bb78; font-weight: bold; padding: 10px; background: #c6f6d5; border-radius: 5px; margin: 10px 0; }
        .error { color: #f56565; font-weight: bold; padding: 10px; background: #fed7d7; border-radius: 5px; margin: 10px 0; }
        .warning { color: #ed8936; font-weight: bold; padding: 10px; background: #feebc8; border-radius: 5px; margin: 10px 0; }
        .info { color: #4299e1; font-weight: bold; padding: 10px; background: #bee3f8; border-radius: 5px; margin: 10px 0; }
        pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn { 
            background: #667eea; 
            color: white; 
            padding: 15px 30px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }
        .btn-danger { background: #f56565; }
        .btn:hover { opacity: 0.9; }
        h1 { color: #667eea; }
        h2 { color: #2d3748; border-bottom: 2px solid #667eea; padding-bottom: 10px; margin-top: 30px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔧 Correction de l'encodage de la base de données</h1>";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<div class='success'>✅ Connexion à la base de données réussie</div>";
    
    // Étape 1 : Sauvegarder les données actuelles
    echo "<h2>📦 Étape 1 : Sauvegarde des données</h2>";
    
    $stmt = $pdo->query("SELECT * FROM invites");
    $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($guests);
    
    echo "<div class='info'>📊 Nombre d'invités à sauvegarder : <strong>$count</strong></div>";
    
    if (!isset($_POST['confirm_fix'])) {
        // Afficher un aperçu et demander confirmation
        echo "<div class='warning'>⚠️ <strong>ATTENTION :</strong> Cette opération va supprimer et recréer la table 'invites' avec le bon encodage.</div>";
        
        echo "<h3>Aperçu des données actuelles :</h3>";
        echo "<pre>";
        echo "Nombre total d'invités : $count\n\n";
        if ($count > 0) {
            echo "Exemple des 3 premiers invités :\n";
            for ($i = 0; $i < min(3, $count); $i++) {
                $g = $guests[$i];
                echo "\n" . ($i + 1) . ". {$g['nom_original']}\n";
                echo "   Statut actuel : '{$g['statut']}'\n";
                echo "   Statut bytes : " . bin2hex($g['statut']) . "\n";
            }
        }
        echo "</pre>";
        
        echo "<form method='POST'>";
        echo "<input type='hidden' name='confirm_fix' value='1'>";
        echo "<p><strong>Voulez-vous continuer ?</strong></p>";
        echo "<button type='submit' class='btn btn-danger'>🔧 Oui, corriger l'encodage maintenant</button>";
        echo "<a href='index.php' class='btn'>❌ Annuler</a>";
        echo "</form>";
        
    } else {
        // Exécuter la correction
        echo "<div class='info'>🔄 Correction en cours...</div>";
        
        // Étape 2 : Supprimer la table
        echo "<h2>🗑️ Étape 2 : Suppression de l'ancienne table</h2>";
        $pdo->exec("DROP TABLE IF EXISTS invites");
        echo "<div class='success'>✅ Table supprimée</div>";
        
        // Étape 3 : Recréer la table avec le BON encodage
        echo "<h2>🏗️ Étape 3 : Création de la nouvelle table</h2>";
        $pdo->exec("CREATE TABLE invites (
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
        
        echo "<div class='success'>✅ Nouvelle table créée avec utf8mb4</div>";
        
        // Vérifier l'encodage
        $stmt = $pdo->query("SHOW CREATE TABLE invites");
        $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<h3>Vérification de l'encodage :</h3>";
        echo "<pre>" . htmlspecialchars($createTable['Create Table']) . "</pre>";
        
        // Étape 4 : Réinsérer les données avec conversion
        echo "<h2>💾 Étape 4 : Restauration des données</h2>";
        
        $stmt = $pdo->prepare("INSERT INTO invites 
            (id, nom_original, nom_affiche, telephone, numero_table, numero_envoi, statut, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $imported = 0;
        $errors = 0;
        
        foreach ($guests as $guest) {
            // Nettoyer et convertir le statut
            $statut = trim($guest['statut']);
            
            // Mapper les statuts mal encodés vers les bons
            if (strpos($statut, 'confirm') !== false || strpos($statut, 'Confirm') !== false) {
                $statut = 'confirmé';
            } elseif (strpos($statut, 'clin') !== false || strpos($statut, 'Clin') !== false) {
                $statut = 'décliné';
            } else {
                $statut = 'en attente';
            }
            
            try {
                $stmt->execute([
                    $guest['id'],
                    $guest['nom_original'],
                    $guest['nom_affiche'],
                    $guest['telephone'],
                    $guest['numero_table'],
                    $guest['numero_envoi'],
                    $statut,
                    $guest['created_at']
                ]);
                $imported++;
            } catch (Exception $e) {
                $errors++;
                echo "<div class='error'>❌ Erreur pour l'invité ID {$guest['id']} : " . $e->getMessage() . "</div>";
            }
        }
        
        echo "<div class='success'>✅ Données restaurées : <strong>$imported</strong> invités</div>";
        if ($errors > 0) {
            echo "<div class='error'>⚠️ Erreurs rencontrées : <strong>$errors</strong></div>";
        }
        
        // Étape 5 : Vérification finale
        echo "<h2>✅ Étape 5 : Vérification finale</h2>";
        
        $stmt = $pdo->query("SHOW COLUMNS FROM invites WHERE Field = 'statut'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Type de colonne statut :</strong> <code>" . htmlspecialchars($column['Type']) . "</code></p>";
        
        // Vérifier les statuts en base
        $stmt = $pdo->query("SELECT statut, COUNT(*) as count FROM invites GROUP BY statut");
        $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Répartition des statuts :</h3>";
        echo "<ul>";
        foreach ($statuts as $s) {
            $bytes = bin2hex($s['statut']);
            echo "<li><strong>{$s['statut']}</strong> : {$s['count']} invité(s) <small>(bytes: $bytes)</small></li>";
        }
        echo "</ul>";
        
        // Test d'insertion
        echo "<h2>🧪 Test d'insertion</h2>";
        try {
            $pdo->exec("INSERT INTO invites (nom_original, nom_affiche, telephone, statut) 
                        VALUES ('Test Encodage', 'Test Encodage', '0000000000', 'confirmé')");
            $testId = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("SELECT statut FROM invites WHERE id = ?");
            $stmt->execute([$testId]);
            $testStatut = $stmt->fetchColumn();
            
            if ($testStatut === 'confirmé') {
                echo "<div class='success'>✅ Test réussi : le statut 'confirmé' est correctement enregistré</div>";
                echo "<p>Bytes du statut testé : " . bin2hex($testStatut) . " (attendu : 636f6e6669726dc3a9)</p>";
            } else {
                echo "<div class='error'>❌ Test échoué : statut attendu 'confirmé', obtenu '$testStatut'</div>";
            }
            
            // Supprimer l'entrée de test
            $pdo->exec("DELETE FROM invites WHERE id = $testId");
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erreur lors du test : " . $e->getMessage() . "</div>";
        }
        
        echo "<h2>🎉 Correction terminée !</h2>";
        echo "<div class='success'>
            ✅ La base de données a été corrigée avec succès !<br>
            <strong>$imported invités</strong> ont été restaurés avec les bons encodages.
        </div>";
        
        echo "<p><a href='index.php' class='btn'>🏠 Retour à l'application</a></p>";
        echo "<p><a href='debug_status.php' class='btn'>🔍 Vérifier avec Debug Status</a></p>";
        
        echo "<div class='info'>
            <strong>ℹ️ Prochaines étapes :</strong>
            <ol>
                <li>Retournez sur l'application principale</li>
                <li>Testez la modification d'un statut</li>
                <li>Les badges devraient maintenant s'afficher correctement</li>
                <li>Vous pouvez supprimer ce fichier fix_encoding.php pour plus de sécurité</li>
            </ol>
        </div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur : " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</div></body></html>";
?>