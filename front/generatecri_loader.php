<?php
include('../../../inc/includes.php');

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

Session::checkLoginUser();

if (Session::getCurrentInterface() == 'central') {
   Html::header(__('Rapport', 'warrantycheck'), '', "tools", "pluginwarrantycheckgeneratecri");
} else {
      Html::helpHeader(__('Rapport', 'warrantycheck'));
}

?>

<div id="loader-wrapper" style="text-align: center; padding: 50px;">
   <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
   <p class="mt-3"><strong>Vérification de la garantie ...</strong></p>
   <p class="mt-3"><strong>Interrogation de l’API du constructeur ...</strong></p>
</div>

<div id="content-wrapper" style="display: none;"></div>

<script>
const params = new URLSearchParams(window.location.search);
const url = "<?php echo $CFG_GLPI["root_doc"]; ?>/plugins/warrantycheck/front/generatecri.form.php?" + params.toString();

fetch(url)
   .then(response => response.text())
   .then(html => {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, "text/html");

      // ✅ Supprime les éléments de navigation (navbar GLPI) du body distant
      ['#header', '#menu', '#menu_pane', '#main-menu'].forEach(selector => {
         const nav = doc.querySelector(selector);
         if (nav) nav.remove();
      });

      // ✅ Remplace tout le body actuel par celui chargé (nettoyé)
      document.body.innerHTML = doc.body.innerHTML;

      // ✅ Recharge les <script> dynamiques (inline + src)
      doc.body.querySelectorAll("script").forEach(oldScript => {
         const newScript = document.createElement("script");
         if (oldScript.src) {
            newScript.src = oldScript.src;
            newScript.async = false;
         } else if (!oldScript.textContent.includes("fetch(")) {
            newScript.textContent = oldScript.textContent;
         }
         document.body.appendChild(newScript);
      });

      // ✅ Réactivation de l'interface GLPI
      if (typeof initGLPI === "function") {
         initGLPI();
      }
      document.dispatchEvent(new Event('glpi:init'));

      // ✅ Réactivation des tooltips et popovers
      if (window.jQuery) {
         $('[data-toggle="tooltip"]').tooltip();
         const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
         popoverTriggerList.map(el => new bootstrap.Popover(el));
      }
   })
   .catch(error => {
      document.body.innerHTML = `
         <div class="alert alert-danger text-center mt-4">
            ❌ Erreur lors du chargement : ${error.message}
         </div>`;
      console.error("Erreur JS : ", error);
   });
</script>








