<?php
include('../../../inc/includes.php');
Session::checkLoginUser();

$PluginRpGenerateCri = new PluginRpGenerateCri();
$PluginRpCri         = new PluginRpCri();
$ticket              = new Ticket();
$UserID = Session::getLoginUserID();

//if (isset($_GET['generatecri'])) {

   global $CFG_GLPI;
   require_once 'warranty_functions.php';

   $Manufacturer = null;
   if (!empty($_GET["Manufacturer"])) {
      $candidate = (string)$_GET["Manufacturer"];
      $allowedManufacturers = ['HP', 'Dell', 'Lenovo', 'Terra', 'Dynabook'];
      if (in_array($candidate, $allowedManufacturers, true)) {
         $Manufacturer = $candidate;
      }
   }

   $serial = strtoupper(trim((string)($_GET['serial'] ?? '')));
   $serial = preg_replace('/[^A-Z0-9#\\-]/', '', $serial);
   if ($serial === '') {
      Session::addMessageAfterRedirect(__('Numéro de série invalide.', 'warrantycheck'), false, ERROR);
      Html::back();
   }
   $result = detectBrand($serial, $Manufacturer);
   
   $cle = uniqid('cri_', true);  // Clé unique
   $_SESSION['generatecri_cache'][$cle] = $result;

   $fabricant = (string)($_SESSION[$serial] ?? '');
   unset($_SESSION[$serial]);

   // Redirection vers front/generatecri.php avec la clé
   Html::redirect(
      'generatecri.php?cache_id=' . rawurlencode($cle)
      . '&fabricant=' . rawurlencode($fabricant)
      . '&serial=' . rawurlencode($serial)
   );
//}

if (Session::getCurrentInterface() == 'central') {
   Html::footer();
} else {
   Html::helpFooter();
}
