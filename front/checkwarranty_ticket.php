<?php
include('../../../inc/includes.php');
Session::checkLoginUser();

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

function findSerialNumbers(string $text): array {
    global $DB, $CFG_GLPI;
    $config = new PluginWarrantycheckConfig();

    // ✅ Blacklist enrichie (à stocker dans un champ ou fichier si besoin)
    $blacklist_row = $config->blacklist();

    // 🧠 Conversion en blacklist dynamique
    $dynamic_blacklist = array_filter(array_map('trim', explode(',', strtolower($blacklist_row))));
    $dynamic_blacklist = array_flip($dynamic_blacklist); // Accès rapide

    // 🧼 Nettoyage HTML
    $text = preg_replace('/<br\s*\/?>/i', ' ', $text);
    $text = preg_replace('/<\/?(p|div|h\d|strong|span|em|b|i|u)>/i', ' ', $text);
    $text = strip_tags($text);
    $text = html_entity_decode($text);
    $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

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
      f.content AS followup_content
   FROM glpi_tickets t
   LEFT JOIN glpi_tickettasks tt ON t.id = tt.tickets_id
   LEFT JOIN glpi_itilfollowups f ON t.id = f.items_id
   WHERE t.id = $Ticket_id
");

$all_text = '';
while ($row = $DB->fetchassoc($result)) {
   foreach (['ticket_content', 'task_content', 'followup_content'] as $field) {
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
$group   = new PluginWarrantycheckPreference();
$userid = Session::getLoginUserID();
$result = $DB->query("SELECT * FROM `glpi_plugin_warrantycheck_preferences` WHERE users_id = $userid")->fetch_object();
$statuswarranty = $result->statuswarranty;

foreach ($liste as $serial) {

    if($statuswarranty === 1){
        $infos = detectBrand($serial, $Manufacturer = null);

        if (isset($infos) && is_array($infos) && isset($infos['fabricant'], $infos['warranty_start'], $infos['warranty_end'], $infos['serial'])) {
            insertSurveyData([
                'tickets_id'    => $Ticket_id,
                'serial_number' => $infos['serial'],
                'model'         => $infos['model'] ?? '',
                'fabricant'     => $infos['fabricant'],
                'date_start'    => $infos['warranty_start'],
                'date_end'      => $infos['warranty_end'],
            ]);
        }
    
        if (isset($infos) && is_array($infos) && array_key_exists('fabricant', $infos) && $infos['fabricant'] != null){
            $resultats[] = [
                'serial' => $serial,
                'fabricant' => $infos['fabricant'] ?? '',
                'warranty_status' => $infos['warranty_status'] ?? '',
                'debug' => $infos // facultatif si tu veux tout retourner
            ];
        }else{
            $resultats[] = [
                'serial' => $serial,
                'warranty_status' => 'Inconnu ou API erreur',
                'debug' => $infos // facultatif si tu veux tout retourner
            ];
        }
    }else{
        insertSurveyData([
            'tickets_id'    => $Ticket_id,
            'serial_number' => $serial,
        ]);

        $resultats[] = [
            'serial' => $serial,
        ];
    }

}

header('Content-Type: application/json');
echo json_encode($resultats);

