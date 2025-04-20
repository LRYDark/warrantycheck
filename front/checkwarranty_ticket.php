<?php
include('../../../inc/includes.php');
Session::checkLoginUser();

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

ob_clean(); // Vide tout ce qui a pu être envoyé avant

function findSerialNumbers(string $text): array {
    global $DB, $CFG_GLPI;
    $config = new PluginWarrantycheckConfig();

    // ✅ Blacklist enrichie (à stocker dans un champ ou fichier si besoin)
    $blacklist_row = $config->blacklist();

    // 🧠 Conversion en blacklist dynamique
    $dynamic_blacklist = array_filter(array_map('trim', explode(',', strtolower($blacklist_row))));
    $dynamic_blacklist = array_flip($dynamic_blacklist); // Accès rapide
    
    // Liste des attributs à supprimer
    $attributes = ['src', 'style', 'onclick', 'onerror', 'onload', 'onmouseover', 'onfocus', 'onblur'];

    foreach ($attributes as $attr) {
        $text = preg_replace(
            '/\s*\b' . preg_quote($attr, '/') . '\s*=\s*(?:(["\']).*?\1|[^\s>]+)/i',
            '',
            $text
        );
    }

    $text = preg_replace('#<(img|svg|meta|link|iframe|noscript)[^>]*?>#is', '', $text); // Suppression des images et autres balises
    $text = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $text); // Supprime les balises <script>, <style>, <iframe>, <object>, <embed> 
    $text = preg_replace('#<img\b[^>]*>#i', '', $text); // Supprime les balises <img> (autofermées ou non)

    // 🧼 Nettoyage HTML
    $text = preg_replace('/<br\s*\/?>/i', ' ', $text);
    $text = preg_replace('/<\/?(p|div|h\d|strong|span|em|b|i|u)>/i', ' ', $text);
    $text = strip_tags($text);

    $text = preg_replace('/\s+/', ' ', $text); // Suppression des espaces multiples
    $text = preg_replace('/\s*([.,;:!?()])\s*/', '$1 ', $text); // Normalisation des espaces autour de la ponctuation
    $text = preg_replace('/\s+/', ' ', $text); // Suppression des espaces multiples
    $text = preg_replace('/\s*([.,;:!?()])\s*/', '$1 ', $text); // Normalisation des espaces autour de la ponctuation

    // 🧼 Nettoyage des entités HTML
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');// Suppression des entités HTML
    $text = trim($text);// Suppression des espaces en début et fin de chaîne
    $text = html_entity_decode($text);// Suppression des entités HTML
    $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');// Suppression des entités HTML

    // 🔠 Séparation des mots
    $words = preg_split('/[^A-Z0-9\-]+/i', $text);
    $results = [];

    // 🧠 Indices sémantiques
    $hints = ['numéro de série', 'numero de serie', 'n° de série', 'serial', 'serial number', 's/n', 'sn', 'seriennummer', 'N/S', 'S/N', 'SN' , 'NUMERO DE SERIE', 'sérial number'];
    $soft_hints = ['et', 'ainsi que', 'plus', 'also', 'and', 'auch'];

    foreach ($words as $i => $word) {
        $word = strtoupper(trim($word));
        $word_lc = strtolower($word);

        // 🛑 Règles d’exclusion
        if (strlen($word) < 6 || strlen($word) > 20) continue;
        if (isset($dynamic_blacklist[$word_lc])) continue;
        if (!preg_match('/[A-Z]/', $word) || !preg_match('/\d/', $word)) continue;
        if (substr_count($word, '-') >= 2) continue;
        if (preg_match('/^\d{4,6}H\d{2}$/', $word)) continue;
        if (preg_match('/^\d{6,}$/', $word)) continue;
        if (preg_match('/^[A-Z]{6,}$/', $word)) continue;

        // 🔍 Contexte autour
        $score = 0;
        $context = implode(' ', array_slice($words, max(0, $i - 5), 10));

        foreach ($hints as $hint) {
            if (stripos($context, $hint) !== false) {
                $score += 100;
                break;
            }
        }
        foreach ($soft_hints as $soft) {
            if (stripos($context, $soft) !== false) {
                $score += 25;
                break;
            }
        }

        // 🎯 Bonus par forme
        if (preg_match('/^[A-Z]{2,5}\d{4,10}$/', $word)) $score += 25;
        if (preg_match('/^\d{4,8}[A-Z]{1,4}$/', $word)) $score += 25;
        if (preg_match('/^[A-Z0-9\-]{7,14}$/', $word)) $score += 15;
        if (preg_match('/^[A-Z0-9]{6,}$/', $word)) $score += 5;
        if (strpos($word, '-') !== false) $score += 5;
        if ($word === strtoupper($word)) $score += 5;

        // IA : rattrapage si motif probable
        if ($score < 20 && preg_match('/^[A-Z0-9\-]{7,14}$/', $word)) {
            $score = 20;
        }

        if ($score >= 20) {
            $results[$word] = $score;
        }
    }

    arsort($results);
    return array_keys($results);
}

$Ticket_id = (int)($_GET['ticket_id'] ?? 0);

if ($Ticket_id <= 0) {
    echo json_encode([]);
    exit;
}

$result = $DB->query("
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

// Appel de notre détection IA
$liste = findSerialNumbers($all_text);
$liste = array_map(function($s) {
    return preg_replace('/[^A-Za-z0-9]/', '', $s); // ne garde que lettres et chiffres
}, $liste);

$resultats = [];
require_once PLUGIN_WARRANTYCHECK_DIR . '/front/warranty_functions.php';
$config = new PluginWarrantycheckConfig();
$userid = Session::getLoginUserID();
$result = $DB->query("SELECT * FROM `glpi_plugin_warrantycheck_preferences` WHERE users_id = $userid")->fetch_object();
$statuswarranty = $result->statuswarranty;
$max = $result->maxserial; // Exemple : l'utilisateur définit la limite à 20
$viewdoc = $result->viewdoc; // Exemple : l'utilisateur définit la limite à 20
$resultats = [];

foreach ($liste as $serial) {

    $BonLivraisonPrefixes = $config->Filtre_BonDeLivraison() ? explode(',', $config->Filtre_BonDeLivraison()) : [];
    $DevisPrefixes = $config->Filtre_Devis() ? explode(',', $config->Filtre_Devis()) : [];
    $FacturePrefixes = $config->Filtre_Facture() ? explode(',', $config->Filtre_Facture()) : [];
    $BonCommadePrefixes = $config->Filtre_BonDeCommande() ? explode(',', $config->Filtre_BonDeCommande()) : [];

    // Tableau des préfixes associés à chaque constructeur
    $brandPrefixes = [
        'Bon de commande : '  => $BonCommadePrefixes,
        'Bon de livraison : ' => $BonLivraisonPrefixes,
        'Facture : '          => $FacturePrefixes,
        'Devis : '            => $DevisPrefixes,
    ];

    $found = false;
    $nodoc = 1;
    $nodocconf = 1;
    $model = null;

    foreach ($brandPrefixes as $label => $prefixes) {
        foreach ($prefixes as $prefix) {
            if (stripos($serial, trim($prefix)) === 0) {
                $found = true;
                if($viewdoc == 0) $nodoc = 0; // On a trouvé un numéro de série qui correspond à un préfixe
                if($config->related_elements() == 0) $nodocconf = 0;
                break 2; // On sort dès qu'on trouve
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
                    insertSurveyData([
                        'tickets_id'    => $Ticket_id,
                        'serial_number' => $serial,
                    ]);
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
}

header('Content-Type: application/json');
echo json_encode($resultats);

