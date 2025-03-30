<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Warrantycheck plugin for GLPI
 Copyright (C) 2014-2022 by the Warrantycheck Development Team.

 https://github.com/InfotelGLPI/warrantycheck
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Warrantycheck.

 Warrantycheck is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Warrantycheck is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Warrantycheck. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * class plugin_warrantycheck_preference
 * Load and store the preference configuration from the database
 */
class PluginWarrantycheckPreference extends CommonDBTM {

   static function checkIfPreferenceExists($users_id) {
      global $DB;

      $result = $DB->query("SELECT `id`
                FROM `glpi_plugin_warrantycheck_preferences`
                WHERE `users_id` = '" . $users_id . "' ");
      if ($DB->numrows($result) > 0)
         return $DB->result($result, 0, "id");
      else
         return 0;
   }

   static function addDefaultPreference($users_id) {

      $self                                  = new self();
      $input["users_id"]                     = $users_id;
      $input["warrantypopup"]                = 0;
      $input["repeatpopup"]                  = 1;
      return $self->add($input);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() == 'Preference'
      && isset($_SESSION["glpiactiveprofile"]["interface"])
          && $_SESSION["glpiactiveprofile"]["interface"] != "helpdesk") {
         return __('Vérification de la garantie', 'warrantycheck');
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      global $CFG_GLPI;

      if (get_class($item) == 'Preference') {
         $pref_ID = self::checkIfPreferenceExists(Session::getLoginUserID());
         if (!$pref_ID)
            $pref_ID = self::addDefaultPreference(Session::getLoginUserID());

         self::showPreferencesForm(PLUGIN_WARRANTYCHECK_WEBDIR . "/front/preference.form.php", $pref_ID);
      }
      return true;
   }

   static function showPreferencesForm($target, $ID) {
      global $DB;

      $self = new self();
      $self->getFromDB($ID);
      
      echo "<form action='" . $target . "' method='post'>";
      echo "<div align='center'>";

      echo "<table class='tab_cadre_fixe' style='margin: 0; margin-top: 5px;'>\n";

      echo "<tr class='tab_bg_1 top'><td>" . __('Analyse de la garantie dans les tickets', 'rp') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("warrantypopup", $self->fields["warrantypopup"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1 top'><td>" . __('Répétition de la PopUp quand le ticket à déjà été ouvert', 'rp') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("repeatpopup", $self->fields["repeatpopup"]);
      echo "</td></tr>";


      echo "<tr class='tab_bg_1 center'><td colspan='2'>";
      echo Html::submit(_sx('button', 'Post'), ['name' => 'update_user_preferences_warrantycheck', 'class' => 'btn btn-primary']);
      echo Html::hidden('id', ['value' => $ID]);
      echo "</td></tr>";
      echo "</table>";

      echo "</div>";
      Html::closeForm();

   }
}
