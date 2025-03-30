<?php
include('../../../inc/includes.php');

Html::header_nocache();
Session::checkLoginUser();


switch ($_POST['action']) {//action bouton généré PDF formulaire ticket
   case 'showCriForm' :
      $PluginWarrantycheckCri = new PluginWarrantycheckCri();
      $params                  = $_POST["params"];
      $PluginWarrantycheckCri->showForm($params["job"], ['modal' => $_POST["modal"], 'root_modal' => $params["root_modal"]]);
      break;
}
