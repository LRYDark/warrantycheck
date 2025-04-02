<?php
//include('../../../inc/includes.php');

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginWarrantycheckGenerateCRI
 */
class PluginWarrantycheckGenerateCRI extends CommonGLPI {

   static $rightname = "ticket";
   /**
    * @param int $nb
    *
    * @return string|\translated
    * @see CommonDBTM::getTypeName($nb)
    *
    */
   static function getMenuName($nb = 0) {
      return __('Vérification de garantie', 'warrantycheck');
   }

   /**
    * @return array
    */
   static function getMenuContent() {

      $menu = [];

      $menu['title'] = self::getMenuName();
      $menu['page'] = PLUGIN_WARRANTYCHECK_NOTFULL_WEBDIR."/front/generatecri.php";
      $menu['icon'] = self::getIcon();

      return $menu;
   }

   /**
    * @return string
    */
   static function getIcon() {
      return "far fa-check-circle";
   }

   /**
    * @param $ticket
    * @param $entities
    *
    * @throws \GlpitestSQLError
    */
   function showWizard($ticket, $entities) {
      if(Session::haveRight("plugin_warrantycheck_survey", READ)){
         
         global $DB, $CFG_GLPI;
         $UserID = Session::getLoginUserID();

         // Formulaire
         echo '<div class="card mb-4 shadow-sm w-100">';
         echo '<div class="card-header bg-secondary text-white fw-bold">';
         echo __('<i class="fas fa-search"></i>&nbsp;&nbsp; Vérification de garantie') . 
         '&nbsp;<i class="fas fa-info-circle text-white" 
         data-bs-toggle="popover" 
         data-bs-html="true" 
         data-bs-trigger="hover focus" 
         data-bs-placement="right" 
         data-bs-custom-class="tooltip-style"
         data-bs-content="<b>Fabricants pris en charge :</b><ul style=\'padding-left: 1.2em; margin: 0; text-align: left; list-style-position: inside;\'><li>HP</li><li>Dell</li><li>Lenovo</li><li>Terra</li><li>Dynabook</li></ul>"></i>';
         ?><script>
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
               return new bootstrap.Popover(popoverTriggerEl);
            });
         </script><?php
         echo '</div>';

         echo '<div class="card-body">';
            
            echo "<form method='get' action='" . self::getFormUrl() . "' class='mb-4'>";

               echo '<div class="d-flex flex-wrap justify-content-center">';
                  // Première ligne (HP, Dell, Lenovo)
                  echo '<div class="form-check form-check-inline">';
                     echo '<input class="form-check-input" type="radio" name="Manufacturer" id="inlineRadio1" value="HP">';
                     echo '<label class="form-check-label" for="inlineRadio1">HP</label>';
                  echo '</div>';
                  echo '<div class="form-check form-check-inline">';
                     echo '<input class="form-check-input" type="radio" name="Manufacturer" id="inlineRadio2" value="Dell">';
                     echo '<label class="form-check-label" for="inlineRadio2">Dell</label>';
                  echo '</div>';
                  echo '<div class="form-check form-check-inline">';
                     echo '<input class="form-check-input" type="radio" name="Manufacturer" id="inlineRadio3" value="Lenovo">';
                     echo '<label class="form-check-label" for="inlineRadio3">Lenovo</label>';
                  echo '</div>';
               
                  // Saut de ligne forcé uniquement en affichage mobile
                  echo '<div class="w-100 d-block d-sm-none"></div>';
               
                  // Deuxième ligne (Terra, Dynabook, Auto)
                  echo '<div class="form-check form-check-inline">';
                     echo '<input class="form-check-input" type="radio" name="Manufacturer" id="inlineRadio4" value="Terra">';
                     echo '<label class="form-check-label" for="inlineRadio4">Terra</label>';
                  echo '</div>';
                  echo '<div class="form-check form-check-inline">';
                     echo '<input class="form-check-input" type="radio" name="Manufacturer" id="inlineRadio5" value="Dynabook">';
                     echo '<label class="form-check-label" for="inlineRadio5">Dynabook</label>';
                  echo '</div>';
                  echo '<div class="form-check form-check-inline">';
                     echo '<input class="form-check-input" type="radio" name="Manufacturer" id="inlineRadio6" value="" checked>';
                     echo '<label class="form-check-label" for="inlineRadio6">Auto</label>';
                  echo '</div>';
               echo '</div>';

               echo '<br>';
               echo '<div class="d-flex justify-content-center">';
                  echo '<div class="input-group" style="max-width: 600px; width: 100%;">';
                     echo '<input type="text" name="serial" id="serial" class="form-control" placeholder="Numéro de série" required>';
                     echo '<button type="submit" name="generatecri" id="sig-submitBtn" class="btn btn-primary">';
                        echo __('Vérifier la garantie');
                     echo '</button>';
                  echo '</div>';
               echo '</div>';
            Html::closeForm();

         echo '</div>'; // card-body
         echo '</div>'; // card

         // Résultat
         if (isset($_GET['cache_id'], $_SESSION['generatecri_cache'][$_GET['cache_id']])) {
            $result = $_SESSION['generatecri_cache'][$_GET['cache_id']];
            unset($_SESSION['generatecri_cache'][$_GET['cache_id']]);

            if ($result && is_array($result)) {
               // Détection de l'état de garantie
               $warranty_status = strtolower(trim($result['warranty_status'] ?? ''));
               $color_class = ($warranty_status === 'expired') ? 'bg-danger' : 'bg-success';

               echo '<div class="card shadow-sm mt-4">';
               echo '<div class="card-header ' . $color_class . ' text-white fw-bold">';
               echo __('<i class="fas fa-info-circle"></i>&nbsp;&nbsp; Détails de la garantie') . ' ' . $result['fabricant'];
               echo '</div>';
               echo '<div class="card-body">';
               echo '<strong>Fabricant : </strong>' . $result['fabricant'] . '<br><br>';

               $labels = [
                  'model' => 'Modèle',
                  'serial' => 'N° Série',
                  'product_number' => 'Réf. Produit',
                  'warranty_start' => 'Début',
                  'warranty_end' => 'Fin',
                  'extended_warranty' => 'Extension',
                  'warranty_type' => 'Type',
                  'warranty_status' => 'Statut',
                  'country' => 'Pays',
                  'ship_date' => 'Expédié le',
                  'description' => 'Description'
               ];

               echo '<div class="row">';
               foreach ($labels as $key => $label) {
                  $value = nl2br(htmlspecialchars($result[$key] ?? ''));
                  echo '<div class="col-md-6 mb-2">';
                  echo '<strong>' . $label . ' :</strong><br>' . $value;
                  echo '</div>';
               }
               echo '</div>'; // row
               echo '</div></div>'; // card-body + card
            } else {
               echo '<div class="alert alert-danger mt-4">Erreur : Aucune donnée retournée ou numéro invalide.</div>';
            }
         } else {
            if (isset($_GET['cache_id'])) {
               echo '<div class="alert alert-danger mt-4">Erreur : Aucune donnée retournée (Erreur serveur '.$_GET['fabricant'].'), Fabricant non détécté ou numéro invalide.</div>';
            }
         }

         $SN = $result['serial'];

         echo '<div class="baspage">';
         echo '  <div class="card mt-4 mb-0 shadow-sm w-100">';
         echo '    <div class="card-body">';
         echo '      <div class="card-body d-flex justify-content-center gap-4">';
         echo '        <label class="d-block mb-2"><strong>Sites officiels de vérification de garantie :</strong></label>';
         echo '        <div class="d-flex flex-wrap gap-2">';
         echo '          <a class="btn btn-outline-primary btn-sm" href="https://support.hp.com/fr-fr/check-warranty" target="_blank" onclick="copyToClipboard(\'' . $SN . '\')">HP</a>';
         echo '          <a class="btn btn-outline-primary btn-sm" href="https://pcsupport.lenovo.com/fr/fr/warranty-lookup#/" target="_blank" onclick="copyToClipboard(\'' . $SN . '\')">Lenovo</a>';
         echo '          <a class="btn btn-outline-primary btn-sm" href="https://www.dell.com/support/contractservices/fr-fr/" target="_blank" onclick="copyToClipboard(\'' . $SN . '\')">Dell</a>';
         echo '          <a class="btn btn-outline-primary btn-sm" href="https://www.wortmann.de/fr-fr/profile/snsearch.aspx?SN=' . $SN . '" target="_blank" onclick="copyToClipboard(\'' . $SN . '\')">Terra</a>';
         echo '          <a class="btn btn-outline-primary btn-sm" href="https://support.dynabook.com/support/warranty" target="_blank" onclick="copyToClipboard(\'' . $SN . '\')">Dynabook</a>';
         echo '        </div>';
         echo '      </div>';
         echo '    </div>';
         echo '  </div>';
         echo '</div>';

         ?>
            <style>
               @media only screen and (max-width: 600px) {
                  .baspage {
                     margin-bottom: 20px;
                  }
               }

               @media only screen and (min-width: 768px) {
                  .baspage {
                     position: fixed;
                     bottom: 0;
                     height: 150px; /* Hauteur du footer */
                  }
               }
            </style>

            <script>
               function copyToClipboard(text) {
               if (!text || text.trim() === '') return; // ne rien faire si vide

               navigator.clipboard.writeText(text).then(function() {
                  console.log('Texte copié dans le presse-papiers : ' + text);
               }, function(err) {
                  console.error('Erreur de copie : ', err);
               });
               }
            </script>
         <?php
      }
   }
}
