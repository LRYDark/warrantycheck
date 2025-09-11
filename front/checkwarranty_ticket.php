<?php
include('../../../inc/includes.php');
Session::checkLoginUser();

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

ob_clean(); // Vide tout ce qui a pu être envoyé avant

    global $DB;

/* ======================================================================
   ===================   HELPERS + EXTRACTEUR ULTRA   ====================
   ====================================================================== */

function _cfg_csv_to_array(?string $csv): array {
    if (!is_string($csv) || $csv === '') return [];
    $parts = preg_split('/[,\;\s]+/u', $csv, -1, PREG_SPLIT_NO_EMPTY);
    $out = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p !== '') $out[] = $p;
    }
    return $out;
}

function normalizeTextUltra(string $text): string {
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $text = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', ' ', $text);
    $text = preg_replace('#<(img|svg|meta|link|iframe|noscript)[^>]*?>#is', ' ', $text);
    $text = preg_replace('/\s+\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/', ' ', $text);
    $text = preg_replace('#<\s*br\s*/?>#i', ' ', $text);

    $text = strip_tags($text);
    $text = preg_replace('/[^\PC\s]/u', ' ', $text);
    $text = preg_replace('/[\r\n\t]+/u', ' ', $text);
    $text = preg_replace('/\s{2,}/u', ' ', $text);

    return trim($text);
}

function hasAny(string $haystack, array $needles): bool {
    foreach ($needles as $n) {
        if ($n === '') continue;
        if (mb_stripos($haystack, $n) !== false) return true;
    }
    return false;
}

function isDateish(string $v): bool {
    $v = strtoupper($v);
    if (preg_match('/^\d{4}[01]\d[0-3]\d$/', $v)) return true;
    if (preg_match('/^[0-3]?\d[.\-\/][01]?\d[.\-\/]\d{2,4}$/', $v)) return true;
    if (preg_match('/^\d{4,6}H\d{2}$/', $v)) return true;
    $mois = 'JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|SEPT|OCT|NOV|DEC|JANV|FEV|FÉV|MARS|AVR|MAI|JUIN|JUIL|AOUT|AOÛT|SEPTEM|OCTOB|NOVEM|DECEM';
    if (preg_match('/\b(' . $mois . ')\b/u', $v)) return true;
    return false;
}

function genericStrongShape(string $v): bool {
    $L = strlen($v);
    if ($L < 6 || $L > 20) return false;
    if (!preg_match('/[A-Z]/', $v)) return false;
    if (!preg_match('/\d/',   $v)) return false;
    return true;
}

function looksLikeWordy(string $v): bool {
    return (bool)preg_match('/^[A-Z]{6,}$/', $v);
}

function isLikelyHPPartNumber(string $v): bool {
    if (preg_match('/^[A-Z0-9]{5}[A-Z]{2}$/', $v)) return true;
    if (preg_match('/^[A-Z0-9]{5}[A-Z]{2}#\w{2,3}$/', $v)) return true;
    return false;
}

function isDellServiceTag(string $v): bool {
    if (!preg_match('/^[A-HJ-NPR-Z0-9]{7}$/', $v)) return false;
    if (!preg_match('/\d/', $v) || !preg_match('/[A-Z]/', $v)) return false;
    $esc = base_convert($v, 36, 10);
    if ($esc === null || $esc === '' || !ctype_digit($esc)) return false;
    $len = strlen($esc);
    return ($len >= 10 && $len <= 12);
}

function isHPSerialWithPrefixes(string $v, array $hpPrefixes): bool {
    $v = strtoupper($v);
    if (!preg_match('/^[A-Z0-9]{9,10}$/', $v)) return false;    // HP = 9 ou 10
    foreach ($hpPrefixes as $p) {
        $p = strtoupper($p);
        if ($p !== '' && strncmp($v, $p, strlen($p)) === 0) return true;
    }
    return false;
}

function isLenovoSerialWithPrefixes(string $v, array $lvPrefixes): bool {
    $v = strtoupper($v);
    if (!preg_match('/^[A-Z0-9]{7,10}$/', $v)) return false;    // Lenovo ~7–10
    foreach ($lvPrefixes as $p) {
        $p = strtoupper($p);
        if ($p !== '' && strncmp($v, $p, strlen($p)) === 0) return true;
    }
    return false;
}

function isDynabookSerialWithPrefixes(string $v, array $dbPrefixes): bool {
    $v = strtoupper($v);
    if (!preg_match('/^[A-Z0-9]{8,10}$/', $v)) return false;
    foreach ($dbPrefixes as $p) {
        $p = strtoupper($p);
        if ($p !== '' && strncmp($v, $p, strlen($p)) === 0) return true;
    }
    return false;
}

function isTerraSerialWithPrefixes(string $v, array $terraPrefixes): bool {
    $v = strtoupper($v);
    if (!preg_match('/^[A-Z0-9]{7,12}$/', $v)) return false;
    foreach ($terraPrefixes as $p) {
        $p = strtoupper($p);
        if ($p !== '' && strncmp($v, $p, strlen($p)) === 0) return true;
    }
    return false;
}

function isOtherWhitelisted(string $v, array $otherPrefixes): bool {
    $v = strtoupper($v);
    foreach ($otherPrefixes as $p) {
        $p = strtoupper($p);
        if ($p !== '' && strncmp($v, $p, strlen($p)) === 0) return true;
    }
    return false;
}

function isIiyamaNumeric(string $v, array $iiyamaPrefixes): bool {
    if (!preg_match('/^\d{13}$/', $v)) return false;
    foreach ($iiyamaPrefixes as $p) {
        $p = preg_replace('/\D/', '', $p);
        if ($p !== '' && strncmp($v, $p, strlen($p)) === 0) return true;
    }
    return false;
}

function startsWithAny(string $hay, array $prefixes): bool {
    $hay = strtoupper($hay);
    foreach ($prefixes as $p) {
        $p = strtoupper(trim($p));
        if ($p === '') continue;
        if (strncmp($hay, $p, strlen($p)) === 0) return true;
    }
    return false;
}

function scanTokens(string $text): array {
    $tokens = preg_split('/[^A-Z0-9\-]+/i', $text);
    $out = [];
    foreach ($tokens as $t) {
        $t = strtoupper(trim($t));
        if ($t === '') continue;
        if (strlen($t) < 6 || strlen($t) > 20) continue;
        $out[] = $t;
    }
    return $out;
}

/* ===== Ajout : déduction du fabricant pour l’insertion DB (sans API) ===== */
function guessFabricantFromSerial(string $serial, PluginWarrantycheckConfig $config): ?string {
    $s = strtoupper(preg_replace('/[^A-Z0-9]/', '', $serial));

    // Préfixes issus de la BDD
    $hpPrefixes      = array_map('strtoupper', _cfg_csv_to_array((string)$config->Filtre_HP()));
    $lenovoPrefixes  = array_map('strtoupper', _cfg_csv_to_array((string)$config->Filtre_Lenovo()));
    $dellPrefixes    = array_map('strtoupper', _cfg_csv_to_array((string)$config->Filtre_Dell()));
    $dynabookPref    = array_map('strtoupper', _cfg_csv_to_array((string)$config->Filtre_Dynabook()));
    $terraPref       = array_map('strtoupper', _cfg_csv_to_array((string)$config->Filtre_Terra()));
    $iiyamaPref      = array_map('trim',       _cfg_csv_to_array((string)($config->Filtre_IIyama() ?? '')));
    $otherPref       = array_map('strtoupper', _cfg_csv_to_array((string)($config->Filtre_Autres() ?? '')));

    if (empty($iiyamaPref)) $iiyamaPref = ['1188','1249','1251'];

    // Règles
    if (isDellServiceTag($s) || startsWithAny($s, $dellPrefixes))             return 'Dell';
    if (isHPSerialWithPrefixes($s, $hpPrefixes))                               return 'HP';
    if (isLenovoSerialWithPrefixes($s, $lenovoPrefixes))                       return 'Lenovo';
    if (isDynabookSerialWithPrefixes($s, $dynabookPref))                       return 'Dynabook';
    if (isTerraSerialWithPrefixes($s, $terraPref))                             return 'Terra';
    if (ctype_digit($s) && strlen($s) === 13 && isIiyamaNumeric($s, $iiyamaPref)) return 'IIYAMA';
    if (isOtherWhitelisted($s, $otherPref) && genericStrongShape($s))          return 'Autres';

    return null;
}

/* =====================  EXTRACTEUR PRINCIPAL  ===================== */

function findSerialNumbers(string $text): array {
    $norm = normalizeTextUltra($text);

    $config = new PluginWarrantycheckConfig();

    $blacklist_map = [];
    $blacklist_row = (string)$config->blacklist();
    foreach (_cfg_csv_to_array($blacklist_row) as $w) {
        $w = strtolower(trim($w));
        if ($w !== '') $blacklist_map[$w] = true;
    }

    $prefix_blacklist = array_map('strtoupper', _cfg_csv_to_array((string)$config->prefix_blacklist()));

    $hpPrefixes      = array_map('strtoupper', _cfg_csv_to_array((string)$config->Filtre_HP()));
    $lenovoPrefixes  = array_map('strtoupper', _cfg_csv_to_array((string)$config->Filtre_Lenovo()));
    $dellPrefixes    = array_map('strtoupper', _cfg_csv_to_array((string)$config->Filtre_Dell()));
    $dynabookPref    = array_map('strtoupper', _cfg_csv_to_array((string)$config->Filtre_Dynabook()));
    $terraPref       = array_map('strtoupper', _cfg_csv_to_array((string)$config->Filtre_Terra()));
    $iiyamaPref      = array_map('trim',       _cfg_csv_to_array((string)($config->Filtre_IIyama() ?? '')));
    $otherPref       = array_map('strtoupper', _cfg_csv_to_array((string)($config->Filtre_Autres() ?? '')));

    if (empty($iiyamaPref)) {
        $iiyamaPref = ['1188','1249','1251'];
    }

    $BLpref = array_map('strtoupper', _cfg_csv_to_array((string)$config->Filtre_BonDeLivraison()));
    $BCpref = array_map('strtoupper', _cfg_csv_to_array((string)$config->Filtre_BonDeCommande()));
    $FApref = array_map('strtoupper', _cfg_csv_to_array((string)$config->Filtre_Facture()));
    $DEpref = array_map('strtoupper', _cfg_csv_to_array((string)$config->Filtre_Devis()));

    $hasDell   = hasAny($norm, ['dell','alienware']);
    $hasIiyama = hasAny($norm, ['iiyama']);

    // === Libellés forts : si présent → on prend d’office (sauf blacklist mot) ===
    $labelCandidates = [];
    if (preg_match_all('/(?:num[ée]ro\s*de\s*s[ée]rie|n[\s°o]*\s*de\s*s[ée]rie|no\s*de\s*s[ée]rie|serial(?:\s*number)?|s\/?n|sn)\s*(?:[:#\-\/]*\s*)([A-Z0-9\-]{6,20})/iu', $norm, $m)) {
        foreach ($m[1] as $raw) {
            $v = strtoupper(trim($raw));
            if ($v === '') continue;
            if (isset($blacklist_map[strtolower($v)])) continue;

            if (ctype_digit($v) && strlen($v) === 13) { $labelCandidates[] = $v; continue; }
            if (isDellServiceTag($v))                { $labelCandidates[] = $v; continue; }
            if (isHPSerialWithPrefixes($v, $hpPrefixes))         { $labelCandidates[] = $v; continue; }
            if (isLenovoSerialWithPrefixes($v, $lenovoPrefixes)) { $labelCandidates[] = $v; continue; }
            if (isDynabookSerialWithPrefixes($v, $dynabookPref)) { $labelCandidates[] = $v; continue; }
            if (isTerraSerialWithPrefixes($v, $terraPref))       { $labelCandidates[] = $v; continue; }
            if (isOtherWhitelisted($v, $otherPref) && genericStrongShape($v)) { $labelCandidates[] = $v; continue; }

            if (genericStrongShape($v)) { $labelCandidates[] = $v; continue; }
        }
    }
    $labelSet = [];
    foreach ($labelCandidates as $lc) { $labelSet[strtoupper($lc)] = true; }

    // === Scan global des tokens ===
    $tokens      = scanTokens($norm);
    $docTokens   = [];
    $snTokens    = [];
    $dellBucket  = [];

    foreach ($tokens as $t) {
        $T = $t;

        if (ctype_digit($T) && isIiyamaNumeric($T, $iiyamaPref)) {
            $snTokens[] = $T;
            continue;
        }

        if (isset($blacklist_map[strtolower($T)])) continue;
        if (isDateish($T))                          continue;
        if (isLikelyHPPartNumber($T))               continue;
        if (looksLikeWordy($T))                     continue;

        $banned = false;
        foreach ($prefix_blacklist as $pb) {
            if ($pb !== '' && strncmp($T, $pb, strlen($pb)) === 0) { $banned = true; break; }
        }
        if ($banned) continue;

        if (preg_match('/^[A-Z]{2}\d{4,}$/', $T)) {
            if (startsWithAny($T, $BLpref) || startsWithAny($T, $BCpref) ||
                startsWithAny($T, $FApref) || startsWithAny($T, $DEpref)) {
                $docTokens[] = $T;
                continue;
            }
        }

        if (strlen($T) === 7 && preg_match('/^[A-HJ-NPR-Z0-9]{7}$/', $T) && isDellServiceTag($T)) {
            $dellBucket[$T] = true;
            continue;
        }

        if (isHPSerialWithPrefixes($T, $hpPrefixes))         { $snTokens[] = $T; continue; }
        if (isLenovoSerialWithPrefixes($T, $lenovoPrefixes)) { $snTokens[] = $T; continue; }
        if (isDynabookSerialWithPrefixes($T, $dynabookPref)) { $snTokens[] = $T; continue; }
        if (isTerraSerialWithPrefixes($T, $terraPref))       { $snTokens[] = $T; continue; }

        if (isOtherWhitelisted($T, $otherPref) && genericStrongShape($T)) {
            $snTokens[] = $T; continue;
        }
    }

    if ($hasDell || count($dellBucket) >= 2) {
        foreach (array_keys($dellBucket) as $d) $snTokens[] = $d;
    }

    $snTokens = array_merge($snTokens, $labelCandidates);

    $docTokens = array_values(array_unique($docTokens));
    $snTokens  = array_values(array_unique($snTokens));

    $snTokens = array_values(array_filter($snTokens, function($v) use ($prefix_blacklist, $blacklist_map, $iiyamaPref, $hasIiyama, $labelSet) {
        $V = strtoupper($v);

        if (isset($blacklist_map[strtolower($V)])) return false;

        if (ctype_digit($V) && strlen($V) === 13) {
            if (isIiyamaNumeric($V, $iiyamaPref) || $hasIiyama || isset($labelSet[$V])) {
                return true;
            }
            return false;
        }

        foreach ($prefix_blacklist as $pb) {
            if ($pb !== '' && strncmp($V, $pb, strlen($pb)) === 0) return false;
        }

        if (looksLikeWordy($V)) return false;
        if (isDateish($V)) return false;

        if (ctype_digit($V) && strlen($V) >= 7 && !isset($labelSet[$V])) return false;

        if (!genericStrongShape($V)) return false;

        return true;
    }));

    return array_merge($docTokens, $snTokens);
}

/* =====================  TEXTE DU TICKET (pour as_json)  ===================== */

function getTicketTextUltra(int $ticketId): string {
    global $DB;
    $ticketId = (int)$ticketId;

    $parts = [];

    $res = $DB->doQuery("SELECT name, content FROM glpi_tickets WHERE id = $ticketId");
    if ($res && $row = $DB->fetchassoc($res)) {
        $parts[] = (string)($row['name'] ?? '');
        $parts[] = (string)($row['content'] ?? '');
    }

    $resF = $DB->doQuery("
        SELECT content
        FROM glpi_itilfollowups
        WHERE items_id = $ticketId AND itemtype = 'Ticket'
        ORDER BY id DESC
        LIMIT 50
    ");
    if ($resF) {
        while ($fr = $DB->fetchassoc($resF)) {
            $parts[] = (string)($fr['content'] ?? '');
        }
    }

    $text = implode("\n\n", array_filter($parts, fn($s)=> is_string($s) && trim($s) !== ''));
    return $text;
}

/* ======================================================================
   ========================  TON FLUX EXISTANT  =========================
   ====================================================================== */

$Ticket_id = (int)($_GET['ticket_id'] ?? 0);

if ($Ticket_id <= 0) {
    echo json_encode([]);
    exit;
}

$result = $DB->doQuery("
   SELECT
      t.content AS ticket_content,
      tt.content AS task_content,
      f.content AS followup_content,
      tv.comment_submission AS validation_comment,
      s.content AS solution_content
   FROM glpi_tickets t
   LEFT JOIN glpi_tickettasks tt ON t.id = tt.tickets_id
   LEFT JOIN glpi_itilfollowups f ON t.id = f.items_id
   LEFT JOIN glpi_ticketvalidations tv ON t.id = tv.tickets_id
   LEFT JOIN glpi_itilsolutions s ON s.items_id = t.id AND s.itemtype = 'Ticket'
   WHERE t.id = $Ticket_id
");

$all_text = '';
while ($row = $DB->fetchassoc($result)) {
   foreach (['ticket_content', 'task_content', 'followup_content', 'validation_comment', 'solution_content'] as $field) {
      if (!empty($row[$field])) {
            $all_text .= "\n" . $row[$field];
      }
   }
}

// Détection
$liste = findSerialNumbers($all_text);
$liste = array_map(function($s) {
    return preg_replace('/[^A-Za-z0-9]/', '', $s);
}, $liste);

$resultats = [];
$warnings  = [];

require_once PLUGIN_WARRANTYCHECK_DIR . '/front/warranty_functions.php';
$config = new PluginWarrantycheckConfig();
$userid = Session::getLoginUserID();
$result = $DB->doQuery("SELECT * FROM `glpi_plugin_warrantycheck_preferences` WHERE users_id = $userid")->fetch_object();
$statuswarranty = $result->statuswarranty;
$max = $result->maxserial;
$viewdoc = $result->viewdoc;

$BonLivraisonPrefixes = $config->Filtre_BonDeLivraison() ? _cfg_csv_to_array($config->Filtre_BonDeLivraison()) : [];
$DevisPrefixes        = $config->Filtre_Devis() ? _cfg_csv_to_array($config->Filtre_Devis()) : [];
$FacturePrefixes      = $config->Filtre_Facture() ? _cfg_csv_to_array($config->Filtre_Facture()) : [];
$BonCommadePrefixes   = $config->Filtre_BonDeCommande() ? _cfg_csv_to_array($config->Filtre_BonDeCommande()) : [];

$brandPrefixes = [
    'Bon de commande : '  => $BonCommadePrefixes,
    'Bon de livraison : ' => $BonLivraisonPrefixes,
    'Facture : '          => $FacturePrefixes,
    'Devis : '            => $DevisPrefixes,
];

foreach ($liste as $serial) {

    $found = false;
    $nodoc = 1;
    $nodocconf = 1;
    $model = null;
    $label = '';

    // Docs (BC/BL/FA/DE)
    foreach ($brandPrefixes as $lab => $prefixes) {
        foreach ($prefixes as $prefix) {
            $prefix = trim($prefix);
            if ($prefix !== '' && stripos($serial, $prefix) === 0) {
                $found = true;
                $label = $lab;
                if($viewdoc == 0) $nodoc = 0;
                if($config->related_elements() == 0) $nodocconf = 0;
                break 2;
            }
        }
    }

    if($nodocconf == 1){
        if($statuswarranty === 1){
            $infos = detectBrand($serial, $Manufacturer = null);

            insertSurveyData([
                'tickets_id'    => $Ticket_id,
                'serial_number' => $infos['serial'] ?? $serial,
                'model'         => $infos['model'] ?? null,
                'fabricant'     => $infos['fabricant'] ?? null,
                'date_start'    => $infos['warranty_start'] ?? null,
                'date_end'      => $infos['warranty_end'] ?? null,
            ]);

            if (isset($infos) && is_array($infos) && array_key_exists('fabricant', $infos) && $infos['fabricant'] != null && $infos['info'] === 'serialnumber') {
                if (count($resultats) < $max) {
                    if ($nodoc == 1) {
                        $resultats[] = [
                            'serial' => $serial,
                            'fabricant' => $infos['fabricant'] ?? '',
                            'warranty_status' => $infos['warranty_status'] ?? '',
                            'info' => 'Numéro de serie : '
                        ];
                    }
                }
            }else{
                if (count($resultats) < $max) {
                    if (is_array($infos) && isset($infos['fabricant']) && in_array($infos['fabricant'], ['Bon de commande', 'Bon de livraison', 'Facture', 'Devis'])){
                        if ($nodoc == 1) {
                            $resultats[] = [
                                'serial' => $serial,
                                'info' => $infos['info'] ?? ''
                            ];
                        }
                    } else {
                        if ($nodoc == 1) {
                            $resultats[] = [
                                'serial' => $serial,
                                'warranty_status' => 'Inconnu ou API erreur',
                                'info' => 'Numéro de serie : '
                            ];
                        }
                    }
                }
            }
        }else{
            if (count($resultats) < $max) {
                if ($nodoc == 1) {
                    $resultat = ['serial' => $serial];
                }

                if (!$found) {
                    if ($nodoc == 1) {
                        $resultat['info'] = 'Numéro de série : ';
                    }

                    // ====== MODIF demandée : tenter d’ajouter 'fabricant' si préfixe correspond ======
                    $fabricant_guess = guessFabricantFromSerial($serial, $config);
                    $payload = [
                        'tickets_id'    => $Ticket_id,
                        'serial_number' => $serial,
                    ];
                    if ($fabricant_guess) {
                        $payload['fabricant'] = $fabricant_guess; // <<<<<<<<<<<<<<<<<<<<<< ajouté
                    }
                    insertSurveyData($payload);
                    // ===================================================================================

                }else{
                    if ($nodoc == 1) {
                        $resultat['info'] = $label;
                    }
                    if($config->related_elements() == 1){
                        if (rtrim($label, ' :') == 'Bon de commande') $model = 'BC' ?? null;
                        if (rtrim($label, ' :') == 'Bon de livraison') $model = 'BL' ?? null;
                        if (rtrim($label, ' :') == 'Facture') $model = 'FA' ?? null;
                        if (rtrim($label, ' :') == 'Devis') $model = 'DE' ?? null;
                    }
                    insertSurveyData([
                        'tickets_id'    => $Ticket_id,
                        'serial_number' => $serial,
                        'model'         => $model ?? null,
                        'fabricant'     => rtrim($label, ' :'),
                    ]);
                }
                if ($nodoc == 1) {
                    $resultats[] = $resultat;
                }
            }
        }
    }

    // ################################# BL / SAGE #################################
    if (!Plugin::isPluginActive('gestion')
        || $result->SageLocal != 1
        || $result->viewdoc != 1
        || strncasecmp($serial, 'BL', 2) !== 0) {
        continue;
    }

    $configGestion = new PluginGestionConfig();
    if ($configGestion->mode() != 1) {
        continue;
    }

    try {
        require_once PLUGIN_GESTION_DIR.'/vendor/autoload.php';
        require_once PLUGIN_GESTION_DIR.'/front/SageApi.php';

        $fields   = parseDocument($serial);
        $ticketId = $Ticket_id;

        $existingRows = iterator_to_array($DB->request([
            'SELECT' => ['id', 'tickets_id'],
            'FROM'   => 'glpi_plugin_gestion_surveys',
            'WHERE'  => ['url_bl' => $serial]
        ]));

        $conflicts = [];

        if (!empty($existingRows)) {
            foreach ($existingRows as $row) {
                $existingTicket = (int)$row['tickets_id'];
                $rowId          = (int)$row['id'];

                if ($existingTicket === $ticketId) {
                    continue;
                }

                if ($existingTicket === 0) {
                    $sql = "UPDATE glpi_plugin_gestion_surveys
                            SET tickets_id = ?
                            WHERE id = ?";
                    $stmt = $DB->prepare($sql);
                    $stmt->execute([$ticketId, $rowId]);

                    $warnings[] = "$serial a été mis à jour pour le ticket $ticketId.";
                    continue;
                }

                if ($existingTicket !== 0 && $existingTicket !== null) {
                    $conflicts[] = $existingTicket;
                }
            }

            if (!empty($conflicts)) {
                $warnings[] = "$serial attribué au(x) ticket(s) : " . implode(', ', $conflicts);
            }

        } else {
            $save      = 'Sage';
            $file_path = $serial.'_'.str_replace(' ', '_', $fields['client']);
            $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $fileUrl   = "{$protocol}://{$_SERVER['SERVER_NAME']}".PLUGIN_GESTION_WEBDIR.
                            "/ajax/view_pdf.php?id={$serial}";
            $itemUrl   = $serial;
            $tracker   = $fields['tracker'] ?? null;

            $entities_id = (int)$DB->request([
                'SELECT' => 'entities_id',
                'FROM'   => 'glpi_tickets',
                'WHERE'  => ['id' => $ticketId],
                'LIMIT'  => 1
            ])->current()['entities_id'];

            $sql  = "INSERT INTO glpi_plugin_gestion_surveys
                        (tickets_id, entities_id, url_bl, bl, doc_url, tracker, save)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $DB->prepare($sql);
            $stmt->execute([$ticketId, $entities_id, $itemUrl, $file_path,
                            $fileUrl,  $tracker,     $save]);

            $warnings[] = "$serial a été ajouté au ticket $ticketId.";
        }

    } catch (Throwable $e) {
        if (strpos($e->getMessage(), '(404)') !== false) {
            continue;
        }
        $warnings[] = "Erreur BL « $serial » : ".$e->getMessage();
        continue;
    }
}

$warnings = array_values(array_filter($warnings));

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok'        => true,
    'resultats' => $resultats,
    'warnings'  => $warnings
], JSON_UNESCAPED_UNICODE);
exit;

/* =======================  Endpoint as_json facultatif  ======================= */
/* (laisse tel quel si tu ne l’utilises pas)
if (isset($_GET['as_json']) && (int)$_GET['as_json'] === 1) {
    // ...
}
*/
