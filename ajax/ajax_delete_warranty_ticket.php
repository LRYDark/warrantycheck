<?php
include ('../../../inc/includes.php');

global $DB;

if (isset($_POST['id']) && is_numeric($_POST['id'])) {
    $id = (int)$_POST['id'];
    $DB->query("DELETE FROM glpi_plugin_warrantycheck_tickets WHERE id = $id");
}
?>
