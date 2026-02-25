<?php
define('GLPI_ROOT', '../../..');
include(GLPI_ROOT . "/inc/includes.php");

Session::checkRight("profile", "warrantycheck");

$prof = new PluginWarrantycheckProfile();

function pluginWarrantycheckProfileCheckCSRF(array $data): void {
    if (!empty($data['plugin_warrantycheck_profile_csrf_token'])) {
        Session::checkCSRF(['_glpi_csrf_token' => (string)$data['plugin_warrantycheck_profile_csrf_token']], true);
        return;
    }
    Session::checkCSRF($data, true);
}

//Save profile
if (isset ($_POST['update'])) {
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      Html::back();
   }
   pluginWarrantycheckProfileCheckCSRF($_POST);
   $prof->update($_POST);
   Html::back();
}

?>
