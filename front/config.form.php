<?php
include('../../../inc/includes.php');

$plugin = new Plugin();
if (!$plugin->isInstalled('warrantycheck') || !$plugin->isActivated('warrantycheck')) {
   Html::displayNotFoundError();
}

Session::checkRight('config', UPDATE);

$config = new PluginWarrantycheckConfig();

// Fonction pour charger la clé de cryptage à partir du fichier
function loadEncryptionKey() {
   // Chemin vers le fichier de clé de cryptage
   $file_path = GLPI_ROOT . '/config/glpicrypt.key';
   return file_get_contents($file_path);
}

function encryptData($data) {
   // Chargez la clé de cryptage
   $encryption_key = loadEncryptionKey();
   return base64_encode(openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, '1234567890123456'));
}

function encryptArray($array) {
   $include_keys = ['ClientID_Dell', 'ClientSecret_Dell', 'ClientID_HP', 'ClientSecret_HP'];
   $encrypted_array = [];

   foreach ($array as $key => $value) {
       // Crypter uniquement les clés définies dans $include_keys
       if (in_array($key, $include_keys) && !empty($value)) {
           $encrypted_array[$key] = encryptData($value);
       } else {
           $encrypted_array[$key] = $value;
       }
   }
   return $encrypted_array;
}

if (isset($_POST["update"])) {
   $encrypted_post = encryptArray($_POST);

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
