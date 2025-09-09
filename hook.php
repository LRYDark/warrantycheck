<?php

function plugin_warrantycheck_install() { // fonction installation du plugin
   global $DB;

   // requete de création des tables
   if (!$DB->TableExists("glpi_plugin_warrantycheck_preferences")) {
      $query = "CREATE TABLE `glpi_plugin_warrantycheck_preferences` (
         `id` int unsigned NOT NULL auto_increment,
         `users_id` int unsigned NOT NULL default '0',
         `statuswarranty` int NOT NULL DEFAULT '0',
         `viewdoc` int NOT NULL DEFAULT '0',
         `positioning` int NOT NULL DEFAULT '0',
         `maxserial` int NOT NULL DEFAULT '9999',
         `warrantypopup` int NULL,
         `repeatpopup` int NULL,
         `toastdelay` int NULL,
         `checkvalidate` int NULL,
         PRIMARY KEY  (`id`),
         KEY `users_id` (`users_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";

      $DB->query($query) or die("error creating glpi_plugin_warrantycheck_preferences " . $DB->error());
   }
      if ($_SESSION['PLUGIN_WARRANTYCHECK_VERSION'] > '1.0.1'){
         // Vérifier si les colonnes existent déjà
         $columns = $DB->query("SHOW COLUMNS FROM `glpi_plugin_warrantycheck_preferences`")->fetch_all(MYSQLI_ASSOC);

         // Liste des colonnes à vérifier
         $required_columns = [
            'statuswarranty'
         ];

         // Liste pour les colonnes manquantes
         $missing_columns = array_diff($required_columns, array_column($columns, 'Field'));

         if (!empty($missing_columns)) {
            $query= "ALTER TABLE glpi_plugin_warrantycheck_preferences
               ADD COLUMN `statuswarranty` INT(10) NOT NULL DEFAULT '0';";
            $DB->query($query) or die($DB->error());
         }
      }

      if ($_SESSION['PLUGIN_WARRANTYCHECK_VERSION'] > '1.0.2'){
         // Vérifier si les colonnes existent déjà
         $columns = $DB->query("SHOW COLUMNS FROM `glpi_plugin_warrantycheck_preferences`")->fetch_all(MYSQLI_ASSOC);

         // Liste des colonnes à vérifier
         $required_columns = [
            'maxserial'
         ];

         // Liste pour les colonnes manquantes
         $missing_columns = array_diff($required_columns, array_column($columns, 'Field'));

         if (!empty($missing_columns)) {
            $query= "ALTER TABLE glpi_plugin_warrantycheck_preferences
               ADD COLUMN `maxserial` INT(10) NOT NULL DEFAULT '9999';";
            $DB->query($query) or die($DB->error());
         }
      }

      if ($_SESSION['PLUGIN_WARRANTYCHECK_VERSION'] > '1.0.3'){
         // Vérifier si les colonnes existent déjà
         $columns = $DB->query("SHOW COLUMNS FROM `glpi_plugin_warrantycheck_preferences`")->fetch_all(MYSQLI_ASSOC);

         // Liste des colonnes à vérifier
         $required_columns = [
            'viewdoc',
            'positioning'
         ];

         // Liste pour les colonnes manquantes
         $missing_columns = array_diff($required_columns, array_column($columns, 'Field'));

         if (!empty($missing_columns)) {
            $query= "ALTER TABLE glpi_plugin_warrantycheck_preferences
               ADD COLUMN `viewdoc` INT(10) NOT NULL DEFAULT '0',
               ADD COLUMN `positioning` INT(10) NOT NULL DEFAULT '0';";
            $DB->query($query) or die($DB->error());
         }
      }

      if ($_SESSION['PLUGIN_WARRANTYCHECK_VERSION'] > '1.0.6'){
         // Vérifier si les colonnes existent déjà
         $columns = $DB->query("SHOW COLUMNS FROM `glpi_plugin_warrantycheck_preferences`")->fetch_all(MYSQLI_ASSOC);

         // Liste des colonnes à vérifier
         $required_columns = [
            'SageLocal'
         ];

         // Liste pour les colonnes manquantes
         $missing_columns = array_diff($required_columns, array_column($columns, 'Field'));

         if (!empty($missing_columns)) {
            $query= "ALTER TABLE glpi_plugin_warrantycheck_preferences
               ADD COLUMN `SageLocal` INT(10) NOT NULL DEFAULT '0';";
            $DB->query($query) or die($DB->error());
         }
      }

      // Exécuter seulement si version > 1.0.8
      if (!empty($_SESSION['PLUGIN_WARRANTYCHECK_VERSION'])
         && version_compare($_SESSION['PLUGIN_WARRANTYCHECK_VERSION'], '1.0.8', '>')
      ) {
         global $DB;

         // Déclare la fonction une seule fois
         if (!function_exists('ensureIndexExists')) {
            /**
             * Crée un index si manquant (MySQL/MariaDB) avec logs GLPI.
            * Utilise SHOW INDEX (pas d'accès $DB->dbh protégé).
            */
            function ensureIndexExists(DBmysql $DB, string $table, string $indexName, string $columnsSql, bool $unique = false): bool {
               try {
                  // 1) L'index existe déjà ?
                  $checkSql = "SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'";
                  $res = $DB->query($checkSql);
                  if ($res && $DB->numrows($res) > 0) {
                     return true;
                  }

                  // 2) Créer l'index
                  $type = $unique ? 'ADD UNIQUE INDEX' : 'ADD INDEX';
                  $createSql = "ALTER TABLE `{$table}` {$type} `{$indexName}` ({$columnsSql})";
                  if (!$DB->query($createSql)) {
                     throw new RuntimeException('ALTER TABLE failed: '.$DB->error());
                  }

                  if (class_exists('Toolbox')) {
                     Toolbox::logInFile('plugin_warrantycheck', "INFO: index `{$indexName}` créé sur {$table}".PHP_EOL);
                  }
                  return true;

               } catch (Throwable $e) {
                  if (class_exists('Toolbox')) {
                     Toolbox::logInFile('plugin_warrantycheck', "WARN: création index `{$indexName}` sur {$table} échouée: ".$e->getMessage().PHP_EOL);
                  }
                  return false;
               }
            }
         }

         // ---- Table de travail (adapte si besoin) ----
         $table = 'glpi_plugin_warrantycheck_tickets';

         // 1) Index sur tickets_id (filtrage par ticket)
         ensureIndexExists($DB, $table, 'idx_warranty_ticketid', '`tickets_id`', false);

         // 2) Index sur serial_number (LIKE prefix). Si utf8mb4, 191 évite "key too long".
         ensureIndexExists($DB, $table, 'idx_warranty_sn', '`serial_number`(191)', false);

         // 3) (Optionnel) Composite: WHERE tickets_id=? ORDER BY id DESC
         ensureIndexExists($DB, $table, 'idx_warranty_ticketid_id', '`tickets_id`, `id`', false);
      }

   $migration = new Migration(PLUGIN_WARRANTYCHECK_VERSION);

   // Parse inc directory
   foreach (glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
         $classname = 'PluginWarrantycheck' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if (method_exists($classname, 'install')) {
            $classname::install($migration);
         }
      }
   }
   $migration->executeMigration();

   PluginWarrantycheckProfile::initProfile();
   PluginWarrantycheckProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

   return true; 
}

function plugin_warrantycheck_uninstall() { // fonction desintallation du plugin
   global $DB;

   $rep_files_rp = GLPI_PLUGIN_DOC_DIR . "/warrantycheck";
   Toolbox::deleteDir($rep_files_rp);

   $migration = new Migration(PLUGIN_WARRANTYCHECK_VERSION);

   // Parse inc directory
   foreach (glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
         $classname = 'PluginWarrantycheck' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if (method_exists($classname, 'uninstall')) {
            $classname::uninstall($migration);
         }
      }
   }

   $migration->executeMigration();

      //Delete rights associated with the plugin
      $profileRight = new ProfileRight();
      foreach (PluginWarrantycheckProfile::getAllRights() as $right) {
         $profileRight->deleteByCriteria(['name' => $right['field']]);
      }
      PluginWarrantycheckProfile::removeRightsFromSession();

   //DELETE TABLE PREFERENCES
   $tables = array("glpi_plugin_warrantycheck_preferences");

   foreach ($tables as $table) {
      $DB->query("DROP TABLE IF EXISTS `$table`;");
   }
   
   return true;
}



