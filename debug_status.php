<?php
require_once 'config.php';
require_once 'includes/GuestManager.php';

$guestManager = new GuestManager();

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Debug - Statuts</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        table { border-collapse: collapse; background: white; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #667eea; color: white; }
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 11px; }
        .badge-confirmed { background: #c6f6d5; color: #22543d; }
        .badge-declined { background: #fed7d7; color: #742a2a; }
        .badge-pending { background: #feebc8; color: #7c2d12; }
        pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>";

echo "<h1>🔍 Debug - Gestion des statuts</h1>";

// 1. Tester la connexion à la base
echo "<h2>1️⃣ Connexion à la base de données</h2>";
try {
    $db = Database::getInstance()->getConnection();
    echo "<p class='success'>✅ Connexion réussie</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur : " . $e->getMessage() . "</p>";
    exit;
}

// 2. Lister tous les invités avec leurs statuts
echo "<h2>2️⃣ Liste des invités actuels</h2>";
$guests = $guestManager->getGuests();

if (empty($guests)) {
    echo "<p class='error'>⚠️ Aucun invité dans la base de données</p>";
} else {
    echo "<p class='success'>✅ " . count($guests) . " invité(s) trouvé(s)</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Nom affiché</th><th>Téléphone</th><th>Statut (Base)</th><th>Statut (Badge)</th><th>Actions</th></tr>";
    
    foreach ($guests as $guest) {
        $badgeClass = '';
        switch($guest['statut']) {
            case 'confirmé':
                $badgeClass = 'badge-confirmed';
                break;
            case 'décliné':
                $badgeClass = 'badge-declined';
                break;
            case 'en attente':
                $badgeClass = 'badge-pending';
                break;
        }
        
        echo "<tr>";
        echo "<td>{$guest['id']}</td>";
        echo "<td>{$guest['nom_affiche']}</td>";
        echo "<td>{$guest['telephone']}</td>";
        echo "<td><code>{$guest['statut']}</code> (bytes: " . bin2hex($guest['statut']) . ")</td>";
        echo "<td><span class='badge $badgeClass'>{$guest['statut']}</span></td>";
        echo "<td>
                <form method='POST' style='display:inline;'>
                    <input type='hidden' name='test_id' value='{$guest['id']}'>
                    <select name='test_statut'>
                        <option value='confirmé'>Confirmé</option>
                        <option value='décliné'>Décliné</option>
                        <option value='en attente'>En attente</option>
                    </select>
                    <button type='submit' name='test_update'>Tester</button>
                </form>
              </td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Tester une mise à jour
if (isset($_POST['test_update'])) {
    echo "<h2>3️⃣ Test de mise à jour</h2>";
    
    $testId = intval($_POST['test_id']);
    $testStatut = $_POST['test_statut'];
    
    echo "<p><strong>Tentative de mise à jour :</strong></p>";
    echo "<ul>";
    echo "<li>ID : $testId</li>";
    echo "<li>Nouveau statut : $testStatut</li>";
    echo "<li>Bytes du statut : " . bin2hex($testStatut) . "</li>";
    echo "</ul>";
    
    if ($guestManager->updateStatus($testId, $testStatut)) {
        echo "<p class='success'>✅ Mise à jour réussie !</p>";
        
        // Vérifier que ça a bien été mis à jour
        $updated = $guestManager->getGuests();
        foreach ($updated as $g) {
            if ($g['id'] == $testId) {
                echo "<p>Statut actuel en base : <code>{$g['statut']}</code> (bytes: " . bin2hex($g['statut']) . ")</p>";
                if ($g['statut'] === $testStatut) {
                    echo "<p class='success'>✅ Vérification OK : le statut correspond</p>";
                } else {
                    echo "<p class='error'>❌ ERREUR : Le statut ne correspond pas !<br>";
                    echo "Attendu : '$testStatut' (bytes: " . bin2hex($testStatut) . ")<br>";
                    echo "Trouvé : '{$g['statut']}' (bytes: " . bin2hex($g['statut']) . ")</p>";
                }
                break;
            }
        }
        
        echo "<p><a href='debug_status.php'>🔄 Recharger la page</a></p>";
    } else {
        echo "<p class='error'>❌ Échec de la mise à jour</p>";
    }
}

// 4. Vérifier les valeurs ENUM possibles
echo "<h2>4️⃣ Valeurs ENUM dans la base de données</h2>";
$stmt = $db->query("SHOW COLUMNS FROM invites WHERE Field = 'statut'");
$column = $stmt->fetch();

if ($column) {
    echo "<p><strong>Type de colonne :</strong> <code>{$column['Type']}</code></p>";
    
    // Extraire les valeurs ENUM
    preg_match("/^enum\(\'(.*)\'\)$/", $column['Type'], $matches);
    if (isset($matches[1])) {
        $enumValues = explode("','", $matches[1]);
        echo "<p><strong>Valeurs autorisées :</strong></p>";
        echo "<ul>";
        foreach ($enumValues as $value) {
            echo "<li><code>$value</code> (bytes: " . bin2hex($value) . ")</li>";
        }
        echo "</ul>";
    }
}

// 5. Tester la fonction updateStatus directement
echo "<h2>5️⃣ Test de la méthode updateStatus()</h2>";
echo "<pre>";
echo "Code de la méthode :\n";
echo htmlspecialchars('
public function updateStatus($id, $statut) {
    $stmt = $this->db->prepare("UPDATE invites SET statut = ? WHERE id = ?");
    return $stmt->execute([$statut, $id]);
}
');
echo "</pre>";

// 6. Statistiques
echo "<h2>6️⃣ Statistiques</h2>";
$stats = $guestManager->getStats();
echo "<ul>";
echo "<li>Total : {$stats['total']}</li>";
echo "<li>Confirmés : {$stats['confirmes']}</li>";
echo "<li>Déclinés : {$stats['declines']}</li>";
echo "<li>En attente : {$stats['en_attente']}</li>";
echo "</ul>";

// 7. Requête SQL de test
echo "<h2>7️⃣ Test SQL direct</h2>";
echo "<p>Essayons une requête UPDATE directement :</p>";

if (isset($_GET['direct_test']) && !empty($guests)) {
    $firstGuest = $guests[0];
    $testId = $firstGuest['id'];
    $newStatus = 'confirmé';
    
    try {
        $stmt = $db->prepare("UPDATE invites SET statut = ? WHERE id = ?");
        $result = $stmt->execute([$newStatus, $testId]);
        
        if ($result) {
            echo "<p class='success'>✅ UPDATE direct réussi</p>";
            echo "<p>Requête : UPDATE invites SET statut = '$newStatus' WHERE id = $testId</p>";
            
            // Vérifier
            $stmt = $db->prepare("SELECT statut FROM invites WHERE id = ?");
            $stmt->execute([$testId]);
            $row = $stmt->fetch();
            echo "<p>Statut après UPDATE : <code>{$row['statut']}</code></p>";
        } else {
            echo "<p class='error'>❌ UPDATE direct échoué</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erreur : " . $e->getMessage() . "</p>";
    }
    
    echo "<p><a href='debug_status.php'>🔄 Recharger sans test</a></p>";
} else {
    echo "<p><a href='debug_status.php?direct_test=1'>▶️ Lancer le test SQL direct</a></p>";
}

// 8. Logs PHP
echo "<h2>8️⃣ Logs d'erreur PHP</h2>";
echo "<p><strong>Vérifiez les logs dans :</strong></p>";
echo "<ul>";
echo "<li><code>/var/log/apache2/error.log</code> (Apache)</li>";
echo "<li><code>/var/log/php/error.log</code> (PHP-FPM)</li>";
echo "<li>Ou via <code>error_log()</code> dans le code</li>";
echo "</ul>";

echo "<h2>9️⃣ Recommandations</h2>";
echo "<ol>";
echo "<li>Ouvrez la console JavaScript du navigateur (F12) et vérifiez les messages de debug</li>";
echo "<li>Testez une mise à jour depuis cette page avec le bouton 'Tester'</li>";
echo "<li>Vérifiez que les bytes des statuts correspondent exactement</li>";
echo "<li>Si ça fonctionne ici mais pas dans l'interface, le problème est dans le JavaScript</li>";
echo "</ol>";

echo "</body></html>";
?>