<?php

$plugin = new Plugin();
if (!$plugin->isInstalled('warrantycheck') || !$plugin->isActivated('warrantycheck')) {
   throw new \Glpi\Exception\Http\NotFoundHttpException();
}

Session::checkRight('config', UPDATE);

$config = new PluginWarrantycheckConfig();

if (isset($_POST["update"])) {
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      Session::addMessageAfterRedirect(__('Méthode invalide', 'warrantycheck'), true, ERROR);
      Html::back();
   }
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

// Fichier charge depuis une methode de GLPI : $CFG_GLPI n'y est visible qu'avec « global ».
global $CFG_GLPI;
Html::redirect($CFG_GLPI["root_doc"] . "/front/config.form.php?forcetab=" . urlencode('PluginWarrantycheckConfig$1'));
