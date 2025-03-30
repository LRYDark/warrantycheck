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
            echo Html::input('Filtre_HP', ['value' => $config->Filtre_HP(), 'size' => 80]);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Lenovo", "gestion") . "</td><td>";
            echo Html::input('Filtre_Lenovo', ['value' => $config->Filtre_Lenovo(), 'size' => 80]);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Dell", "gestion") . "</td><td>";
            echo Html::input('Filtre_Dell', ['value' => $config->Filtre_Dell(), 'size' => 80]);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Dynabook", "gestion") . "</td><td>";
            echo Html::input('Filtre_Dynabook', ['value' => $config->Filtre_Dynabook(), 'size' => 80]);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Terra", "gestion") . "</td><td>";
            echo Html::input('Filtre_Terra', ['value' => $config->Filtre_Terra(), 'size' => 80]);// bouton configuration du bas de page line 1
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
   }   function Filtre_Terra()
   {
      return ($this->fields['Filtre_Terra']);
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
                  `Filtre_HP` TEXT NULL,
                  `Filtre_Lenovo` TEXT NULL,
                  `Filtre_Dell` TEXT NULL,
                  `Filtre_Dynabook` TEXT NULL,
                  `Filtre_Terra` TEXT NULL,
                  PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
         $DB->query($query) or die($DB->error());
         $config->add(['id' => 1,]);
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
