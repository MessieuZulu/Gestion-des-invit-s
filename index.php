<?php
session_start();
require_once 'config.php';
require_once 'includes/GuestManager.php';

$guestManager = new GuestManager();
$hasPassword = $guestManager->hasPassword();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Invités</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📋 Gestion des Invités</h1>
            <div class="header-actions">
                <button id="btnImport" class="btn btn-primary">📁 Charger une base de données</button>
                <button id="btnAdd" class="btn btn-success">➕ Ajouter un invité</button>
                <button id="btnExport" class="btn btn-info">📤 Exporter</button>
                <button id="btnReset" class="btn btn-danger">🔄 Réinitialiser</button>
            </div>
        </header>

        <!-- Zone des statistiques -->
        <div id="stats-container" class="stats-container">
            <div class="stat-card stat-total">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <div class="stat-label">Total</div>
                    <div class="stat-value" id="stat-total">0</div>
                </div>
            </div>
            <div class="stat-card stat-confirmed">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <div class="stat-label">Confirmés</div>
                    <div class="stat-value" id="stat-confirmed">0</div>
                </div>
            </div>
            <div class="stat-card stat-declined">
                <div class="stat-icon">❌</div>
                <div class="stat-content">
                    <div class="stat-label">Déclinés</div>
                    <div class="stat-value" id="stat-declined">0</div>
                </div>
            </div>
            <div class="stat-card stat-pending">
                <div class="stat-icon">⏳</div>
                <div class="stat-content">
                    <div class="stat-label">En attente</div>
                    <div class="stat-value" id="stat-pending">0</div>
                </div>
            </div>
        </div>

        <!-- Tableau des invités -->
        <div class="table-container">
            <table id="guestsTable" class="display">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Téléphone</th>
                        <th>Table</th>
                        <th>Envoi</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Modal Import -->
    <div id="modalImport" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>📁 Importer une base de données</h2>
            <form id="formImport" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Fichier (CSV ou XLSX)</label>
                    <input type="file" name="file" accept=".csv,.xlsx" required>
                </div>
                <?php if (!$hasPassword): ?>
                <div class="form-group">
                    <label>Définir un mot de passe</label>
                    <input type="password" name="password" required minlength="6" placeholder="Minimum 6 caractères">
                </div>
                <div class="form-group">
                    <label>Confirmer le mot de passe</label>
                    <input type="password" name="password_confirm" required minlength="6">
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" required>
                </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Importer</button>
            </form>
        </div>
    </div>

    <!-- Modal Ajout -->
    <div id="modalAdd" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>➕ Ajouter un invité</h2>
            <form id="formAdd">
                <div class="form-group">
                    <label>Nom *</label>
                    <input type="text" name="nom" required>
                </div>
                <div class="form-group">
                    <label>Téléphone *</label>
                    <input type="tel" name="telephone" required>
                </div>
                <div class="form-group">
                    <label>Numéro de table</label>
                    <input type="text" name="numero_table">
                </div>
                <div class="form-group">
                    <label>Numéro d'envoi</label>
                    <input type="text" name="numero_envoi">
                </div>
                <button type="submit" class="btn btn-success">Ajouter</button>
            </form>
        </div>
    </div>

    <!-- Modal Modifier Statut -->
    <div id="modalStatus" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>✏️ Modifier le statut</h2>
            <form id="formStatus">
                <input type="hidden" name="id" id="statusGuestId">
                <div class="form-group">
                    <label>Invité</label>
                    <input type="text" id="statusGuestName" readonly>
                </div>
                <div class="form-group">
                    <label>Statut</label>
                    <select name="statut" id="statusSelect" required>
                        <option value="confirmé">✅ Confirmé</option>
                        <option value="décliné">❌ Décliné</option>
                        <option value="en attente">⏳ En attente</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Mettre à jour</button>
            </form>
        </div>
    </div>

    <!-- Modal Export -->
    <div id="modalExport" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>📤 Exporter les données</h2>
            <form id="formExport">
                <div class="form-group">
                    <label>Filtrer par statut</label>
                    <select name="filter">
                        <option value="">Tous</option>
                        <option value="confirmé">Confirmés uniquement</option>
                        <option value="décliné">Déclinés uniquement</option>
                        <option value="en attente">En attente uniquement</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Format</label>
                    <select name="format">
                        <option value="csv">CSV</option>
                        <option value="xlsx">XLSX</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-info">Exporter</button>
            </form>
        </div>
    </div>

    <!-- Modal Réinitialisation -->
    <div id="modalReset" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>🔄 Réinitialiser la base de données</h2>
            <p class="warning">⚠️ Cette action supprimera TOUS les invités de la base de données. Cette action est irréversible !</p>
            <form id="formReset">
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-danger">Confirmer la réinitialisation</button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>