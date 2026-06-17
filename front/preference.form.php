<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Manageentities plugin for GLPI
 Copyright (C) 2014-2022 by the Manageentities Development Team.

 https://github.com/InfotelGLPI/manageentities
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Manageentities.

 Manageentities is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Manageentities is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Manageentities. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

include('../../../inc/includes.php');

Session::checkLoginUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
   Html::back();
}
if (!isset($_POST['update_user_preferences_warrantycheck'])) {
   Html::back();
}

function pluginWarrantycheckPrefCheckCSRF(array $data): void {
    if (!empty($data['plugin_warrantycheck_pref_csrf_token'])) {
        Session::checkCSRF(['_glpi_csrf_token' => (string)$data['plugin_warrantycheck_pref_csrf_token']], true);
        return;
    }
    Session::checkCSRF($data, true);
}
pluginWarrantycheckPrefCheckCSRF($_POST);

$pref   = new PluginWarrantycheckPreference();
$config = new PluginWarrantycheckConfig();

// Met à jour uniquement les préférences utilisateur attendues par ce formulaire.
$prefAllowed = [
   'id',
   'positioning',
   'warrantypopup',
   'repeatpopup',
   'checkvalidate',
   'viewdoc',
   'maxserial',
   'statuswarranty',
   'toastdelay'
];
$prefInput = array_intersect_key($_POST, array_flip($prefAllowed));
$prefId = (int)PluginWarrantycheckPreference::checkIfPreferenceExists(Session::getLoginUserID());
if ($prefId <= 0) {
   $prefId = (int)PluginWarrantycheckPreference::addDefaultPreference(Session::getLoginUserID());
}
$prefInput['id'] = $prefId;
$pref->update($prefInput);

// Mise à jour contrôlée de la config globale (uniquement les champs exposés dans l'onglet Préférences).
$configAllowed = [
   'prefix_blacklist',
   'Filtre_HP',
   'Filtre_Lenovo',
   'Filtre_Dell',
   'Filtre_Dynabook',
   'Filtre_Terra',
   'Filtre_IIyama',
   'Filtre_Autres'
];
$configInput = ['id' => 1];
foreach ($configAllowed as $field) {
   if (array_key_exists($field, $_POST)) {
      $configInput[$field] = $_POST[$field];
   }
}

$configOk = true;
if ((int)$config->whitelistuser_read() === 1
   && ((int)$config->whitelistuser_update() === 1 || (int)$config->whitelistuser_delete() === 1)
   && count($configInput) > 1) {
   $configOk = (bool)$config->update($configInput);
}

if (!$configOk) {
   Session::addMessageAfterRedirect(
      __('Erreur lors de la modification', 'warrantycheck'),
      true,
      ERROR
   );
} else {
   Session::addMessageAfterRedirect(
      __('Modification(s) effectuée(s)', 'warrantycheck'),
      true,
      INFO
   );
}
Html::back();
