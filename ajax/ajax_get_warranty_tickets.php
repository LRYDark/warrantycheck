<?php
include ('../../../inc/includes.php');

global $DB;

$query = "SELECT id, tickets_id, serial_number FROM glpi_plugin_warrantycheck_tickets ORDER BY id ASC";
$result = $DB->query($query);

if ($DB->numrows($result) > 0) {
    while ($data = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>".$data['id']."</td>";
        echo "<td>".$data['tickets_id']."</td>";
        echo "<td>".$data['serial_number']."</td>";
        echo "<td><button type='button' class='btn btn-danger btn-sm' onclick='deleteWarrantyLine(".$data['id'].");'>Supprimer</button></td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='4' class='text-center'>Aucun numéro de série trouvé.</td></tr>";
}
?>
