<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginWarrantycheckConfig extends CommonDBTM
{
   static private $_instance = null;

   function __construct()
   {
      global $DB;

      if ($DB->tableExists($this->getTable())) {
         $this->getFromDB(1);
      }
   }

   static function canCreate()
   {
      return Session::haveRight('config', UPDATE);
   }

   static function canView()
   {
      return Session::haveRight('config', READ);
   }

   static function canUpdate()
   {
      return Session::haveRight('config', UPDATE);
   }

   static function getTypeName($nb = 0)
   {
      return __("Warrantycheck", "warrantycheck");
   }

   static function getInstance()
   {
      if (!isset(self::$_instance)) {
         self::$_instance = new self();
         if (!self::$_instance->getFromDB(1)) {
            self::$_instance->getEmpty();
         }
      }
      return self::$_instance;
   }

   static function showConfigForm() //formulaire de configuration du plugin
   {
      $config = new self();
      $config->getFromDB(1);

      $config->showFormHeader(['colspan' => 4]);

      echo "<tr><th colspan='2'>" . __('Extraction des éléments liées', 'gestion') . "</th></tr>";
      echo "<tr class='tab_bg_1 top'><td>" . __('Recherche des Devis, Factures, Bon de commandes, Bon de livraisons', 'rp') . "</td>";
         echo "<td>";
         Dropdown::showYesNo("related_elements", $config->related_elements());
      echo "</td></tr>";
     
      // Dell
      echo "<tr><th colspan='2'>" . __('Dell', 'gestion') . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Client ID", "gestion") . "</td><td>";
            echo Html::input('ClientID_Dell', ['value' => $config->ClientID_Dell(), 'size' => 80]);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Client Secret", "gestion") . "</td><td>";
            echo Html::input('ClientSecret_Dell', ['value' => $config->ClientSecret_Dell(), 'size' => 80]);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      // HP
      echo "<tr><th colspan='2'>" . __('HP', 'gestion') . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Client ID", "gestion") . "</td><td>";
            echo Html::input('ClientID_HP', ['value' => $config->ClientID_HP(), 'size' => 80]);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Client Secret", "gestion") . "</td><td>";
            echo Html::input('ClientSecret_HP', ['value' => $config->ClientSecret_HP(), 'size' => 80]);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      // Filtre 
      echo "<tr><th colspan='2'>" . __('Filtres des numéros de série par Préfixes', 'gestion') . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre HP", "gestion") . "</td><td>";
            echo Html::input('Filtre_HP', ['value' => $config->Filtre_HP(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Lenovo", "gestion") . "</td><td>";
            echo Html::input('Filtre_Lenovo', ['value' => $config->Filtre_Lenovo(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Dell", "gestion") . "</td><td>";
            echo Html::input('Filtre_Dell', ['value' => $config->Filtre_Dell(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Dynabook", "gestion") . "</td><td>";
            echo Html::input('Filtre_Dynabook', ['value' => $config->Filtre_Dynabook(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Terra", "gestion") . "</td><td>";
            echo Html::input('Filtre_Terra', ['value' => $config->Filtre_Terra(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      // facture, devis, bon de livraison, bon de commande
      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Devis", "gestion") . "</td><td>";
            echo Html::input('Filtre_Devis', ['value' => $config->Filtre_Devis(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Facture", "gestion") . "</td><td>";
            echo Html::input('Filtre_Facture', ['value' => $config->Filtre_Facture(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Bon de livraison", "gestion") . "</td><td>";
            echo Html::input('Filtre_BonDeLivraison', ['value' => $config->Filtre_BonDeLivraison(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Bon de commande", "gestion") . "</td><td>";
            echo Html::input('Filtre_BonDeCommande', ['value' => $config->Filtre_BonDeCommande(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";
      //-------------------

      echo "<tr><th colspan='2'>" . __('Blacklist pour le fitre des numéros de série', 'gestion') . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Blacklist", "gestion") . "</td><td>";
            Html::textarea([
               'name'  => 'blacklist',
               'value' => $config->blacklist(),
               'rows'  => 10,
            ]);
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Blacklist Prefixes", "gestion") . "</td><td>";
            Html::textarea([
               'name'  => 'prefix_blacklist',
               'value' => $config->prefix_blacklist(),
               'rows'  => 2,
            ]);
         echo "</td>";
      echo "</tr>";

      echo "<tr><th colspan='2'>" . __('Droits des utilisateurs sur les filtres de blacklist et les filtres de numéros de service par préfixe', 'gestion') . "</th></tr>";
      echo "<tr class='tab_bg_1 top'><td>" . __('Droits des utilisateurs', 'rp') . "</td>";
      echo "<td>";
         echo '<div style="display: inline-block; margin-right: 30px;">';
         echo '<label for="whitelistuser_read">' . __('Lecture', 'rp') . '</label> ';
         Html::showCheckbox([
            'name'    => 'whitelistuser_read',
            'id'      => 'whitelistuser_read',
            'checked' => $config->whitelistuser_read(),
         ]);
         echo '</div>';
         
         echo '<div style="display: inline-block; margin-right: 30px;">';
         echo '<label for="whitelistuser_update">' . __('Mise à jour', 'rp') . '</label> ';
         Html::showCheckbox([
            'name'    => 'whitelistuser_update',
            'id'      => 'whitelistuser_update',
            'checked' => $config->whitelistuser_update(),
         ]);
         echo '</div>';
         
         echo '<div style="display: inline-block; margin-right: 30px;">';
         echo '<label for="whitelistuser_delete">' . __('Supprimer', 'rp') . '</label> ';
         Html::showCheckbox([
            'name'    => 'whitelistuser_delete',
            'id'      => 'whitelistuser_delete',
            'checked' => $config->whitelistuser_delete(),
         ]);
         echo '</div>';
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Gestion des numéros de série", "gestion") . "</td><td>";

         ?>
         <button id="openModalButton" type="button" class="btn btn-primary">Voir les numéros de série</button>

         <script type="text/javascript">

            $('#openModalButton').on('click', function() {
               $('#customModal').modal('show');
               loadWarrantyTickets();
            });

         function loadWarrantyTickets() {
            console.log("Chargement des tickets en cours...");
            $.ajax({
               url: '../plugins/warrantycheck/ajax/ajax_get_warranty_tickets.php',
               type: 'GET',
               success: function(data) {
                     console.log("Données reçues : ", data);
                     $('#warrantyTicketsBody').html(data);
               },
               error: function(xhr) {
                     console.error("Erreur Ajax : ", xhr.status, xhr.statusText);
               }
            });
         }

         function deleteWarrantyLine(id) {
            if (confirm("Confirmer la suppression ?")) {
               $.post('../plugins/warrantycheck/ajax/ajax_delete_warranty_ticket.php', { id: id }, function() {
                     loadWarrantyTickets();
               });
            }
         }

         $(document).on('input', '#searchWarrantyInput', function() {
            let value = $(this).val().toLowerCase();
            $("#warrantyTicketsBody tr").filter(function() {
               $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
         });

         </script>

         <?php

         // Modal HTML
         echo <<<HTML
         <div class="modal fade" id="customModal" tabindex="-1" aria-labelledby="AddGestionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
               <div class="modal-content">
                  <div class="modal-header">
                     <h5 class="modal-title" id="AddGestionModalLabel">Gestion des numéros de série</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                     <table class="table table-striped">
                        <thead>
                           <input type="text" id="searchWarrantyInput" class="form-control mb-3" placeholder="Rechercher...">
                           <tr>
                              <th>ID</th>
                              <th>Ticket ID</th>
                              <th>Serial Number</th>
                              <th>Action</th>
                           </tr>
                        </thead>
                        <tbody id="warrantyTicketsBody">
                           <!-- Données AJAX -->
                        </tbody>
                     </table>
                  </div>
                  <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                  </div>
               </div>
            </div>
         </div>
         HTML;

         echo "</td>";
      echo "</tr>";

      $config->showFormButtons(['candel' => false]);
      return false;
   }

   // Fonction pour charger la clé de cryptage à partir du fichier
   private function loadEncryptionKey() {
      // Chemin vers le fichier de clé de cryptage
      $file_path = GLPI_ROOT . '/config/glpicrypt.key';
      return file_get_contents($file_path);
   }

   // return fonction (retourn les values enregistrées en bdd)
   function Filtre_HP()
   {
      return ($this->fields['Filtre_HP']);
   }
   function Filtre_Lenovo()
   {
      return ($this->fields['Filtre_Lenovo']);
   }
   function Filtre_Dell()
   {
      return ($this->fields['Filtre_Dell']);
   }
   function Filtre_Dynabook()
   {
      return ($this->fields['Filtre_Dynabook']);
   }   
   function Filtre_Terra()
   {
      return ($this->fields['Filtre_Terra']);
   }
   function Filtre_Devis()
   {
      return ($this->fields['Filtre_Devis']);
   }
   function Filtre_Facture()
   {
      return ($this->fields['Filtre_Facture']);
   }
   function Filtre_BonDeLivraison()
   {
      return ($this->fields['Filtre_BonDeLivraison']);
   }
   function Filtre_BonDeCommande()
   {
      return ($this->fields['Filtre_BonDeCommande']);
   }
   function blacklist()
   {
      return ($this->fields['blacklist']);
   }
   function related_elements()
   {
      return ($this->fields['related_elements']);
   }
   function prefix_blacklist()
   {
      return ($this->fields['prefix_blacklist']);
   }
   function whitelistuser_read()
   {
      return ($this->fields['whitelistuser_read']);
   }
   function whitelistuser_delete()
   {
      return ($this->fields['whitelistuser_delete']);
   }
   function whitelistuser_update()
   {
      return ($this->fields['whitelistuser_update']);
   }
 
   function ClientID_Dell(){
      return openssl_decrypt(base64_decode($this->fields['ClientID_Dell']), 'aes-256-cbc', $this->loadEncryptionKey(), 0, '1234567890123456');   
   }
   function ClientSecret_Dell(){
      return openssl_decrypt(base64_decode($this->fields['ClientSecret_Dell']), 'aes-256-cbc', $this->loadEncryptionKey(), 0, '1234567890123456');   
   }
   function ClientID_HP(){
      return openssl_decrypt(base64_decode($this->fields['ClientID_HP']), 'aes-256-cbc', $this->loadEncryptionKey(), 0, '1234567890123456');   
   }
   function ClientSecret_HP(){
      return openssl_decrypt(base64_decode($this->fields['ClientSecret_HP']), 'aes-256-cbc', $this->loadEncryptionKey(), 0, '1234567890123456');   
   }
   // return fonction


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      if ($item->getType() == 'Config') {
         return __("Warrantycheck", "warrantycheck");
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      if ($item->getType() == 'Config') {
         self::showConfigForm();
      }
      return true;
   }
  
   static function install(Migration $migration)
   {
      global $DB;

      $default_charset = DBConnection::getDefaultCharset();
      $default_collation = DBConnection::getDefaultCollation();
      $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

      $table = self::getTable();
      $config = new self();

      if (!$DB->tableExists($table)) {

         $migration->displayMessage("Installing $table");

         $query = "CREATE TABLE IF NOT EXISTS $table (
                  `id` int {$default_key_sign} NOT NULL auto_increment,
                  `ClientID_Dell` TEXT NULL,
                  `ClientSecret_Dell` TEXT NULL,
                  `ClientID_HP` TEXT NULL,
                  `ClientSecret_HP` TEXT NULL,
                  `Filtre_HP` TEXT NULL DEFAULT '5CD,5CG,CZC,1H',
                  `Filtre_Lenovo` TEXT NULL DEFAULT 'MP,PF,PW',
                  `Filtre_Dell` TEXT NULL,
                  `Filtre_Dynabook` TEXT NULL DEFAULT '41',
                  `Filtre_Terra` TEXT NULL DEFAULT 'R',
                  `Filtre_Devis` TEXT NULL DEFAULT 'DE',
                  `Filtre_Facture` TEXT NULL DEFAULT 'FA',
                  `Filtre_BonDeLivraison` TEXT NULL DEFAULT 'BL',
                  `Filtre_BonDeCommande` TEXT NULL DEFAULT 'BC',
                  `related_elements` INT(10) NULL DEFAULT '1',
                  `whitelistuser_read` INT(10) NULL DEFAULT '1',
                  `whitelistuser_delete` INT(10) NULL DEFAULT '0',
                  `whitelistuser_update` INT(10) NULL DEFAULT '1',
                  `blacklist` MEDIUMTEXT NULL DEFAULT 'FR1009626,fr,FR,08H00,08H30,09H00,09H30,10H00,11H00,12H00,13H30,17H00,ABEND,ABTEILUNG,ACCUEIL,ADDRESS,ADMINISTRATION,ADRESSE,AFTERNOON,AGENT,ANFRAGE,ANNÉE,ANRUF,APPEL,APRÈS-MIDI,ASSISTANCE,AUSBILDUNG,AUSTAUSCH,AUTHORIZATION,AUTORISATION,Backup,Beispiel,Benutzer,Bonjour,Building,BÂTIMENT,CALL,CAS,CASE,CD54,CENTER,CENTRE,CHECK,CLIENT,COLLÈGE,COMMUNICATION,COMPUTER,CONFIGURATION,CONNECTION,CONNEXION,DANKE,DATA,DATEN,DAY,DAYS,DEMANDE,DEPARTEMENT54,DEPARTMENT,DIENST,DIRECTION,DONNÉES,DRUCKER,DSI,EBENE,EINSATZ,EMAIL,EMPFANG,ESCALIER,ETAGE,EVENING,EXAMPLE,EXEMPLE,FAILURE,FALL,FEHLER,FIRSTNAME,FLOOR,FOG-PRG-S110-00,FORMATION,GEBÄUDE,GENEHMIGUNG,GLPI,HALLO,HARDWARE,HELLO,HEURES,HILFE,HOURS,IMPRIMANTE,INCIDENT,INTERVENANT,INTERVENTION,ITIL,JAHR,JOUR,JOURS,KOMMUNIKATION,KONFIGURATION,KUNDE,LAPTOP,LASTNAME,LEVEL,LOGICIEL,LYCÉE,MACHINE,MAINTENANCE,MASCHINE,MATERIAL,MATIN,MATÉRIEL,MERCI,MITARBEITER,MODEL,MODELL,MODÈLE,MOIS,MONAT,MONTH,MORGEN,MORNING,NACHMITTAG,NACHNAME,NAME,NETWORK,NETZWERK,NIVEAU,NIVEAUX,NOM,NUMBER,NUMMER,NUMÉRISATION,NUMÉRO,ORDINATEUR,OXE-APP01,PANNE,PHONE,PLN-GNC-PORT-04,PORTABLE,PRINTER,PROBLEM,PROBLÈME,PRODUCT,PRODUIT,PRODUKT,PRÉNOM,RAUM,RDC-ADMIN02,RECEPTION,REFERENCE,REFERENZ,REMPLACEMENT,REPAIR,REPARATUR,REPLACEMENT,REQUEST,ROOM,RÉCEPTION,RÉFÉRENCE,RÉPARATION,RÉSEAU,SALLE,SAUVEGARDE,SCAN,SCHOOL,SCHULE,SEMAINE,SERVICE,SICHERUNG,SITE,SOFTWARE,SOIR,ST-ADMIN01,STAIRS,STANDORT,STUNDEN,SUPPORT,TAG,TAGE,TECHNICIAN,TECHNICIEN,TECHNIKER,TELECOM,TELEFON,TELEKOM,TEMPS,TEST,THANKS,TICKET,TIME,TRAINING,TREPPE,TÉLÉCOMS,TÉLÉPHONE,UNTERSTÜTZUNG,USER,UTILISATEUR,VERBINDUNG,VERWALTUNG,VORFALL,VORNAME,VÉRIFICATION,WARTUNG,WEEK,WOCHE,YEAR,ZEIT,ZENTRUM,ZONE,ÉTABLISSEMENT,ÉTAGE,ÜBERPRÜFUNG,überprüfung',
                  `prefix_blacklist` MEDIUMTEXT NULL DEFAULT 'KB,X8,0X,DE23,PRB,ERR,VER',
                  PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
         $DB->query($query) or die($DB->error());
         $config->add(['id' => 1,]);
      }else{
         if ($_SESSION['PLUGIN_WARRANTYCHECK_VERSION'] > '1.0.3'){
            // Vérifier si les colonnes existent déjà
            $columns = $DB->query("SHOW COLUMNS FROM `$table`")->fetch_all(MYSQLI_ASSOC);
   
            // Liste des colonnes à vérifier
            $required_columns = [
               'related_elements',
               'Filtre_Devis',
               'Filtre_Facture',
               'Filtre_BonDeLivraison',
               'Filtre_BonDeCommande',
               'whitelistuser_read',
               'whitelistuser_delete',
               'whitelistuser_update',
               'prefix_blacklist',
            ];
   
            // Liste pour les colonnes manquantes
            $missing_columns = array_diff($required_columns, array_column($columns, 'Field'));
   
            if (!empty($missing_columns)) {
               $query= "ALTER TABLE $table
                  ADD COLUMN `whitelistuser_read` INT(10) NULL DEFAULT '1',
                  ADD COLUMN `whitelistuser_delete` INT(10) NULL DEFAULT '0',
                  ADD COLUMN `whitelistuser_update` INT(10) NULL DEFAULT '1',
                  ADD COLUMN `prefix_blacklist` MEDIUMTEXT NULL DEFAULT 'KB,X8,0X,DE23,PRB,ERR,VER',
                  ADD COLUMN `related_elements` INT(10) NULL DEFAULT '1',
                  ADD COLUMN `Filtre_Devis` TEXT NULL DEFAULT 'DE',
                  ADD COLUMN `Filtre_Facture` TEXT NULL DEFAULT 'FA',
                  ADD COLUMN `Filtre_BonDeLivraison` TEXT NULL DEFAULT 'BL',
                  ADD COLUMN `Filtre_BonDeCommande` TEXT NULL DEFAULT 'BC';";
               $DB->query($query) or die($DB->error());
            }
         }
      }
   }

   static function uninstall(Migration $migration)
   {
      global $DB;

      $table = self::getTable();
      if ($DB->TableExists($table)) {
         $migration->displayMessage("Uninstalling $table");
         $migration->dropTable($table);
      }
      $table = 'glpi_plugin_warrantycheck';
      if ($DB->TableExists($table)) {
         $migration->displayMessage("Uninstalling $table");
         $migration->dropTable($table);
      }
   }
}
