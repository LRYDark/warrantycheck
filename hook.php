<?php

function plugin_warrantycheck_install() { // fonction installation du plugin
   global $DB;

   // requete de création des tables
   if (!$DB->TableExists("glpi_plugin_warrantycheck_preferences")) {
      $query = "CREATE TABLE `glpi_plugin_warrantycheck_preferences` (
         `id` int unsigned NOT NULL auto_increment,
         `users_id` int unsigned NOT NULL default '0',
         `statuswarranty` int NOT NULL DEFAULT '0',
         `maxserial` int NOT NULL DEFAULT '9999',
         `warrantypopup` int NULL,
         `repeatpopup` int NULL,
         `toastdelay` int NULL,
         `checkvalidate` int NULL,
         PRIMARY KEY  (`id`),
         KEY `users_id` (`users_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";

      $DB->query($query) or die("error creating glpi_plugin_warrantycheck_preferences " . $DB->error());
   }else{
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



