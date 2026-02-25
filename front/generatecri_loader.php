<?php
include('../../../inc/includes.php');

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

Session::checkLoginUser();

$query = (string)($_SERVER['QUERY_STRING'] ?? '');
// URL relative pour rester dans le meme dossier du plugin, quel que soit le root_doc GLPI.
$targetUrl = 'generatecri.form.php';
if ($query !== '') {
   $targetUrl .= '?' . $query;
}
Html::nullHeader(__('Vérification de garantie', 'warrantycheck'));
?>

<div id="loader-wrapper" style="text-align: center; padding: 50px;">
   <i class="fas fa-spinner fa-spin fa-3x text-primary" aria-hidden="true"></i>
   <p class="mt-3"><strong>Vérification de la garantie ...</strong></p>
   <p class="mt-3"><strong>Interrogation de l'API du constructeur ...</strong></p>
   <noscript>
      <p class="mt-3">
         <a href="<?php echo htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8'); ?>">
            Continuer
         </a>
      </p>
   </noscript>
</div>

<script>
   (function () {
      var url = <?php echo json_encode($targetUrl); ?>;
      var redirected = false;

      function go() {
         if (redirected) {
            return;
         }
         redirected = true;
         window.location.replace(url);
      }

      // Laisse le temps au vrai thème GLPI de se peindre.
      window.addEventListener('load', function () {
         window.setTimeout(go, 120);
      });

      // Filet de sécurité.
      window.setTimeout(go, 1500);
   })();
</script>

<?php
// On ferme les wrappers HTML ouverts par Html::nullHeader() sans exécuter Html::nullFooter(),
// pour éviter les scripts GLPI de fin de page (source des erreurs JS sur ce loader).
echo '</main></div></div></div></body></html>';
