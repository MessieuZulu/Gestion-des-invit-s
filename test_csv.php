<?php
/**
 * Script de diagnostic pour analyser la structure d'un fichier CSV
 * Permet d'identifier le délimiteur, l'encodage et les colonnes
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filepath = $file['tmp_name'];
        $content = file_get_contents($filepath);
        
        echo "<div style='font-family: monospace; padding: 20px; background: #f5f5f5;'>";
        echo "<h2>📊 Diagnostic du fichier CSV</h2>";
        
        // 1. Détecter l'encodage
        echo "<h3>1️⃣ Encodage détecté :</h3>";
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'UTF-16'], true);
        echo "<p><strong>$encoding</strong></p>";
        
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            echo "<p style='color: orange;'>⚠️ Fichier converti en UTF-8</p>";
        }
        
        // 2. Afficher les 3 premières lignes brutes
        echo "<h3>2️⃣ Premières lignes brutes (200 premiers caractères) :</h3>";
        $lines = explode("\n", $content);
        echo "<pre style='background: white; padding: 10px; border: 1px solid #ccc;'>";
        for ($i = 0; $i < min(3, count($lines)); $i++) {
            $line = $lines[$i];
            echo "Ligne " . ($i + 1) . " : " . htmlspecialchars(substr($line, 0, 200)) . "\n";
        }
        echo "</pre>";
        
        // 3. Tester différents délimiteurs
        echo "<h3>3️⃣ Test des délimiteurs :</h3>";
        $delimiters = [
            ',' => 'Virgule (,)',
            ';' => 'Point-virgule (;)',
            "\t" => 'Tabulation (\\t)',
            '|' => 'Pipe (|)'
        ];
        
        $bestDelimiter = ',';
        $maxColumns = 0;
        
        echo "<table border='1' cellpadding='5' style='background: white; border-collapse: collapse;'>";
        echo "<tr><th>Délimiteur</th><th>Nombre de colonnes</th><th>Recommandé</th></tr>";
        
        foreach ($delimiters as $delimiter => $name) {
            $testLine = $lines[0];
            $columns = str_getcsv($testLine, $delimiter);
            $count = count($columns);
            
            $recommended = '';
            if ($count > $maxColumns) {
                $maxColumns = $count;
                $bestDelimiter = $delimiter;
                $recommended = '✅ Recommandé';
            }
            
            echo "<tr>";
            echo "<td><strong>$name</strong></td>";
            echo "<td>$count colonnes</td>";
            echo "<td style='color: green;'>$recommended</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 4. Parser avec le meilleur délimiteur
        echo "<h3>4️⃣ Analyse avec le délimiteur recommandé : <strong>" . $delimiters[$bestDelimiter] . "</strong></h3>";
        
        $headerLine = array_shift($lines);
        $headers = str_getcsv($headerLine, $bestDelimiter);
        
        // Nettoyer les en-têtes
        $headers = array_map(function($h) {
            $h = trim($h);
            $h = str_replace("\xEF\xBB\xBF", '', $h); // BOM
            return $h;
        }, $headers);
        
        echo "<p><strong>En-têtes détectés (" . count($headers) . ") :</strong></p>";
        echo "<ol>";
        foreach ($headers as $index => $header) {
            $bytes = bin2hex($header);
            echo "<li><strong>" . htmlspecialchars($header) . "</strong> <small style='color: #666;'>(position: $index, bytes: $bytes)</small></li>";
        }
        echo "</ol>";
        
        // 5. Afficher les 3 premières lignes de données
        echo "<h3>5️⃣ Premières lignes de données (parsées) :</h3>";
        echo "<table border='1' cellpadding='5' style='background: white; border-collapse: collapse; font-size: 12px;'>";
        echo "<tr>";
        foreach ($headers as $header) {
            echo "<th>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr>";
        
        for ($i = 0; $i < min(3, count($lines)); $i++) {
            $line = $lines[$i];
            if (empty(trim($line))) continue;
            
            $row = str_getcsv($line, $bestDelimiter);
            
            echo "<tr>";
            foreach ($row as $cell) {
                echo "<td>" . htmlspecialchars($cell) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        // 6. Recommandations
        echo "<h3>6️⃣ Recommandations :</h3>";
        echo "<ul>";
        
        if ($maxColumns === 1) {
            echo "<li style='color: red;'>⚠️ <strong>ATTENTION :</strong> Une seule colonne détectée ! Le délimiteur n'est probablement pas correct.</li>";
            echo "<li>Vérifiez le format de votre fichier CSV. Il devrait y avoir plusieurs colonnes séparées par un délimiteur.</li>";
        } else {
            echo "<li style='color: green;'>✅ $maxColumns colonnes détectées avec le délimiteur : <strong>" . $delimiters[$bestDelimiter] . "</strong></li>";
        }
        
        // Vérifier si la colonne "Nom" existe
        $nomFound = false;
        foreach ($headers as $header) {
            if (stripos(strtolower($header), 'nom') !== false || 
                stripos(strtolower($header), 'name') !== false) {
                $nomFound = true;
                echo "<li style='color: green;'>✅ Colonne 'Nom' trouvée : <strong>" . htmlspecialchars($header) . "</strong></li>";
                break;
            }
        }
        
        if (!$nomFound) {
            echo "<li style='color: orange;'>⚠️ Aucune colonne 'Nom' explicite trouvée. La première colonne sera utilisée par défaut : <strong>" . htmlspecialchars($headers[0]) . "</strong></li>";
        }
        
        echo "</ul>";
        
        // 7. Code PHP suggéré
        echo "<h3>7️⃣ Code suggéré pour votre import.php :</h3>";
        echo "<pre style='background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px;'>";
        echo htmlspecialchars("// Utiliser ce délimiteur :
\$delimiter = " . var_export($bestDelimiter, true) . ";

// Parser le CSV
\$headers = str_getcsv(\$headerLine, \$delimiter);
while (\$line = fgets(\$handle)) {
    \$row = str_getcsv(\$line, \$delimiter);
    // ...
}");
        echo "</pre>";
        
        echo "</div>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test CSV - Diagnostic</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .upload-area {
            border: 3px dashed #667eea;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: #f8f9ff;
            transition: all 0.3s;
        }
        .upload-area:hover {
            background: #f0f2ff;
            border-color: #5568d3;
        }
        .upload-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        input[type="file"] {
            display: none;
        }
        .btn-upload {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-upload:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .file-info {
            margin-top: 20px;
            padding: 15px;
            background: #e8f5e9;
            border-radius: 8px;
            display: none;
        }
        .btn-analyze {
            background: #48bb78;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .btn-analyze:hover {
            background: #38a169;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(72, 187, 120, 0.4);
        }
        .info-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnostic de fichier CSV</h1>
        <p class="subtitle">Analysez votre fichier CSV pour identifier les problèmes de structure</p>
        
        <div class="info-box">
            <strong>ℹ️ Cet outil va vous aider à :</strong>
            <ul>
                <li>Détecter l'encodage de votre fichier</li>
                <li>Identifier le bon délimiteur (virgule, point-virgule, tabulation)</li>
                <li>Visualiser la structure des colonnes</li>
                <li>Comprendre pourquoi l'import échoue</li>
            </ul>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">📄</div>
                <p><strong>Glissez votre fichier CSV ici</strong></p>
                <p style="color: #666; margin: 10px 0;">ou</p>
                <label for="csv_file" class="btn-upload">
                    📁 Parcourir les fichiers
                </label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
            </div>
            
            <div class="file-info" id="fileInfo">
                <strong>✅ Fichier sélectionné :</strong>
                <p id="fileName"></p>
            </div>
            
            <center>
                <button type="submit" class="btn-analyze">🔍 Analyser le fichier</button>
            </center>
        </form>
    </div>
    
    <script>
        const fileInput = document.getElementById('csv_file');
        const uploadArea = document.getElementById('uploadArea');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        
        // Clic sur la zone de drop
        uploadArea.addEventListener('click', function(e) {
            if (e.target !== fileInput && !e.target.classList.contains('btn-upload')) {
                fileInput.click();
            }
        });
        
        // Sélection de fichier
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileName.textContent = this.files[0].name;
                fileInfo.style.display = 'block';
            }
        });
        
        // Drag & Drop
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#5568d3';
            this.style.background = '#e8f0ff';
        });
        
        uploadArea.addEventListener('dragleave', function() {
            this.style.borderColor = '#667eea';
            this.style.background = '#f8f9ff';
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#667eea';
            this.style.background = '#f8f9ff';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileName.textContent = files[0].name;
                fileInfo.style.display = 'block';
            }
        });
    </script>
</body>
</html>