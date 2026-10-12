<?php
Session::checkRight("profile", UPDATE);

$prof = new PluginWarrantycheckProfile();

//Save profile
if (isset ($_POST['update'])) {
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      Html::back();
   }
   $prof->update($_POST);
   Html::back();
}

?>
