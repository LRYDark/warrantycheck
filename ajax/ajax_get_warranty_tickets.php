<?php
include ('../../../inc/includes.php');

global $DB;

$query = "SELECT id, tickets_id, serial_number FROM glpi_plugin_warrantycheck_tickets ORDER BY id ASC";
$result = $DB->doQuery($query);

if ($DB->numrows($result) > 0) {
    while ($data = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><input type='checkbox' class='warrantyCheckbox' value='".$data['id']."'></td>";
        echo "<td>".$data['id']."</td>";
        echo "<td>".$data['tickets_id']."</td>";
        echo "<td>".$data['serial_number']."</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='4' class='text-center'>Aucun numéro de série trouvé.</td></tr>";
}
?>
