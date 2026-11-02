<?php
define('PLUGIN_WARRANTYCHECK_VERSION', '1.2.1'); // version du plugin
$_SESSION['PLUGIN_WARRANTYCHECK_VERSION'] = PLUGIN_WARRANTYCHECK_VERSION;

// Minimal GLPI version,
define("PLUGIN_WARRANTYCHECK_MIN_GLPI", "12.0.0");
// Maximum GLPI version,
define("PLUGIN_WARRANTYCHECK_MAX_GLPI", "12.0.99");

define("PLUGIN_WARRANTYCHECK_WEBDIR", ($GLOBALS['CFG_GLPI']['root_doc'] ?? '') . '/plugins/warrantycheck');
define("PLUGIN_WARRANTYCHECK_DIR", Plugin::getPhpDir("warrantycheck"));
define("PLUGIN_WARRANTYCHECK_NOTFULL_DIR", Plugin::getPhpDir("warrantycheck",false));
define("PLUGIN_WARRANTYCHECK_NOTFULL_WEBDIR", 'plugins/warrantycheck');

function plugin_init_warrantycheck() { // fonction glpi d'initialisation du plugin
   global $PLUGIN_HOOKS, $CFG_GLPI;

   $PLUGIN_HOOKS['change_profile']['warrantycheck'] = [PluginWarrantycheckProfile::class, 'initProfile'];
   // Secrets chiffrés avec la clé de GLPI : s'il change de clé, il les rechiffre lui-même.
   $PLUGIN_HOOKS['secured_fields']['warrantycheck'] = ['glpi_plugin_warrantycheck_configs.ClientID_Dell', 'glpi_plugin_warrantycheck_configs.ClientSecret_Dell', 'glpi_plugin_warrantycheck_configs.ClientID_HP', 'glpi_plugin_warrantycheck_configs.ClientSecret_HP'];

   $plugin = new Plugin();
   if ($plugin->isActivated('warrantycheck')){ // verification si le plugin warrantycheck est installé et activé

      $pref_ID = PluginWarrantycheckPreference::checkIfPreferenceExists(Session::getLoginUserID());
         if (!$pref_ID && Session::getLoginUserID() != 0)
            PluginWarrantycheckPreference::addDefaultPreference(Session::getLoginUserID());

      if (Session::getLoginUserID()) {
         Plugin::registerClass('PluginWarrantycheckProfile', ['addtabon' => 'Profile']);
      }

      if (Session::haveRight('plugin_warrantycheck_survey', READ)) {
         $PLUGIN_HOOKS["menu_toadd"]['warrantycheck'] = ['tools' => PluginWarrantycheckGenerateCRI::class];
      }

      Plugin::registerClass('PluginWarrantycheckTicket', ['addtabon' => 'Ticket']);

      $PLUGIN_HOOKS['config_page']['warrantycheck'] = "../../front/config.form.php?forcetab=" . urlencode('PluginWarrantycheckConfig$1'); // initialisation de la page config
      Plugin::registerClass('PluginWarrantycheckConfig', ['addtabon' => 'Config']); // ajout de la de la class config dans glpi

      if (Session::haveRight('plugin_warrantycheck', READ)) {
         $PLUGIN_HOOKS['pre_show_item']['warrantycheck'] = ['PluginWarrantycheckTicket', 'postShowItemNewTaskWARRANTYCHECK']; // initialisation de la class
         Plugin::registerClass('PluginWarrantycheckPreference',['addtabon' => 'Preference']);
      }
   }
}

function plugin_version_warrantycheck() { // fonction version du plugin (verification et affichage des infos de la version)
   return [
      'name'           => _n('Warrantycheck', 'Warrantycheck', 2, 'warrantycheck'),
      'version'        => PLUGIN_WARRANTYCHECK_VERSION,
      'author'         => 'REINERT Joris',
      'homepage'       => 'https://www.jcd-groupe.fr',
      'requirements'   => [
         'glpi' => [
            'min' => PLUGIN_WARRANTYCHECK_MIN_GLPI,
            'max' => PLUGIN_WARRANTYCHECK_MAX_GLPI,
         ]
      ]
   ];
}

/**
 * @return bool
 */
function plugin_warrantycheck_check_prerequisites() {
   return true;
}
