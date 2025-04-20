<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

//------------------------------------------------------------------------------------------
class PluginWarrantycheckTicket extends CommonDBTM {

   public static $rightname = 'plugin_warrantycheck';
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

   public static function countTicketsIDMatch($table, $column, $value) {
      global $DB;

      $sql = "
         SELECT COUNT(*) AS total
         FROM `$table`
         WHERE `$column` = '$value'
            OR `$column` LIKE '$value,%'
            OR `$column` LIKE '%,$value'
            OR `$column` LIKE '%,{$value},%'
      ";
      $res = $DB->query($sql);
      if ($row = $DB->fetchassoc($res)) {
         return (int)$row['total'];
      }
      return 0;
   }

   public static function countForItem(CommonDBTM $item) { 
      if(Session::haveRight("plugin_warrantycheck", READ)){
         $count = self::countTicketsIDMatch('glpi_plugin_warrantycheck_tickets', 'tickets_id', $item->getID());
         return $count;
      }
   }

   static function getAllForTicket($ID): array { // fonction qui va récupérer les informations sur le ticket 
      global $DB;

      $request = [
         'SELECT' => '*',
         'FROM'   => 'glpi_plugin_warrantycheck_tickets',
         'WHERE'  => [
            'OR' => [
               ['tickets_id' => $ID],                             // cas: "3"
               ['tickets_id' => ['LIKE', "$ID,%"]],               // cas: "3,4,5"
               ['tickets_id' => ['LIKE', "%,$ID"]],               // cas: "1,2,3"
               ['tickets_id' => ['LIKE', "%,$ID,%"]],             // cas: "1,2,3,4"
            ]
         ],
         'ORDER'  => ['id DESC'],
      ];    

      $vouchers = [];
      foreach ($DB->request($request) as $data) {
         $vouchers[$data['id']] = $data;
      }

      return $vouchers;
   }

   static function showForTicket(Ticket $ticket) {
      global $DB;
   
      $ID = $ticket->getID();
      $warranties = self::getAllForTicket($ID);
      $count = count($warranties);
      $rand = mt_rand();
   
      if (!$ticket->can($ID, READ)) return false;

      if (!Session::haveRight(Entity::$rightname, READ)) return false;
   
      $canedit = Session::haveRight(Entity::$rightname, PURGE)
         || ($ticket->canEdit($ID) && !in_array($ticket->fields['status'], array_merge(Ticket::getSolvedStatusArray(), Ticket::getClosedStatusArray())));
   
      echo "<div class='spaced'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th colspan='2'>" . __('Informations de garantie', 'plugin_warrantycheck') . "</th></tr>";
      echo "</table></div>";
   
      if ($count > 0) {
         if ($canedit) {
            echo Html::getOpenMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = [
               'num_displayed'    => $count,
               'container'        => 'mass'.__CLASS__.$rand,
               'rand'             => $rand,
               'display'          => false,
               'specific_actions' => [
                  'purge' => _x('button', 'Supprimer définitivement de GLPI')
               ]
            ];
            echo Html::showMassiveActions($massiveactionparams);
         }
   
         echo "<table class='tab_cadre_fixehov'>";
         $header = "<tr>";
   
         if ($canedit) {
            $header .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand) . "</th>";
         }
   
         $header .= "<th class='center'>" . __('Numéro de série') . "</th>";
         $header .= "<th class='center'>" . __('Modèle') . "</th>";
         $header .= "<th class='center'>" . __('Fabricant') . "</th>";
         $header .= "<th class='center'>" . __('Date de début') . "</th>";
         $header .= "<th class='center'>" . __('Date de fin') . "</th>";
         $header .= "<th class='center'>" . __('Statut de garantie') . "</th>";
         $header .= "</tr>";
   
         echo $header;
   
         foreach ($warranties as $data) {
            $id         = $data['id'];
            $serial     = $data['serial_number'] ?? '';
            $model      = $data['model'] ?? '';
            $fabricant  = $data['fabricant'] ?? '';
            $start      = Html::convDate($data['date_start'] ?? '');
            $end        = Html::convDate($data['date_end'] ?? '');
   
            $status = "Inconnu";
            $color  = "lightgray";
   
            if (!empty($data['date_end'])) {
               $timestamp_end = strtotime($data['date_end']);
               if ($timestamp_end < time()) {
                  $status = "Expirée";
                  $color  = "red";
               } else {
                  $status = "Active";
                  $color  = "green";
               }
            }
   
            echo "<tr class='tab_bg_2'>";
   
            if ($canedit) {
               echo "<td>" . Html::getMassiveActionCheckBox(__CLASS__, $id) . "</td>";
            }
   
            echo "<td class='center'>$serial</td>";
            echo "<td class='center'>$model</td>";
            echo "<td class='center'>$fabricant</td>";
            echo "<td class='center'>$start</td>";
            echo "<td class='center'>$end</td>";
            echo "<td class='center'><span style='color:white; background:$color; padding:2px 6px; border-radius:6px;'>$status</span></td>";
            echo "</tr>";
         }
   
         echo "</table>";
   
         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            echo Html::showMassiveActions($massiveactionparams);
            echo Html::closeForm(false);
         }
   
      } else {
         echo "<p class='center b'>" . __('Aucune garantie enregistrée pour ce ticket.', 'plugin_warrantycheck') . "</p>";
      }
   }
   
   static function postShowItemNewTaskWARRANTYCHECK($params) {
      global $DB, $CFG_GLPI, $warrantycheck;
      $config = new PluginWarrantycheckConfig();
      $userid = Session::getLoginUserID();
      $result = $DB->query("SELECT * FROM `glpi_plugin_warrantycheck_preferences` WHERE users_id = $userid")->fetch_object();
      $VerifURL = isset($_GET['_target']) ? basename($_GET['_target']) : '';

      $checkvalidate = $result->checkvalidate;
      $statuswarranty = $result->statuswarranty;
      $toastdelay = $result->toastdelay * 1000;

      $entities_id = 0;
      $idticket = $_GET['id'];
      if($idticket){
         if($query = $DB->query("SELECT entities_id FROM `glpi_tickets` WHERE id = $idticket")->fetch_object()){
            $entities_id = $query->entities_id;
         }
      }

      // Vérifier si l'URL contient 'id != 0'
      if ($VerifURL == 'ticket.form.php' && $_GET['id'] != 0) {
         if ($result->warrantypopup == 1){
            if ($warrantycheck == 0){

               if ($result->repeatpopup == 1){
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
                           overflow-y: auto; /* Ajoute un ascenseur si nécessaire */
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
                        .toast-body {
                           max-height: 300px; /* Limite la hauteur maximale du corps du toast */
                           overflow-y: auto; /* Ajoute un ascenseur si nécessaire */
                        }
                     </style>

                     <div class="toast-container" id="warranty-toast-container" style="display:none;">
                        <div id="myToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                           <div class="toast-header bg-dark text-white">
                              <strong class="mr-auto">Numéros de série détecté dans le ticket</strong>
                                 <button type="button" class="close" aria-label="Close">
                                    <span aria-hidden="true" class="text-white">&times;</span>
                                 </button>
                           </div>
                           <div class="toast-body" id="warranty_result">
                              <div class="text-center">
                                 <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                 </div>                           
                                 <div>Recheche des numéros de série...</div>
                                 <?php if ($statuswarranty === 1){ ?>
                                    <div>Chargement des données de garantie...</div>
                                 <?php } ?>
                              </div>
                           </div>
                        </div>
                     </div>

                     <script>
                        if (window.top === window.self) {
                           const checkValidate = <?php echo ($checkvalidate == 0 ? 'true' : 'false'); ?>;
                           const toastDelay = <?php echo (isset($toastdelay) ? (int)$toastdelay : 30000); ?>;

                           $(document).ready(function(){
                              const ticketID = <?php echo json_encode($_GET['id']); ?>;
                              const entityID = <?php echo json_encode($entities_id); ?>;

                              // Affiche immédiatement le toast avec le spinner
                              $('#warranty-toast-container').show();
                              $('#myToast').toast({ delay: toastDelay }).toast('show');

                              $.ajax({
                                 url: '<?php echo $CFG_GLPI["root_doc"]; ?>/plugins/warrantycheck/front/checkwarranty_ticket.php',
                                 method: 'GET',
                                 data: {ticket_id: ticketID},
                                 dataType: 'json', // JSON
                                 success: function(data) {
                                    let html = "";

                                    if (data.length === 0) {
                                       if (!checkValidate) {
                                          html = "Aucun numéro de série trouvé.";
                                          $('#warranty_result').html(html);
                                       }else {
                                          setTimeout(() => {
                                             $('#myToast').toast('hide');
                                             $('#warranty-toast-container').hide();
                                          }, 2000);
                                       }
                                       return;
                                    }

                                    data.forEach(entry => {
                                       let info = entry.info || null;
                                       let statusText = entry.warranty_status || null;
                                       let fabricant = entry.fabricant || null;
                                       let colorClass = 'badge bg-secondary';

                                       if (statusText && statusText.toLowerCase().includes('active')) {
                                          colorClass = 'badge bg-success';
                                       } else if (statusText && statusText.toLowerCase().includes('expired')) {
                                          colorClass = 'badge bg-danger';
                                       }

                                       const pluginUrl = '<?php echo $CFG_GLPI["root_doc"]; ?>/plugins/warrantycheck/front/generatecri_loader.php';
                                       const serialLink = `<a href="${pluginUrl}?serial=${encodeURIComponent(entry.serial)}" target="_blank">${entry.serial}</a>`;

                                       html += `
                                          <div style="margin-bottom: 6px; padding-bottom: 6px; border-bottom: 1px solid #ccc;">
                                             <div><strong>${info}</strong> ${serialLink}</div>
                                             ${fabricant ? `<div><strong>Fabricant :</strong> ${fabricant}</div>` : ''}
                                             ${statusText ? `<div><strong>Statut de la garantie :</strong> <span class="${colorClass}">${statusText}</span></div>` : ''}
                                          </div>
                                       `;
                                    });

                                    $('#warranty_result').html(html);
                                 },
                                 error: function(err) {
                                    console.error("Erreur AJAX garantie :", err);
                                    $('#warranty_result').html("Erreur lors de la récupération des données de garantie.");
                                 }
                              });

                              $('.toast .close').on('click', function() {
                                 $('#myToast').toast('hide');
                              });
                           });
                        }
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

      $table = 'glpi_plugin_warrantycheck_tickets';

      if (!$DB->tableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int {$default_key_sign} NOT NULL auto_increment,
                     `tickets_id` VARCHAR(255) NOT NULL DEFAULT '0',
                     `serial_number` VARCHAR(255) NULL,
                     `date_start` TIMESTAMP NULL,
                     `date_end` TIMESTAMP NULL,
                     `model` VARCHAR(255) NULL,
                     `fabricant` VARCHAR(255) NULL,
                     PRIMARY KEY (`id`),
                     KEY `tickets_id` (`tickets_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
         $DB->query($query) or die($DB->error());
      }
   }

   static function uninstall(Migration $migration) {

      $table = 'glpi_plugin_warrantycheck_tickets';
      $migration->dropTable($table);
   }
}

