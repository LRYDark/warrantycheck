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

   if (!empty($_GET["Manufacturer"])){
      $Manufacturer = $_GET["Manufacturer"];
   }else{
      $Manufacturer = NULL;
   }

   $serial = strtoupper(trim($_GET['serial']));
   $result = detectBrand($serial, $Manufacturer);
   
   $cle = uniqid('cri_', true);  // Clé unique
   $_SESSION['generatecri_cache'][$cle] = $result;

   $fabricant = $_SESSION[$serial];
   unset($_SESSION[$serial]);

   // Redirection vers front/generatecri.php avec la clé
   Html::redirect('generatecri.php?cache_id=' . $cle . '&fabricant=' . $fabricant . '&serial=' . $serial);
//}

if (Session::getCurrentInterface() == 'central') {
   Html::footer();
} else {
   Html::helpFooter();
}
