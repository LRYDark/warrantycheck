<?php
include('../../../inc/includes.php');

$plugin = new Plugin();
if (!$plugin->isInstalled('warrantycheck') || !$plugin->isActivated('warrantycheck')) {
   Html::displayNotFoundError();
}

Session::checkRight('config', UPDATE);

$config = new PluginWarrantycheckConfig();

function pluginWarrantycheckConfigCheckCSRF(array $data): void {
    if (!empty($data['plugin_warrantycheck_config_csrf_token'])) {
        Session::checkCSRF(['_glpi_csrf_token' => (string)$data['plugin_warrantycheck_config_csrf_token']], true);
        return;
    }
    Session::checkCSRF($data, true);
}

if (isset($_POST["update"])) {
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      Session::addMessageAfterRedirect(__('Méthode invalide', 'warrantycheck'), true, ERROR);
      Html::back();
   }
   pluginWarrantycheckConfigCheckCSRF($_POST);
   $encrypted_post = PluginWarrantycheckConfig::prepareConfigInputForSave($_POST);

   if(!$config->update($encrypted_post)){
      Session::addMessageAfterRedirect(
         __('Erreur lors de la modification', 'warrantycheck'),
         true,
         ERROR
      );
   }
   Html::back();
}

Html::redirect($CFG_GLPI["root_doc"] . "/front/config.form.php?forcetab=" . urlencode('PluginWarrantycheckConfig$1'));
