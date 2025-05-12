<?php
include ('../../../inc/includes.php');

global $DB;

// Suppression multiple
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
    $ids_list = implode(',', $ids);
    $DB->query("DELETE FROM glpi_plugin_warrantycheck_tickets WHERE id IN ($ids_list)");
}

// Suppression simple
if (isset($_POST['id']) && is_numeric($_POST['id'])) {
    $id = (int)$_POST['id'];
    $DB->query("DELETE FROM glpi_plugin_warrantycheck_tickets WHERE id = $id");
}
?>
