<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginWarrantycheckConfig extends CommonDBTM
{
   static private $_instance = null;

   function __construct()
   {
      global $DB;

      if ($DB->tableExists($this->getTable())) {
         $this->getFromDB(1);
      }
   }

   static function canCreate()
   {
      return Session::haveRight('config', UPDATE);
   }

   static function canView()
   {
      return Session::haveRight('config', READ);
   }

   static function canUpdate()
   {
      return Session::haveRight('config', UPDATE);
   }

   static function getTypeName($nb = 0)
   {
      return __("Warrantycheck", "warrantycheck");
   }

   static function getInstance()
   {
      if (!isset(self::$_instance)) {
         self::$_instance = new self();
         if (!self::$_instance->getFromDB(1)) {
            self::$_instance->getEmpty();
         }
      }
      return self::$_instance;
   }

   static function showConfigForm() //formulaire de configuration du plugin
   {
      $config = new self();
      $config->getFromDB(1);

      $config->showFormHeader(['colspan' => 4]);

      echo "<tr><th colspan='2'>" . __('Extraction des éléments liées', 'gestion') . "</th></tr>";
      echo "<tr class='tab_bg_1 top'><td>" . __('Recherche des Devis, Factures, Bon de commandes, Bon de livraisons', 'rp') . "</td>";
         echo "<td>";
         Dropdown::showYesNo("related_elements", $config->related_elements());
      echo "</td></tr>";
     
      // Dell
      echo "<tr><th colspan='2'>" . __('Dell', 'gestion') . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Client ID", "gestion") . "</td><td>";
            echo Html::input('ClientID_Dell', ['value' => $config->ClientID_Dell(), 'size' => 80]);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Client Secret", "gestion") . "</td><td>";
            echo Html::input('ClientSecret_Dell', ['value' => $config->ClientSecret_Dell(), 'size' => 80]);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      // HP
      echo "<tr><th colspan='2'>" . __('HP', 'gestion') . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Client ID", "gestion") . "</td><td>";
            echo Html::input('ClientID_HP', ['value' => $config->ClientID_HP(), 'size' => 80]);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Client Secret", "gestion") . "</td><td>";
            echo Html::input('ClientSecret_HP', ['value' => $config->ClientSecret_HP(), 'size' => 80]);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      // Filtre 
      echo "<tr><th colspan='2'>" . __('Filtres des numéros de série par Préfixes', 'gestion') . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre HP", "gestion") . "</td><td>";
            echo Html::input('Filtre_HP', ['value' => $config->Filtre_HP(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Lenovo", "gestion") . "</td><td>";
            echo Html::input('Filtre_Lenovo', ['value' => $config->Filtre_Lenovo(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Dell", "gestion") . "</td><td>";
            echo Html::input('Filtre_Dell', ['value' => $config->Filtre_Dell(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Dynabook", "gestion") . "</td><td>";
            echo Html::input('Filtre_Dynabook', ['value' => $config->Filtre_Dynabook(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Terra", "gestion") . "</td><td>";
            echo Html::input('Filtre_Terra', ['value' => $config->Filtre_Terra(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>"; // new
         echo "<td>" . __("Filtre Autres", "gestion") . "</td><td>";
            echo Html::input('Filtre_Autres', ['value' => $config->Filtre_Autres(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>"; // new
         echo "<td>" . __("Filtre IIyama", "gestion") . "</td><td>";
            echo Html::input('Filtre_IIyama', ['value' => $config->Filtre_IIyama(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      // facture, devis, bon de livraison, bon de commande
      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Devis", "gestion") . "</td><td>";
            echo Html::input('Filtre_Devis', ['value' => $config->Filtre_Devis(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Facture", "gestion") . "</td><td>";
            echo Html::input('Filtre_Facture', ['value' => $config->Filtre_Facture(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Bon de livraison", "gestion") . "</td><td>";
            echo Html::input('Filtre_BonDeLivraison', ['value' => $config->Filtre_BonDeLivraison(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Filtre Bon de commande", "gestion") . "</td><td>";
            echo Html::input('Filtre_BonDeCommande', ['value' => $config->Filtre_BonDeCommande(), 'size' => 80, 'style' => 'text-transform: uppercase;']);// bouton configuration du bas de page line 1
         echo "</td>";
      echo "</tr>";
      //-------------------

      echo "<tr><th colspan='2'>" . __('Blacklist pour le fitre des numéros de série', 'gestion') . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Blacklist", "gestion") . "</td><td>";
            Html::textarea([
               'name'  => 'blacklist',
               'value' => $config->blacklist(),
               'rows'  => 10,
            ]);
         echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Blacklist Prefixes", "gestion") . "</td><td>";
            Html::textarea([
               'name'  => 'prefix_blacklist',
               'value' => $config->prefix_blacklist(),
               'rows'  => 2,
            ]);
         echo "</td>";
      echo "</tr>";

      echo "<tr><th colspan='2'>" . __('Droits des utilisateurs sur les filtres de blacklist et les filtres de numéros de service par préfixe', 'gestion') . "</th></tr>";
      echo "<tr class='tab_bg_1 top'><td>" . __('Droits des utilisateurs', 'rp') . "</td>";
      echo "<td>";
         echo '<div style="display: inline-block; margin-right: 30px;">';
         echo '<label for="whitelistuser_read">' . __('Lecture', 'rp') . '</label> ';
         Html::showCheckbox([
            'name'    => 'whitelistuser_read',
            'id'      => 'whitelistuser_read',
            'checked' => $config->whitelistuser_read(),
         ]);
         echo '</div>';
         
         echo '<div style="display: inline-block; margin-right: 30px;">';
         echo '<label for="whitelistuser_update">' . __('Mise à jour', 'rp') . '</label> ';
         Html::showCheckbox([
            'name'    => 'whitelistuser_update',
            'id'      => 'whitelistuser_update',
            'checked' => $config->whitelistuser_update(),
         ]);
         echo '</div>';
         
         echo '<div style="display: inline-block; margin-right: 30px;">';
         echo '<label for="whitelistuser_delete">' . __('Supprimer', 'rp') . '</label> ';
         Html::showCheckbox([
            'name'    => 'whitelistuser_delete',
            'id'      => 'whitelistuser_delete',
            'checked' => $config->whitelistuser_delete(),
         ]);
         echo '</div>';
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
         echo "<td>" . __("Gestion des numéros de série", "gestion") . "</td><td>";
            // URLs construites par GLPI (évite les chemins relatifs)
            $WARRANTY_BASE   = Plugin::getWebDir('warrantycheck');
            $WARRANTY_AJAX   = $WARRANTY_BASE . '/ajax/ajax_get_warranty_tickets.php';
            $WARRANTY_DELETE = $WARRANTY_BASE . '/ajax/ajax_delete_warranty_ticket.php';
            $WARRANTY_EXPORT = $WARRANTY_BASE . '/ajax/ajax_export_warranty_tickets.php'; // export

            // Jeton CSRF pour POST
            $GLPI_CSRF = Session::getNewCSRFToken();
            ?>

            <button id="openModalButton" type="button" class="btn btn-primary">Voir les numéros de série</button>

            <script type="text/javascript">
            const WARRANTY_AJAX_URL   = "<?= $WARRANTY_AJAX ?>";
            const WARRANTY_DELETE_URL = "<?= $WARRANTY_DELETE ?>";
            const WARRANTY_EXPORT_URL = "<?= $WARRANTY_EXPORT ?? '' ?>"; // peut être vide si pas d'export
            const GLPI_CSRF           = "<?= $GLPI_CSRF ?>";

            // Désactive globalement le cache jQuery pour les GET (belt & suspenders)
            $.ajaxSetup({ cache: false });

            let WT = {
            page: 1,
            pageSize: 50,
            q: '',
            prefix: '',
            exact: 0,
            selected: new Set()
            };

            function parseQuery(raw) {
            let input = String(raw || '').toLowerCase().trim();
            let prefix = '', value = input, exact = 0;
            if (input.startsWith('id='))         { prefix='id';     value=input.replace('id=','').trim(); exact=1; }
            else if (input.startsWith('ticket=')){ prefix='ticket'; value=input.replace('ticket=','').trim(); exact=1; }
            else if (input.startsWith('sn='))    { prefix='sn';     value=input.replace('sn=','').trim();  exact=1; }
            else if (input.startsWith('id:'))    { prefix='id';     value=input.replace('id:','').trim(); }
            else if (input.startsWith('ticket:')){ prefix='ticket'; value=input.replace('ticket:','').trim(); }
            else if (input.startsWith('sn:'))    { prefix='sn';     value=input.replace('sn:','').trim(); }
            return {prefix, value, exact};
            }

            function loadWarrantyTickets() {
            $('#warrantyTicketsBody').html('<tr><td colspan="4">Chargement…</td></tr>');

            $.ajax({
               url: WARRANTY_AJAX_URL,
               method: 'GET',
               dataType: 'json',
               cache: false,                 // <-- No cache
               data: {
                  page: WT.page,
                  pageSize: WT.pageSize,
                  q: WT.q,
                  prefix: WT.prefix,
                  exact: WT.exact,
                  _ts: Date.now()            // <-- anti-cache URL param
               }
            }).done(function(resp){
               // Si le backend renvoie {error:true}, on affiche un message lisible
               if (resp && resp.error) {
                  const dbg = resp.debug_html ? ("\n" + String(resp.debug_html).slice(0,500)) : "";
                  alert("Erreur serveur: " + (resp.message || "inconnue") + dbg);
                  $('#warrantyTicketsBody').html('<tr><td colspan="4">Erreur de chargement</td></tr>');
                  return;
               }
               renderWarrantyRows(resp.rows || []);
               renderPagination(resp.page, resp.pageSize, resp.total);
               updateSelectedCount();
               $('#checkAll').prop('checked', false);
            }).fail(function(xhr){
               let body = xhr.responseText || '';
               const looksHtml = /^\s*</.test(body);
               let msg = `Erreur Ajax ${xhr.status} ${xhr.statusText}`;
               if (looksHtml) { msg += " — Réponse HTML (session expirée ? mauvais chemin ?)"; }
               else { msg += `\n${body.substring(0, 500)}`; }
               console.error(msg);
               alert(msg);
               $('#warrantyTicketsBody').html('<tr><td colspan="4">Erreur de chargement</td></tr>');
            });
            }

            function renderWarrantyRows(rows) {
            let html = '';
            if (!rows.length) {
               html = '<tr><td colspan="4"><em>Aucun résultat</em></td></tr>';
            } else {
               rows.forEach(r => {
                  const id   = r.id ?? '';
                  const tick = r.tickets_id ?? '';
                  const sn   = r.serial_number ?? '';
                  const checked = WT.selected.has(String(id)) ? 'checked' : '';
                  const safeSN = $('<div>').text(sn).html();
                  html += `<tr>
                  <td><input type="checkbox" class="warrantyCheckbox" value="${id}" ${checked}></td>
                  <td>${id}</td>
                  <td>${tick}</td>
                  <td>${safeSN}</td>
                  </tr>`;
               });
            }
            $('#warrantyTicketsBody').html(html);
            }

            function renderPagination(page, pageSize, total) {
            const $p = $('#warrantyPagination');
            const totalPages = Math.max(1, Math.ceil(total / pageSize));
            if (totalPages <= 1) { $p.html(''); return; }
            const pageBtn = (p, label, disabled=false, active=false) =>
               `<li class="page-item ${disabled?'disabled':''} ${active?'active':''}">
                  <a class="page-link" href="#" data-page="${p}">${label}</a>
               </li>`;
            let html = '';
            html += pageBtn(page-1, '&laquo;', page===1);
            const start = Math.max(1, page-2);
            const end   = Math.min(totalPages, page+2);
            if (start > 1) html += pageBtn(1, '1');
            if (start > 2) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
            for (let p=start; p<=end; p++) html += pageBtn(p, String(p), false, p===page);
            if (end < totalPages-1) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
            if (end < totalPages)   html += pageBtn(totalPages, String(totalPages));
            html += pageBtn(page+1, '&raquo;', page===totalPages);
            $p.html(html);
            }

            // Ouverture modal
            $(document).on('click', '#openModalButton', function() {
            $('#customModal').modal('show');
            WT.page = 1;
            loadWarrantyTickets();
            });

            // Pagination
            $(document).on('click', '#warrantyPagination .page-link', function(e){
            e.preventDefault();
            const target = parseInt($(this).data('page'), 10);
            if (!isNaN(target) && target !== WT.page) { WT.page = target; loadWarrantyTickets(); }
            });

            // Sélection
            $(document).on('change', '.warrantyCheckbox', function() {
            const id = String($(this).val());
            if (this.checked) WT.selected.add(id); else WT.selected.delete(id);
            updateSelectedCount();
            });
            $(document).on('change', '#checkAll', function() {
            const checked = this.checked;
            $('.warrantyCheckbox').each(function() { $(this).prop('checked', checked).trigger('change'); });
            });
            function updateSelectedCount() { $('#selectedCount').text(WT.selected.size); }

            // Suppression (CSRF)
            $(document).on('click', '#deleteSelectedBtn', function() {
            if (WT.selected.size === 0) { alert("Aucun élément sélectionné."); return; }
            if (!confirm(`Confirmer la suppression des ${WT.selected.size} éléments sélectionnés ?`)) return;
            $.post(WARRANTY_DELETE_URL,
               { _glpi_csrf_token: GLPI_CSRF, ids: Array.from(WT.selected) },
               function() { WT.selected.clear(); loadWarrantyTickets(); }
            ).fail(function(xhr){
               let body = xhr.responseText || '';
               const looksHtml = /^\s*</.test(body);
               let msg = `Erreur suppression ${xhr.status} ${xhr.statusText}`;
               if (!looksHtml && body) msg += `\n${body.substring(0, 500)}`;
               alert(msg); console.error(msg);
            });
            });

            // EXPORT CSV via AJAX (blob) + overlay (si tu l'as ajouté)
            function showExportLoader(show) {
            $('#exportOverlay').css('display', show ? 'flex' : 'none');
            $('#exportBtn').prop('disabled', !!show);
            }
            $(document).on('click', '#exportBtn', function() {
            const params = { q: WT.q, prefix: WT.prefix, exact: WT.exact };
            showExportLoader(true);
            $.ajax({
               url: WARRANTY_EXPORT_URL,
               method: 'GET',
               data: { ...params, _ts: Date.now() }, // anti-cache aussi
               cache: false,
               xhrFields: { responseType: 'blob' }
            }).done(function(blob, status, xhr) {
               const ct = (xhr.getResponseHeader('Content-Type') || '').toLowerCase();
               if (ct.includes('application/json') || ct.includes('text/json')) {
                  const reader = new FileReader();
                  reader.onload = function() {
                  try { const j = JSON.parse(reader.result); alert(j.message || 'Erreur export'); }
                  catch(e) { alert('Erreur export (réponse JSON invalide).'); }
                  showExportLoader(false);
                  };
                  reader.readAsText(blob);
                  return;
               }
               let filename = 'warranty_tickets.csv';
               const cd = xhr.getResponseHeader('Content-Disposition') || '';
               const match = cd.match(/filename\*?=(?:UTF-8'')?"?([^\";]+)"?/i);
               if (match && match[1]) filename = decodeURIComponent(match[1]).replace(/[/\\]/g,'_');
               const url = window.URL.createObjectURL(blob);
               const a = document.createElement('a');
               a.href = url; a.download = filename;
               document.body.appendChild(a); a.click(); a.remove();
               window.URL.revokeObjectURL(url);
               showExportLoader(false);
            }).fail(function(xhr) {
               let msg = `Erreur export ${xhr.status} ${xhr.statusText}`;
               if (xhr.responseText) msg += `\n${xhr.responseText.substring(0, 500)}`;
               alert(msg);
               showExportLoader(false);
            });
            });

            // Recherche (debounce 300ms)
            let debounceTimer = null;
            $(document).on('input', '#searchWarrantyInput', function() {
            clearTimeout(debounceTimer);
            const raw = $(this).val();
            debounceTimer = setTimeout(() => {
               const {prefix, value, exact} = parseQuery(raw);
               WT.prefix = prefix; WT.q = value; WT.exact = exact; WT.page = 1;
               loadWarrantyTickets();
            }, 300);
            });
            </script>

            <?php
            // Modal HTML (ajout du bouton Export + overlay caché)
            echo <<<HTML
            <div class="modal fade" id="customModal" tabindex="-1" aria-labelledby="AddGestionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
               <div class="modal-content">
                  <div class="modal-header">
                  <h5 class="modal-title" id="AddGestionModalLabel">Gestion des numéros de série</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body" style="overflow-x:auto; position:relative;">
                  <div class="d-flex mb-2 gap-2">
                     <input type="text" id="searchWarrantyInput" class="form-control" placeholder="Rechercher… (id=123 | ticket=456 | sn=ABC | global)">
                     <button type="button" id="deleteSelectedBtn" class="btn btn-danger">
                        Supprimer la sélection (<span id="selectedCount">0</span>)
                     </button>
                     <button type="button" id="exportBtn" class="btn btn-outline-secondary">
                        Exporter CSV
                     </button>
                  </div>

                  <table class="table table-striped">
                     <thead>
                        <tr>
                        <th><input type="checkbox" id="checkAll"></th>
                        <th>ID</th>
                        <th>Ticket ID</th>
                        <th>Serial Number</th>
                        </tr>
                     </thead>
                     <tbody id="warrantyTicketsBody"></tbody>
                  </table>
                  <nav><ul class="pagination justify-content-center" id="warrantyPagination"></ul></nav>

                  <!-- Overlay export (centré, masqué par défaut) -->
                  <div id="exportOverlay"
                        style="display:none; position:absolute; inset:0; background:rgba(255,255,255,0.75); z-index:1060; align-items:center; justify-content:center;">
                     <div class="d-flex align-items-center p-3 bg-white rounded shadow">
                        <div class="spinner-border me-3" role="status" aria-hidden="true"></div>
                        <div>
                        <strong>Création du fichier CSV en cours…</strong><br>
                        <small>Merci de patienter</small>
                        </div>
                     </div>
                  </div>

                  </div>
                  <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                  </div>
               </div>
            </div>
            </div>
            HTML;
         echo "</td>";
      echo "</tr>";

      $config->showFormButtons(['candel' => false]);
      return false;
   }

   // Fonction pour charger la clé de cryptage à partir du fichier
   private function loadEncryptionKey() {
      // Chemin vers le fichier de clé de cryptage
      $file_path = GLPI_ROOT . '/config/glpicrypt.key';
      return file_get_contents($file_path);
   }

   // return fonction (retourn les values enregistrées en bdd)
   function Filtre_HP()
   {
      return ($this->fields['Filtre_HP']);
   }
   function Filtre_Lenovo()
   {
      return ($this->fields['Filtre_Lenovo']);
   }
   function Filtre_Dell()
   {
      return ($this->fields['Filtre_Dell']);
   }
   function Filtre_Dynabook()
   {
      return ($this->fields['Filtre_Dynabook']);
   }   
   function Filtre_Terra()
   {
      return ($this->fields['Filtre_Terra']);
   }
   function Filtre_Autres() // new
   {
      return ($this->fields['Filtre_Autres']);
   }
   function Filtre_IIyama() // new
   {
      return ($this->fields['Filtre_IIyama']);
   }
   function Filtre_Devis()
   {
      return ($this->fields['Filtre_Devis']);
   }
   function Filtre_Facture()
   {
      return ($this->fields['Filtre_Facture']);
   }
   function Filtre_BonDeLivraison()
   {
      return ($this->fields['Filtre_BonDeLivraison']);
   }
   function Filtre_BonDeCommande()
   {
      return ($this->fields['Filtre_BonDeCommande']);
   }
   function blacklist()
   {
      return ($this->fields['blacklist']);
   }
   function related_elements()
   {
      return ($this->fields['related_elements']);
   }
   function prefix_blacklist()
   {
      return ($this->fields['prefix_blacklist']);
   }
   function whitelistuser_read()
   {
      return ($this->fields['whitelistuser_read']);
   }
   function whitelistuser_delete()
   {
      return ($this->fields['whitelistuser_delete']);
   }
   function whitelistuser_update()
   {
      return ($this->fields['whitelistuser_update']);
   }
 
   function ClientID_Dell(){
      return openssl_decrypt(base64_decode($this->fields['ClientID_Dell']), 'aes-256-cbc', $this->loadEncryptionKey(), 0, '1234567890123456');   
   }
   function ClientSecret_Dell(){
      return openssl_decrypt(base64_decode($this->fields['ClientSecret_Dell']), 'aes-256-cbc', $this->loadEncryptionKey(), 0, '1234567890123456');   
   }
   function ClientID_HP(){
      return openssl_decrypt(base64_decode($this->fields['ClientID_HP']), 'aes-256-cbc', $this->loadEncryptionKey(), 0, '1234567890123456');   
   }
   function ClientSecret_HP(){
      return openssl_decrypt(base64_decode($this->fields['ClientSecret_HP']), 'aes-256-cbc', $this->loadEncryptionKey(), 0, '1234567890123456');   
   }
   // return fonction


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      if ($item->getType() == 'Config') {
         return __("Warrantycheck", "warrantycheck");
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      if ($item->getType() == 'Config') {
         self::showConfigForm();
      }
      return true;
   }
  
   static function install(Migration $migration)
   {
      global $DB;

      $default_charset = DBConnection::getDefaultCharset();
      $default_collation = DBConnection::getDefaultCollation();
      $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

      $table = self::getTable();
      $config = new self();

      if (!$DB->tableExists($table)) {

         $migration->displayMessage("Installing $table");

         $query = "CREATE TABLE IF NOT EXISTS $table (
                  `id` int {$default_key_sign} NOT NULL auto_increment,
                  `ClientID_Dell` TEXT NULL,
                  `ClientSecret_Dell` TEXT NULL,
                  `ClientID_HP` TEXT NULL,
                  `ClientSecret_HP` TEXT NULL,
                  `Filtre_HP` TEXT NULL DEFAULT '5CD,5CG,CZC,1H',
                  `Filtre_Lenovo` TEXT NULL DEFAULT 'MP,PF,PW',
                  `Filtre_Dell` TEXT NULL,
                  `Filtre_Autres` TEXT NULL,
                  `Filtre_Dynabook` TEXT NULL DEFAULT '41',
                  `Filtre_Terra` TEXT NULL DEFAULT 'R',
                  `Filtre_Devis` TEXT NULL DEFAULT 'DE',
                  `Filtre_Facture` TEXT NULL DEFAULT 'FA',
                  `Filtre_BonDeLivraison` TEXT NULL DEFAULT 'BL',
                  `Filtre_BonDeCommande` TEXT NULL DEFAULT 'BC',
                  `related_elements` INT(10) NULL DEFAULT '1',
                  `whitelistuser_read` INT(10) NULL DEFAULT '1',
                  `whitelistuser_delete` INT(10) NULL DEFAULT '0',
                  `whitelistuser_update` INT(10) NULL DEFAULT '1',
                  `blacklist` MEDIUMTEXT NULL DEFAULT 'FR1009626,fr,FR,08H00,08H30,09H00,09H30,10H00,11H00,12H00,13H30,17H00,ABEND,ABTEILUNG,ACCUEIL,ADDRESS,ADMINISTRATION,ADRESSE,AFTERNOON,AGENT,ANFRAGE,ANNÉE,ANRUF,APPEL,APRÈS-MIDI,ASSISTANCE,AUSBILDUNG,AUSTAUSCH,AUTHORIZATION,AUTORISATION,Backup,Beispiel,Benutzer,Bonjour,Building,BÂTIMENT,CALL,CAS,CASE,CD54,CENTER,CENTRE,CHECK,CLIENT,COLLÈGE,COMMUNICATION,COMPUTER,CONFIGURATION,CONNECTION,CONNEXION,DANKE,DATA,DATEN,DAY,DAYS,DEMANDE,DEPARTEMENT54,DEPARTMENT,DIENST,DIRECTION,DONNÉES,DRUCKER,DSI,EBENE,EINSATZ,EMAIL,EMPFANG,ESCALIER,ETAGE,EVENING,EXAMPLE,EXEMPLE,FAILURE,FALL,FEHLER,FIRSTNAME,FLOOR,FOG-PRG-S110-00,FORMATION,GEBÄUDE,GENEHMIGUNG,GLPI,HALLO,HARDWARE,HELLO,HEURES,HILFE,HOURS,IMPRIMANTE,INCIDENT,INTERVENANT,INTERVENTION,ITIL,JAHR,JOUR,JOURS,KOMMUNIKATION,KONFIGURATION,KUNDE,LAPTOP,LASTNAME,LEVEL,LOGICIEL,LYCÉE,MACHINE,MAINTENANCE,MASCHINE,MATERIAL,MATIN,MATÉRIEL,MERCI,MITARBEITER,MODEL,MODELL,MODÈLE,MOIS,MONAT,MONTH,MORGEN,MORNING,NACHMITTAG,NACHNAME,NAME,NETWORK,NETZWERK,NIVEAU,NIVEAUX,NOM,NUMBER,NUMMER,NUMÉRISATION,NUMÉRO,ORDINATEUR,OXE-APP01,PANNE,PHONE,PLN-GNC-PORT-04,PORTABLE,PRINTER,PROBLEM,PROBLÈME,PRODUCT,PRODUIT,PRODUKT,PRÉNOM,RAUM,RDC-ADMIN02,RECEPTION,REFERENCE,REFERENZ,REMPLACEMENT,REPAIR,REPARATUR,REPLACEMENT,REQUEST,ROOM,RÉCEPTION,RÉFÉRENCE,RÉPARATION,RÉSEAU,SALLE,SAUVEGARDE,SCAN,SCHOOL,SCHULE,SEMAINE,SERVICE,SICHERUNG,SITE,SOFTWARE,SOIR,ST-ADMIN01,STAIRS,STANDORT,STUNDEN,SUPPORT,TAG,TAGE,TECHNICIAN,TECHNICIEN,TECHNIKER,TELECOM,TELEFON,TELEKOM,TEMPS,TEST,THANKS,TICKET,TIME,TRAINING,TREPPE,TÉLÉCOMS,TÉLÉPHONE,UNTERSTÜTZUNG,USER,UTILISATEUR,VERBINDUNG,VERWALTUNG,VORFALL,VORNAME,VÉRIFICATION,WARTUNG,WEEK,WOCHE,YEAR,ZEIT,ZENTRUM,ZONE,ÉTABLISSEMENT,ÉTAGE,ÜBERPRÜFUNG,überprüfung',
                  `prefix_blacklist` MEDIUMTEXT NULL DEFAULT 'KB,X8,0X,DE23,PRB,ERR,VER',
                  PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
         $DB->query($query) or die($DB->error());
         $config->add(['id' => 1,]);
      }

      if ($_SESSION['PLUGIN_WARRANTYCHECK_VERSION'] > '1.0.3'){
         // Vérifier si les colonnes existent déjà
         $columns = $DB->query("SHOW COLUMNS FROM `$table`")->fetch_all(MYSQLI_ASSOC);

         // Liste des colonnes à vérifier
         $required_columns = [
            'related_elements',
            'Filtre_Devis',
            'Filtre_Facture',
            'Filtre_BonDeLivraison',
            'Filtre_BonDeCommande',
            'whitelistuser_read',
            'whitelistuser_delete',
            'whitelistuser_update',
            'prefix_blacklist',
         ];

         // Liste pour les colonnes manquantes
         $missing_columns = array_diff($required_columns, array_column($columns, 'Field'));

         if (!empty($missing_columns)) {
            $query= "ALTER TABLE $table
               ADD COLUMN `whitelistuser_read` INT(10) NULL DEFAULT '1',
               ADD COLUMN `whitelistuser_delete` INT(10) NULL DEFAULT '0',
               ADD COLUMN `whitelistuser_update` INT(10) NULL DEFAULT '1',
               ADD COLUMN `prefix_blacklist` MEDIUMTEXT NULL DEFAULT 'KB,X8,0X,DE23,PRB,ERR,VER',
               ADD COLUMN `related_elements` INT(10) NULL DEFAULT '1',
               ADD COLUMN `Filtre_Devis` TEXT NULL DEFAULT 'DE',
               ADD COLUMN `Filtre_Facture` TEXT NULL DEFAULT 'FA',
               ADD COLUMN `Filtre_BonDeLivraison` TEXT NULL DEFAULT 'BL',
               ADD COLUMN `Filtre_BonDeCommande` TEXT NULL DEFAULT 'BC';";
            $DB->query($query) or die($DB->error());
         }
      }

      if ($_SESSION['PLUGIN_WARRANTYCHECK_VERSION'] > '1.0.8'){ // new
         // Vérifier si les colonnes existent déjà
         $columns = $DB->query("SHOW COLUMNS FROM `$table`")->fetch_all(MYSQLI_ASSOC);

         // Liste des colonnes à vérifier
         $required_columns = [
            'Filtre_IIyama',
            'Filtre_Autres',
         ];

         // Liste pour les colonnes manquantes
         $missing_columns = array_diff($required_columns, array_column($columns, 'Field'));

         if (!empty($missing_columns)) {
            $query= "ALTER TABLE $table
               ADD COLUMN `Filtre_IIyama` TEXT NULL,
               ADD COLUMN `Filtre_Autres` TEXT NULL";
            $DB->query($query) or die($DB->error());
         }
      }
   }

   static function uninstall(Migration $migration)
   {
      global $DB;

      $table = self::getTable();
      if ($DB->TableExists($table)) {
         $migration->displayMessage("Uninstalling $table");
         $migration->dropTable($table);
      }
      $table = 'glpi_plugin_warrantycheck';
      if ($DB->TableExists($table)) {
         $migration->displayMessage("Uninstalling $table");
         $migration->dropTable($table);
      }
   }
}
