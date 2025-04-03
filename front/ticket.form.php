<?php
include ('../../../inc/includes.php');

use Glpi\Event;

Session::haveRight("ticket", UPDATE);
global $DB, $CFG_GLPI;
$doc = new Document();

require_once PLUGIN_WARRANTYCHECK_DIR.'/front/SharePointGraph.php';
$sharepoint = new PluginWarrantycheckSharepoint();
$config = new PluginWarrantycheckConfig();

// Vérifier que le formulaire a été soumis
if (isset($_POST['save_selection']) && isset($_POST['tickets_id'])) {
    $ticketId = (int) $_POST['tickets_id'];

    // Récupérer l'ID de l'entité associée au ticket
    $entityResult = $DB->query("SELECT entities_id FROM glpi_tickets WHERE id = $ticketId")->fetch_object();
    $entityId = $entityResult->entities_id;
    
    $selected_items = isset($_POST['groups_id']) ? $_POST['groups_id'] : [];

    // Récupérer les éléments déjà en base
    $current_items = [];
    $result = $DB->query("SELECT url_bl, bl FROM glpi_plugin_warrantycheck_tickets WHERE tickets_id = $ticketId AND signed = 0");
    while ($data = $result->fetch_assoc()) {
        $current_items[] = $data['url_bl'].'/'.$data['bl'];
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
        // Étape 4 : Récupérez l'URL du fichier
        $fileUrl = $sharepoint->getFileUrl($file_path);

        $tracker = $sharepoint->GetTrackerPdfDownload($file_path);
        if ($sharepoint->checkFileExists($file_path)) {
            // Expression régulière pour extraire les deux parties
            $pattern = '#^(.*)/(.*)$#';

            // Vérification et extraction
            if (preg_match($pattern, $item, $matches)) {
                $itemUrl = $matches[1]; // xxx/zzzz ou xxx/xxxx/zzzz
                $item = $matches[2]; // zzzz
            }    
            
            $existedoc = $DB->query("SELECT tickets_id, bl FROM `glpi_plugin_warrantycheck_tickets` WHERE bl = '".$DB->escape($item)."'")->fetch_object(); // Récupérer les informations du document
            if(empty($existedoc->bl)){
                // Insérer le ticket et l'ID de document dans glpi_plugin_warrantycheck_tickets
                if (!$DB->query("INSERT INTO glpi_plugin_warrantycheck_tickets (tickets_id, entities_id, url_bl, bl, doc_url, tracker) VALUES ($ticketId, $entityId, '".$DB->escape($itemUrl)."', '".$DB->escape($item)."', '$fileUrl', '$tracker')")) {
                    Session::addMessageAfterRedirect(__("Erreur lors de l'ajout", 'warrantycheck'), false, ERROR);
                    $success = false; // Si l'insertion échoue, mettre le drapeau de succès à false
                }
            }else{
                if($existedoc->tickets_id == NULL){
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
                }elseif($existedoc->tickets_id != $ticketId){
                    Session::addMessageAfterRedirect(__($DB->escape($item)." déjà associé au ticket : ".$existedoc->tickets_id, 'warrantycheck'), false, ERROR);
                    $success = false;
                }
            }

        } else {
            // Gérer le cas où le fichier n'existe pas
            Session::addMessageAfterRedirect(__("Le fichier $file_path n'existe pas.", 'warrantycheck'), false, ERROR);
            $success = false;
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