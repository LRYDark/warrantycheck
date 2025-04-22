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
      $input["warrantypopup"]                = 1;
      $input["repeatpopup"]                  = 0;
      $input["checkvalidate"]                = 1;
      $input["statuswarranty"]               = 0;
      $input["toastdelay"]                   = 60;
      $input["maxserial"]                    = 9999;
      $input["viewdoc"]                      = 0;
      $input["positioning"]                  = 0;
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

      function generate_protected_field(string $name, string $value, int $update, int $delete, string $type = 'input') {
         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __($name, 'gestion') . "</td><td>";
      
         // 📘 Lecture seule = aucun droit
         if ($update === 0 && $delete === 0) {
            if ($type === 'textarea') {
               echo '<textarea name="' . $name . '" rows="2" readonly style="background-color:#f5f5f5; color:#555; width:100%; text-transform: uppercase;">' . htmlspecialchars($value) . '</textarea>';
            } else {
               echo '<input type="text" name="' . $name . '" value="' . htmlspecialchars($value) . '" readonly style="background-color:#f5f5f5; color:#555; width:100%; text-transform: uppercase;">';
            }
      
            echo "</td></tr>";
            return;
         }
      
         // 🟢 Champ modifiable avec protections JS
         if ($type === 'textarea') {
            echo '<textarea id="'.$name.'" name="'.$name.'" rows="2" style="width:100%; text-transform: uppercase;">' . htmlspecialchars($value) . '</textarea>';
         } else {
            echo Html::input($name, [
               'value' => $value,
               'id'    => $name,
               'size'  => 80,
               'style' => 'text-transform: uppercase;'
            ]);
         }
      
         echo "</td></tr>";
      
         // ⚙️ Protection JS dynamique
         echo "<script>
         (function() {
            const field = document.getElementById('".addslashes($name)."');
            const originalText = `".addslashes($value)."`;
            const originalLength = originalText.length;
            const update = $update;
            const del = $delete;
      
            function forceProtectedZone() {
               const current = field.value.toUpperCase();
               const before = current.substring(0, originalLength);
               const after = current.substring(originalLength);
               const cursor = field.selectionStart;
      
               if (del === 0 && before !== originalText) {
                  field.value = originalText + after;
                  field.setSelectionRange(Math.max(originalLength, cursor), Math.max(originalLength, cursor));
                  return;
               }
      
               if (update === 0 && after.length > 0) {
                  field.value = originalText;
                  field.setSelectionRange(originalLength, originalLength);
                  return;
               }
      
               field.value = before + after;
            }
      
            field.addEventListener('input', forceProtectedZone);
      
            field.addEventListener('paste', function(e) {
               const pos = field.selectionStart;
               if ((update === 0 && pos >= originalLength) || (del === 0 && pos < originalLength)) {
                  e.preventDefault();
               }
            });
      
            field.addEventListener('drop', function(e) {
               e.preventDefault();
            });
      
            if (update === 0) {
               field.addEventListener('focus', function() {
                  setTimeout(() => {
                     field.setSelectionRange(originalLength, originalLength);
                  }, 0);
               });
            }
         })();
         </script>";
      }
      
      $self = new self();
      $self->getFromDB($ID);
      $config = new PluginWarrantycheckConfig();     
     
      echo "<form action='" . $target . "' method='post'>";
      echo "<div align='center'>";

      echo "<table class='tab_cadre_fixe' style='margin: 0; margin-top: 5px;'>\n";

      // Générer les options du menu déroulant
      $positioning = [];
      $positioning[0] = "En bas à droite";
      $positioning[1] = "En bas à Gauche";
      $positioning[2] = "En haut à droite";
      $positioning[3] = "En haut à Gauche";
      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Positionnement de la fenêtre", "gestion") . "</td><td>";
            // Afficher le menu déroulant avec Dropdown::show()
            Dropdown::showFromArray(
               'positioning',  // Nom de l'identifiant du champ
               $positioning,    // Tableau des options
               [
                  'value'      => $self->fields["positioning"],        // Valeur sélectionnée par défaut (optionnel)
               ]
            );
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1 top'><td>" . __('Rechercher dans les tickets', 'rp') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("warrantypopup", $self->fields["warrantypopup"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1 top'><td>" . __('Ne pas réafficher la PopUP pendant 15 minutes après la première ouverture du ticket', 'rp') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("repeatpopup", $self->fields["repeatpopup"]);
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_1 top'><td>" . __('Afficher le message "Aucun numéro de série trouvé"', 'rp') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("checkvalidate", $self->fields["checkvalidate"]);
      echo "</td></tr>";

      if ($config->related_elements() == 1){
         echo "<tr class='tab_bg_1 top'><td>" . __('Afficher les Bon de commande, Bon de livraison, Facture et devis si détécté', 'rp') . "</td>";
         echo "<td>";
         Dropdown::showYesNo("viewdoc", $self->fields["viewdoc"]);
         echo "</td></tr>";
      }

      // Générer les options du menu déroulant
      $dropdownValues = [];
      for ($i = 5; $i <= 100; $i += 5) {
         $dropdownValues[$i] = $i; // La clé et la valeur sont identiques dans ce cas
      }
      // Ajouter l'option "Illimité" avec la valeur 9999
      $dropdownValues[9999] = "Illimité";
      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Nombre maximum d'éléments à afficher", "gestion") . "</td><td>";
            // Afficher le menu déroulant avec Dropdown::show()
            Dropdown::showFromArray(
               'maxserial',  // Nom de l'identifiant du champ
               $dropdownValues,    // Tableau des options
               [
                  'value'      => $self->fields["maxserial"],        // Valeur sélectionnée par défaut (optionnel)
               ]
            );
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1 top'><td>" . __('Afficher le statut de garantie dans la PopUp', 'rp') . "</td>";
      echo "<td>";
      
      Dropdown::showYesNo("statuswarranty", $self->fields["statuswarranty"]);
      echo "  (Si Oui, L’appel à l’API de garantie peut entraîner un très léger ralentissement dans les tickets)";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1 top'><td>" . __("Temps d'affichage de la PopUp", 'rp') . "</td>";
      echo "<td>";
         // Générer les libellés personnalisés pour chaque valeur
         $choices = [];

         // ➤ 10s à 60s → pas de 10s
         for ($i = 10; $i <= 60; $i += 10) {
            $choices[$i] = "$i secondes";
         }
         
         // ➤ 1 min (60s) à 10 min (600s) → pas de 1 min
         for ($i = 60; $i <= 600; $i += 60) {
            $min = $i / 60;
            $choices[$i] = "$min min";
         }
         
         // ➤ 20 min (1200s) à 120 min (7200s) → pas de 10 min
         for ($i = 1200; $i <= 7200; $i += 600) {
            $min = $i / 60;
            if ($min < 60) {
               $choices[$i] = "$min min";
            } else {
               $h = floor($min / 60);
               $rest = $min % 60;
               $label = "{$h} h";
               if ($rest > 0) $label .= " {$rest} min";
               $choices[$i] = $label;
            }
         }
         
         Dropdown::showFromArray('toastdelay', $choices, [
            'value' => $self->fields['toastdelay'],
            'width' => '30%'
         ]);

      echo "</td></tr>";

      if ($config->whitelistuser_read() == 1) {
         echo "<tr><th colspan='2'>" . __('Blacklist Prefixes pour le filtre des numéros de série', 'gestion') . "</th></tr>";
         generate_protected_field('Blacklist Prefixes', $config->prefix_blacklist(), $config->whitelistuser_update(), $config->whitelistuser_delete(), 'textarea');

         echo "<tr><th colspan='2'>" . __('Filtres des numéros de série par Préfixes', 'gestion') . "</th></tr>";
         generate_protected_field('Filtre HP',        $config->Filtre_HP(),         $config->whitelistuser_update(), $config->whitelistuser_delete());
         generate_protected_field('Filtre Lenovo',    $config->Filtre_Lenovo(),     $config->whitelistuser_update(), $config->whitelistuser_delete());
         generate_protected_field('Filtre Dell',      $config->Filtre_Dell(),       $config->whitelistuser_update(), $config->whitelistuser_delete());
         generate_protected_field('Filtre Dynabook',  $config->Filtre_Dynabook(),   $config->whitelistuser_update(), $config->whitelistuser_delete());
         generate_protected_field('Filtre Terra',     $config->Filtre_Terra(),      $config->whitelistuser_update(), $config->whitelistuser_delete());
      }

      echo "<tr class='tab_bg_1 center'><td colspan='2'>";
      echo "<br><br>";
      echo Html::submit(_sx('button', 'Post'), ['name' => 'update_user_preferences_warrantycheck', 'class' => 'btn btn-primary']);
      echo Html::hidden('id', ['value' => $ID]);
      echo "</td></tr>";
      echo "</table>";

      echo "</div>";
      Html::closeForm();
   }
}
