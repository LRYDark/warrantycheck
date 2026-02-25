<?php
include ('../../../inc/includes.php');

use Glpi\Event;

Session::checkLoginUser();
Session::checkRight("ticket", UPDATE);
global $DB, $CFG_GLPI;
$doc = new Document();

require_once PLUGIN_WARRANTYCHECK_DIR.'/front/SharePointGraph.php';
$sharepoint = new PluginWarrantycheckSharepoint();
$config = new PluginWarrantycheckConfig();

// Vérifier que le formulaire a été soumis
if (isset($_POST['save_selection']) && isset($_POST['tickets_id'])) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Html::back();
    }
    if (isset($_POST['_glpi_csrf_token'])) {
        Session::checkCSRF($_POST, true);
    }
    $ticketId = (int) $_POST['tickets_id'];
    if ($ticketId <= 0) {
        Html::back();
    }

    // Récupérer l'ID de l'entité associée au ticket
    $entityId = 0;
    $entityStmt = $DB->prepare("SELECT entities_id FROM glpi_tickets WHERE id = ? LIMIT 1");
    $entityStmt->execute([$ticketId]);
    if ($entityRow = $entityStmt->fetch(PDO::FETCH_ASSOC)) {
        $entityId = (int)($entityRow['entities_id'] ?? 0);
    }
    
    $selected_items = isset($_POST['groups_id']) && is_array($_POST['groups_id']) ? $_POST['groups_id'] : [];
    $selected_items = array_values(array_unique(array_filter(array_map(static function ($v) {
        $v = trim((string)$v);
        // conserve le format chemin/nom attendu, sans caractères de contrôle
        $v = preg_replace('/[\\x00-\\x1F\\x7F]/', '', $v);
        return mb_substr($v, 0, 1024, 'UTF-8');
    }, $selected_items), static fn($v) => $v !== '')));

    // Récupérer les éléments déjà en base
    $current_items = [];
    $currentStmt = $DB->prepare("SELECT url_bl, bl FROM glpi_plugin_warrantycheck_tickets WHERE tickets_id = ? AND signed = 0");
    $currentStmt->execute([$ticketId]);
    while ($data = $currentStmt->fetch(PDO::FETCH_ASSOC)) {
        $current_items[] = (string)($data['url_bl'] ?? '') . '/' . (string)($data['bl'] ?? '');
    }

    // Identifier les éléments à ajouter et à supprimer
    $items_to_add = array_diff($selected_items, $current_items);
    $items_to_remove = array_diff($current_items, $selected_items);

    // Initialiser le drapeau de succès
    $success = true;

    // Ajouter les nouveaux éléments
    foreach ($items_to_add as $item) {
        // Étape 3 : Spécifiez le chemin relatif du fichier dans SharePoint
        $file_path = $item . ".pdf"; // Remplacez par le chemin exact de votre fichier
        if ($sharepoint->checkFileExists($file_path)) {
            // Étape 4 : Récupérez l'URL du fichier (seulement si le fichier existe)
            $fileUrl = $sharepoint->getFileUrl($file_path);
            $tracker = $sharepoint->GetTrackerPdfDownload($file_path);
            $itemUrl = '';
            // Expression régulière pour extraire les deux parties
            $pattern = '#^(.*)/(.*)$#';

            // Vérification et extraction
            if (preg_match($pattern, $item, $matches)) {
                $itemUrl = $matches[1]; // xxx/zzzz ou xxx/xxxx/zzzz
                $item = $matches[2]; // zzzz
            }    
            
            $existingStmt = $DB->prepare("SELECT tickets_id, bl FROM `glpi_plugin_warrantycheck_tickets` WHERE bl = ? LIMIT 1");
            $existingStmt->execute([$item]);
            $existedoc = $existingStmt->fetch(PDO::FETCH_ASSOC) ?: null; // Récupérer les informations du document
            if (empty($existedoc['bl'])) {
                // Insérer le ticket et l'ID de document dans glpi_plugin_warrantycheck_tickets
                $insertStmt = $DB->prepare("INSERT INTO glpi_plugin_warrantycheck_tickets (tickets_id, entities_id, url_bl, bl, doc_url, tracker) VALUES (?, ?, ?, ?, ?, ?)");
                if (!$insertStmt->execute([$ticketId, $entityId, $itemUrl, $item, (string)$fileUrl, (string)$tracker])) {
                    Session::addMessageAfterRedirect(__("Erreur lors de l'ajout", 'warrantycheck'), false, ERROR);
                    $success = false; // Si l'insertion échoue, mettre le drapeau de succès à false
                }
            }else{
                if (($existedoc['tickets_id'] ?? null) == NULL){
                    // Validation des entrées numériques
                    $ticketId = intval($ticketId);

                    // Préparer la requête SQL
                    $sql = "UPDATE glpi_plugin_warrantycheck_tickets 
                            SET tickets_id = ?, 
                                url_bl = ?,
                                tracker = ?
                            WHERE bl = ?";

                    // Exécution de la requête préparée
                    $stmt = $DB->prepare($sql);
                    $stmt->execute([$ticketId, $itemUrl, $tracker, $item]);
                }elseif((int)$existedoc['tickets_id'] !== $ticketId){
                    Session::addMessageAfterRedirect(__($DB->escape($item)." déjà associé au ticket : ".$existedoc['tickets_id'], 'warrantycheck'), false, ERROR);
                    $success = false;
                }
            }

        } else {
            // Gérer le cas où le fichier n'existe pas
            Session::addMessageAfterRedirect(__("Le fichier $file_path n'existe pas.", 'warrantycheck'), false, ERROR);
            $success = false;
            $tracker = null;
        }

        if ($success) {
            if($config->ExtractYesNo() == 1){
                if (!empty($tracker)){
                    Session::addMessageAfterRedirect(__("$item - <strong>Tracker : $tracker</strong>", 'warrantycheck'), false, INFO);
                }else{
                    $tracker = NULL;
                    Session::addMessageAfterRedirect(__("$item - Aucun tracker", 'warrantycheck'), false, WARNING);
                }
            }        
        }
    }

    //$UserId = 1;
    // Supprimer les éléments désélectionnés
    foreach ($items_to_remove as $item) {
        // Normaliser les noms des fichiers dans $current_items
        $item = basename($item);
    
        // Validation des entrées numériques
        $ticketId = intval($ticketId);

        // Préparer la requête SQL
        $sql = "UPDATE glpi_plugin_warrantycheck_tickets 
                SET tickets_id = ?
                WHERE bl = ?";

        // Exécution de la requête préparée
        $stmt = $DB->prepare($sql);
        if (!$stmt->execute([0, $item])){
            Session::addMessageAfterRedirect(__("Erreur de suppression des éléments", 'warrantycheck'), true, ERROR);
        }else{
            //Event::log($UserId, "users", 5, "setup", sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
        }
    }

    // Message de confirmation si tout s'est bien passé
    if ($success) {
        //Event::log($UserId, "users", 5, "setup", sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
        Session::addMessageAfterRedirect(__("Les éléments ont été mis à jour avec succès.", 'warrantycheck'), true, INFO);
    }
}

Html::back();
