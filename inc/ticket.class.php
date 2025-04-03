<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

//------------------------------------------------------------------------------------------
class PluginWarrantycheckTicket extends CommonDBTM {

   public static $rightname = 'warrantycheck';
   public  static  $warrantycheck = 0 ;

//*--------------------------------------------------------------------------------------------- WARRANTYCHECK ONGLET
   static function getTypeName($nb = 0) { // voir doc glpi 
      if(Session::haveRight("plugin_warrantycheck", READ)){
         return _n('Garantie', 'Garantie', $nb, 'warrantycheck');
      }
   }
   
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) { // voir doc glpi 
         $nb = self::countForItem($item);
         switch ($item->getType()) {
            case 'Ticket' :
                  return self::createTabEntry(self::getTypeName($nb), $nb);
            default :
               return self::getTypeName($nb);
         }
         return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) { // voir doc glpi 
      switch ($item->getType()) {
         case 'Ticket' :
            self::showForTicket($item);
            break;
      }
      return true;
   }

   public static function countForItem(CommonDBTM $item) { 
      if(Session::haveRight("plugin_warrantycheck", READ)){
         return countElementsInTable('glpi_plugin_warrantycheck_surveys', ['tickets_id' => $item->getID()]);
      }
   }

   static function getAllForTicket($ID): array { // fonction qui va récupérer les informations sur le ticket 
      global $DB;

      $request = [
         'SELECT' => '*',
         'FROM'   => 'glpi_plugin_warrantycheck_surveys',
         'WHERE'  => [
            'tickets_id' => $ID,
         ],
         'ORDER'  => ['id DESC'],
      ];

      $vouchers = [];
      foreach ($DB->request($request) as $data) {
         $vouchers[$data['id']] = $data;
      }

      return $vouchers;
   }

   static function showForTicket(Ticket $ticket) { // formulaire sur le ticket
      global $DB, $CFG_GLPI;
     
      $ID = $ticket->getField('id'); // recupération de l'id ticket
      
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='noHover'>
              <th>Numéro de série</th>
              <th>Modèle</th>
              <th>Fabricant</th>
              <th>Date de début</th>
              <th>Date de fin</th>
              <th>Statut de garantie</th>
            </tr>";
      
      foreach (self::getAllForTicket($ID) as $data) {
          $serial = $data['serial_number'] ?? '';
          $model = $data['model'] ?? '';
          $fabricant = $data['fabricant'] ?? '';
          $start = $data['date_start'] ?? '';
          $end = $data['date_end'] ?? '';
      
          $now = time();
          $status = "Inconnu";
          $color = "lightgray";
      
          if (!empty($end)) {
              $timestamp_end = strtotime($end);
              if ($timestamp_end < $now) {
                  $status = "Expirée";
                  $color = "red";
              } else {
                  $status = "Active";
                  $color = "green";
              }
          }
      
          echo "<tr class='noHover'>
                  <td>$serial</td>
                  <td>$model</td>
                  <td>$fabricant</td>
                  <td>" . Html::convDate($start) . "</td>
                  <td>" . Html::convDate($end) . "</td>
                  <td><span style='color:white; background-color:$color; padding:2px 6px; border-radius:6px;'>$status</span></td>
               </tr>";
      }
      
      echo "</table>";
   }

   static function postShowItemNewTaskWARRANTYCHECK($params) {
      global $DB, $CFG_GLPI, $warrantycheck;
      $config = new PluginWarrantycheckConfig();
      $group   = new PluginWarrantycheckPreference();
      $result  = $group->find(['users_id' => Session::getLoginUserID()]);
      $VerifURL = substr($_GET['_target'], -15, null); // recupération URL
      $checkvalidate = $result[1]['checkvalidate'];
      $toastdelay = $result[1]['toastdelay'] * 1000;

      $entities_id = 0;
      $idticket = $_GET['id'];
      if($query = $DB->query("SELECT entities_id FROM `glpi_tickets` WHERE id = $idticket")->fetch_object()){
         $entities_id = $query->entities_id;
      }

      // Vérifier si l'URL contient 'id != 0'
      if ($VerifURL == 'ticket.form.php' && $_GET['id'] != 0) {
         if ($result[1]['warrantypopup'] == 1){
            if ($warrantycheck == 0){

               if ($result[1]['repeatpopup'] == 1){
                  $id = $_GET['id'] ?? null;
                  $now = time();
                  $expire_after = 900; // 15 minutes
                  
                  // Initialise le tableau si nécessaire
                  if (!isset($_SESSION['current_ticket_ids'])) {
                     $_SESSION['current_ticket_ids'] = [];
                  }
                  
                  // Nettoyage : supprimer les ID expirés
                  foreach ($_SESSION['current_ticket_ids'] as $stored_id => $timestamp) {
                     if ($now - $timestamp > $expire_after) {
                        unset($_SESSION['current_ticket_ids'][$stored_id]);
                     }
                  }
                  
                  // Traitement de l’ID courant
                  if ($id) {
                     if (isset($_SESSION['current_ticket_ids'][$id])) {
                        $norepeat = true;
                     } else {
                        $_SESSION['current_ticket_ids'][$id] = $now;
                        $norepeat = false;
                     }
                  }
               }else{
                  $norepeat = false;
               }

               if($norepeat == false){
                  ?>
                     <style>
                        .toast-container {
                           position: fixed;
                           bottom: 20px;
                           right: 20px;
                        }
                        .toast .toast-header {
                           display: flex;
                           justify-content: space-between;
                           align-items: center;
                        }
                        .toast .close {
                           background-color: transparent;
                           color: #000;
                           border: none;
                           font-size: 1.5rem;
                           line-height: 1;
                           opacity: 0.7;
                           margin-left: auto;
                        }
                        .toast .close:hover {
                           opacity: 1;
                        }
                     </style>

                     <div class="toast-container" style="display:none;">
                        <div id="myToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                           <div class="toast-header">
                              <strong class="mr-auto">Numéro de série détecté dans le ticket</strong>
                              <button type="button" class="close" aria-label="Close">
                                 <span aria-hidden="true">&times;</span>
                              </button>
                           </div>
                           <div class="toast-body" id="warranty_result">
                           </div>
                        </div>
                     </div>

                     <script>
                        const checkValidate = <?php echo ($checkvalidate == 0 ? 'true' : 'false'); ?>;
                        const toastDelay = <?php echo (isset($toastdelay) ? (int)$toastdelay : 30000); ?>;

                        $(document).ready(function(){
                           const ticketID = <?php echo json_encode($_GET['id']); ?>;
                           const entityID = <?php echo json_encode($entities_id); ?>;

                           $.ajax({
                              url: '<?php echo $CFG_GLPI["root_doc"]; ?>/plugins/warrantycheck/front/checkwarranty_ticket.php',
                              method: 'GET',
                              data: {
                                 ticket_id: ticketID,
                                 entities_id: entityID
                              },
                              success: function(data) {
                                 console.log("Réponse brute :", data);

                                 let html = "";

                                 if (data.length === 0) {
                                    if (!checkValidate) {
                                       html = "Aucun numéro de série trouvé.";
                                       $('#warranty_result').html(html);
                                       $('.toast-container').show();
                                       $('#myToast').toast({ delay: toastDelay }).toast('show');
                                    }
                                    // Si checkValidate = true ET aucun numéro → ne rien faire
                                    return;
                                 }

                                 // data.length > 0 : on affiche normalement
                                 data.forEach(entry => {
                                    let statusText = entry.warranty_status || null;
                                    let fabricant = entry.fabricant || null;
                                    let colorClass = 'badge bg-secondary';

                                    if (statusText && statusText.toLowerCase().includes('active')) {
                                       colorClass = 'badge bg-success';
                                    } else if (statusText && statusText.toLowerCase().includes('expired')) {
                                       colorClass = 'badge bg-danger';
                                    }

                                    const pluginUrl = '<?php echo $CFG_GLPI["root_doc"]; ?>/plugins/warrantycheck/front/generatecri.form.php';
                                    const serialLink = `<a href="${pluginUrl}?serial=${encodeURIComponent(entry.serial)}" target="_blank">${entry.serial}</a>`;

                                    html += `
                                       <div style="margin-bottom: 6px; padding-bottom: 6px; border-bottom: 1px solid #ccc;">
                                          <div><strong>Numéro de série :</strong> ${serialLink}</div>
                                          ${fabricant ? `<div><strong>Fabricant :</strong> ${fabricant}</div>` : ''}
                                          ${statusText ? `<div><strong>Statut de la garantie :</strong> <span class="${colorClass}">${statusText}</span></div>` : ''}
                                       </div>
                                    `;
                                 });

                                 $('#warranty_result').html(html);
                                 $('.toast-container').show();
                                 $('#myToast').toast({ delay: toastDelay }).toast('show');
                              },
                              error: function(err) {
                                 console.log("Réponse brute :", data);
                                 console.error("❌ Erreur AJAX :", textStatus, errorThrown);
                                 console.warn("Réponse brute :", jqXHR.responseText);

                                 $('#warranty_result').html("Erreur lors de la récupération des données de garantie.");
                                 $('.toast-container').show();
                                 $('#myToast').toast({ delay: toastDelay }).toast('show');
                              }
                           });

                           $('.toast .close').on('click', function() {
                              $('#myToast').toast('hide');
                           });
                        });
                     </script>
                  <?php
               }

               $warrantycheck++;
            }
         }
      }
   }
    
   static function install(Migration $migration) { // fonction intsllation de la table en BDD
      global $DB;

      $default_charset = DBConnection::getDefaultCharset();
      $default_collation = DBConnection::getDefaultCollation();
      $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

      $table = 'glpi_plugin_warrantycheck_surveys';

      if (!$DB->tableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int {$default_key_sign} NOT NULL auto_increment,
                     `tickets_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                     `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                     `serial_number` VARCHAR(255) NULL,
                     `date_start` TIMESTAMP NULL,
                     `date_end` TIMESTAMP NULL,
                     `model` VARCHAR(255) NULL,
                     `fabricant` VARCHAR(255) NULL,
                     PRIMARY KEY (`id`),
                     KEY `tickets_id` (`tickets_id`),
                     KEY `entities_id` (`entities_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
         $DB->query($query) or die($DB->error());
      }
   }

   static function uninstall(Migration $migration) {

      $table = 'glpi_plugin_warrantycheck_surveys';
      $migration->dropTable($table);
   }
}

