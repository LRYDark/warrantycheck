<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

//------------------------------------------------------------------------------------------
class PluginWarrantycheckTicket extends CommonDBTM {

   public static $rightname = 'plugin_warrantycheck';
   public  static  $warrantycheck = 0 ;

   static function getIcon() {
      return "far fa-check-circle";
   }

//*--------------------------------------------------------------------------------------------- WARRANTYCHECK ONGLET
   static function getTypeName($nb = 0) { // voir doc glpi 
      return _n('Garantie', 'Garantie', $nb, 'warrantycheck');
   }
   
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) { // voir doc glpi 
      if(Session::haveRight("plugin_warrantycheck", READ)){
         $nb = self::countForItem($item);
         switch ($item->getType()) {
            case 'Ticket' :
                  return self::createTabEntry(self::getTypeName($nb), $nb);
            default :
               return self::getTypeName($nb);
         }
         return '';
      }
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
      $value = (int)$value;

      $sql = "
         SELECT COUNT(*) AS total
         FROM `$table`
         WHERE `$column` = '$value'
            OR `$column` LIKE '$value,%'
            OR `$column` LIKE '%,$value'
            OR `$column` LIKE '%,{$value},%'
      ";
      $res = $DB->doQuery($sql);
      if ($res && $row = $res->fetch_assoc()) {
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
   
      $canedit = Session::haveRight(Entity::$rightname, UPDATE)
         || ($ticket->canEdit($ID) && !in_array($ticket->fields['status'], array_merge(Ticket::getSolvedStatusArray(), Ticket::getClosedStatusArray())));
   
      echo "<div class='spaced'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th colspan='2'>" . __('Informations de garantie', 'plugin_warrantycheck') . "</th></tr>";
      echo "</table></div>";
   
      if ($count > 0) {
         if (Session::haveRight('plugin_warrantycheck', PURGE) || Session::haveRight('plugin_warrantycheck', UPDATE)){
            if ($canedit) {
               echo Html::getOpenMassiveActionsForm('mass'.__CLASS__.$rand);
               if (Session::haveRight('plugin_warrantycheck', PURGE)){
                  $massiveactionparams = [
                     'num_displayed'    => $count,
                     'container'        => 'mass'.__CLASS__.$rand,
                     'rand'             => $rand,
                     'display'          => false,
                     'specific_actions' => [
                        'purge' => _x('button', 'Supprimer définitivement de GLPI')
                     ]
                  ];
               }
               if (Session::haveRight('plugin_warrantycheck', UPDATE)){
                  $massiveactionparams = [
                     'num_displayed'    => $count,
                     'container'        => 'mass'.__CLASS__.$rand,
                     'rand'             => $rand,
                     'display'          => false,
                     'specific_actions' => [
                        'update' => _x('button', 'Update')
                     ]
                  ];
               }
               if (Session::haveRight('plugin_warrantycheck', PURGE) && Session::haveRight('plugin_warrantycheck', UPDATE)){
                  $massiveactionparams = [
                     'num_displayed'    => $count,
                     'container'        => 'mass'.__CLASS__.$rand,
                     'rand'             => $rand,
                     'display'          => false,
                     'specific_actions' => [
                        'update' => _x('button', 'Update'),
                        'purge' => _x('button', 'Supprimer définitivement de GLPI')
                     ]
                  ];
               }
               echo Html::showMassiveActions($massiveactionparams);
            }
         }
   
         echo "<table class='tab_cadre_fixehov'>";
         $header = "<tr>";
   
         if (Session::haveRight('plugin_warrantycheck', PURGE) || Session::haveRight('plugin_warrantycheck', UPDATE)){
            if ($canedit) {
               $header .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand) . "</th>";
            }
         }
   
         $header .= "<th class='center'>" . __('Numéro de série') . "</th>";
         $header .= "<th class='center'>" . __('Modèle') . "</th>";
         $header .= "<th class='center'>" . __('Fabricant / Élément') . "</th>";
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
   
            if (Session::haveRight('plugin_warrantycheck', PURGE) || Session::haveRight('plugin_warrantycheck', UPDATE)){
               if ($canedit) {
                  echo "<td>" . Html::getMassiveActionCheckBox(__CLASS__, $id) . "</td>";
               }
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
   
         if (Session::haveRight('plugin_warrantycheck', PURGE) || Session::haveRight('plugin_warrantycheck', UPDATE)){
            if ($canedit) {
               $massiveactionparams['ontop'] = false;
               echo Html::showMassiveActions($massiveactionparams);
               echo Html::closeForm(false);
            }
         }
   
      } else {
         echo "<p class='center b'>" . __('Aucune garantie enregistrée pour ce ticket.', 'plugin_warrantycheck') . "</p>";
      }
   }
   
   static function postShowItemNewTaskWARRANTYCHECK($params) {
      global $DB, $CFG_GLPI, $warrantycheck;
      $config = new PluginWarrantycheckConfig();
      $userid = (int)Session::getLoginUserID();
      $prefId = (int)PluginWarrantycheckPreference::checkIfPreferenceExists($userid);
      if ($prefId <= 0) {
         $prefId = (int)PluginWarrantycheckPreference::addDefaultPreference($userid);
      }
      $pref = new PluginWarrantycheckPreference();
      $pref->getFromDB($prefId);
      $VerifURL = isset($_GET['_target']) ? basename($_GET['_target']) : '';

      $checkvalidate  = (int)($pref->fields['checkvalidate'] ?? 1);
      $statuswarranty = (int)($pref->fields['statuswarranty'] ?? 0);
      $toastdelay     = (int)($pref->fields['toastdelay'] ?? 60) * 1000;
      $ticketIdParam  = (int)($_GET['id'] ?? 0);

      $entities_id = 0;
      $idticket = $ticketIdParam;
      if($idticket){
         if($query = $DB->doQuery("SELECT entities_id FROM `glpi_tickets` WHERE id = $idticket")->fetch_object()){
            $entities_id = $query->entities_id;
         }
      }

      // Vérifier si l'URL contient 'id != 0'
      if ($VerifURL == 'ticket.form.php' && $ticketIdParam !== 0) {
         if ((int)($pref->fields['warrantypopup'] ?? 0) === 1){
            if ($warrantycheck == 0){

               if ((int)($pref->fields['repeatpopup'] ?? 0) === 1){
                  $id = $ticketIdParam ?: null;
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

                     <?php
                     // Position du toast
                     $toastPositionClass = match((int)($pref->fields['positioning'] ?? 0)) {
                        0 => 'bottom-0 end-0',   // Bas droite
                        1 => 'bottom-0 start-0', // Bas gauche
                        2 => 'top-0 end-0',      // Haut droite
                        3 => 'top-0 start-0',    // Haut gauche
                        default => 'bottom-0 end-0'
                     };
                     ?>

                     <div class="toast-container position-fixed <?= $toastPositionClass ?> p-3" id="warranty-toast-container" style="display:none;">
                     <div id="myToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header bg-dark text-white">
                           <strong class="me-auto">Données extraites du ticket</strong>
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>

                        <div class="toast-body">
                           <!-- Loader initial (sera retiré en success/error) -->
                           <div id="initial-loader" class="text-center">
                           <div class="spinner-border text-primary" role="status">
                              <span class="visually-hidden">Chargement...</span>
                           </div>
                           <div>Extraction des informations en cours...</div>
                           <?php if ($statuswarranty === 1){ ?>
                              <div>Chargement des données de garantie...</div>
                           <?php } ?>
                           </div>

                           <!-- Zone résultats (vide au départ) -->
                           <div id="warranty_result"></div>
                        </div>
                     </div>
                     </div>

                     <script>
                        if (window.top === window.self) {
                           const checkValidate = <?= ($checkvalidate == 0 ? 'true' : 'false'); ?>;
                           const toastDelay    = <?= isset($toastdelay) ? (int)$toastdelay : 30000; ?>;

                           $(function () {
                              const ticketID = <?= json_encode($ticketIdParam); ?>;
                              const esc = (v) => $('<div>').text(String(v ?? '')).html();

                              /* ----- Affiche immédiatement le toast + spinner ----- */
                              $('#warranty-toast-container').show();
                              $('#myToast').toast({ delay: toastDelay }).toast('show');

                              $.ajax({
                                 url: '<?= $CFG_GLPI["root_doc"]; ?>/plugins/warrantycheck/front/checkwarranty_ticket.php',
                                 method: 'GET',
                                 data: { ticket_id: ticketID },
                                 dataType: 'json',

                                 success: function (data) {
                                 /* ---------- Nettoie l’affichage initial ---------- */
                                 $('#initial-loader').remove();       // supprime spinner + textes
                                 $('#warranty_result').empty();       // nettoie la zone résultat

                                 /* ---------- Warnings (BL déjà attribué) ---------- */
                                 if (data && data.warnings && data.warnings.length) {
                                    $('#warranty_result').append(
                                       data.warnings.map(msg => `<div class="alert alert-warning p-2 mb-2">${esc(msg)}</div>`).join('')
                                    );
                                 }

                                 /* ---------- Résultats de garantie --------------- */
                                 if (data && data.resultats && data.resultats.length) {
                                    const pluginUrl = '<?= $CFG_GLPI["root_doc"]; ?>/plugins/warrantycheck/front/generatecri_loader.php';
                                    let html = '';

                                    data.resultats.forEach(entry => {
                                       const info       = entry.info            ?? '';
                                       const statusText = entry.warranty_status ?? '';
                                       const fabricant  = entry.fabricant       ?? '';
                                       const serial     = entry.serial          ?? '';
                                       let   colorClass = 'badge bg-secondary';

                                       if (statusText && statusText.toLowerCase().includes('active'))  colorClass = 'badge bg-success';
                                       if (statusText && statusText.toLowerCase().includes('expired')) colorClass = 'badge bg-danger';

                                       const serialLink = serial
                                       ? `<a href="${pluginUrl}?serial=${encodeURIComponent(serial)}" target="_blank">${esc(serial)}</a>`
                                       : '';

                                       html += `
                                       <div style="margin-bottom:6px;padding-bottom:6px;border-bottom:1px solid #ccc;">
                                          <div><strong>${esc(info)}</strong> ${serialLink}</div>
                                          ${fabricant  ? `<div><strong>Fabricant :</strong> ${esc(fabricant)}</div>` : ''}
                                          ${statusText ? `<div><strong>Statut de la garantie :</strong> <span class="${colorClass}">${esc(statusText)}</span></div>` : ''}
                                       </div>`;
                                    });

                                    $('#warranty_result').append(html);

                                 } else if (!checkValidate) {
                                    $('#warranty_result').text('Aucun numéro de série trouvé.');
                                 }

                                 /* ----- Ferme le toast après la durée configurée ----- */
                                 setTimeout(() => { $('#myToast').toast('hide'); }, toastDelay);
                                 },

                                 error: function (xhr) {
                                 $('#initial-loader').remove();
                                 $('#warranty_result').empty().text('Erreur lors de la récupération des données de garantie.');
                                 console.error('[WarrantyCheck] AJAX error :', xhr);
                                 console.error('[WarrantyCheck] Response text :', xhr.responseText);
                                 setTimeout(() => { $('#myToast').toast('hide'); }, toastDelay);
                                 }
                              });

                              /* -------- Cache le conteneur quand le toast est hidden -------- */
                              $('#myToast').on('hidden.bs.toast', () => {
                                 $('#warranty-toast-container').hide();
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

   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();
      $table = 'glpi_plugin_warrantycheck_tickets';

      $tab[] = [
         'id'       => 880,
         'table'    => $table,
         'field'    => 'serial_number',
         'name'     => __("Numéro de serie", 'warrantycheck'),
         'datatype' => 'string',
      ];

      $tab[] = [
         'id'       => 881,
         'table'    => $table,
         'field'    => 'date_start',
         'name'     => __("Date de debut de garantie", 'warrantycheck'),
         'datatype' => 'date',
      ];

      $tab[] = [
         'id'       => 882,
         'table'    => $table,
         'field'    => 'date_end',
         'name'     => __("Date de fin de garantie", 'warrantycheck'),
         'datatype' => 'date',
      ];

      $tab[] = [
         'id'       => 883,
         'table'    => $table,
         'field'    => 'date_start',
         'name'     => __("Date de debut de garantie", 'warrantycheck'),
         'datatype' => 'date',
      ];

      $tab[] = [
         'id'       => 884,
         'table'    => $table,
         'field'    => 'model',
         'name'     => __("Model", 'warrantycheck'),
         'datatype' => 'string',
      ];

      $tab[] = [
         'id'       => 885,
         'table'    => $table,
         'field'    => 'fabricant',
         'name'     => __("fabricant", 'warrantycheck'),
         'datatype' => 'string',
      ];
      
      return $tab;
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
         $DB->doQuery($query) or die($DB->error());
      }
   }

   static function uninstall(Migration $migration) {

      $table = 'glpi_plugin_warrantycheck_tickets';
      $migration->dropTable($table);
   }
}

