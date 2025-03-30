<?php
include('../../../inc/includes.php');
Session::checkLoginUser();

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

function findSerialNumbers(string $text): array {
    // 🔧 Nettoyage HTML
    $text = preg_replace('/<br\s*\/?>/i', ' ', $text);
    $text = preg_replace('/<\/?(p|div|h\d|strong|span|em|b|i|u)>/i', ' ', $text);
    $text = strip_tags($text);
    $text = html_entity_decode($text);
    $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

    // 🔠 Découpe des mots
    $words = preg_split('/[^A-Z0-9\-]+/i', $text);
    $results = [];

    // ❌ Blacklist multilingue
    $blacklist = [
        // FR
        'client','prise','accessoire','sauvegarde','informations','données',
        'bonjour','merci','reçu','ticket','machine','produit','test',
        'numéro','serie','glpi','portable','ref','support','urgent',
        'non','oui','ordinateur','imprimante',
        // EN
        'hello','thanks','product','reference','model','support',
        'accessory','backup','data','issue','pc','laptop','desktop',
        'screen','number','serial','information','yes','no',
        // DE
        'kunde','danke','modell','gerät','sichern','daten','anhang',
        'unterstützung','nummer','informationen','problem',
        'ja','nein'
    ];

    // 🔍 Mots-clés explicites
    $hints = [
        'numéro de série','numero de serie','n° de série','n° série',
        'serial','serial number','s/n','sn','seriennummer'
    ];

    // 🔍 Mots de liaison
    $soft_hints = ['et','ainsi que','plus','also','and','auch'];

    // 🔍 Analyse intelligente mot par mot
    for ($i = 0; $i < count($words); $i++) {
        $word = strtoupper(trim($words[$i]));

        if (
            strlen($word) < 6 || strlen($word) > 20 ||
            in_array(strtolower($word), $blacklist)
        ) continue;

        // Exclure noms machines typiques
        if (preg_match('/^[A-Z]{2,5}-[A-Z0-9]{2,6}(-[A-Z0-9]{1,4})?$/', $word)) continue;

        // Doit contenir au moins une lettre et un chiffre
        if (!preg_match('/[A-Z]/', $word) || !preg_match('/\d/', $word)) continue;

        $score = 0;
        $context = implode(' ', array_slice($words, max(0, $i - 5), 10));

        foreach ($hints as $hint) {
            if (stripos($context, $hint) !== false) {
                $score += 100;
                break;
            }
        }

        foreach ($soft_hints as $hint) {
            if (stripos($context, $hint) !== false) {
                $score += 25;
                break;
            }
        }

        // 🧩 Bonus forme
        if (strlen($word) >= 8 && strlen($word) <= 12) $score += 10;
        if (preg_match('/^[A-Z]{2,5}\d{4,10}$/', $word)) $score += 15;
        if (preg_match('/^\d{4,8}[A-Z]{1,3}$/', $word)) $score += 15;
        if (preg_match('/^[A-Z0-9]{6,}$/', $word)) $score += 5;
        if (strpos($word, '-') !== false) $score += 5;
        if ($word === strtoupper($word)) $score += 5;

        if ($score < 15 && preg_match('/^[A-Z0-9]{6,12}$/', $word)) {
            $score = 15;
        }

        if ($score >= 15) {
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
$resultats[] = [
    'serial' => $liste
];

$resultats = [];
require_once PLUGIN_WARRANTYCHECK_DIR . '/front/warranty_functions.php';

foreach ($liste as $serial) {
   $infos = detectBrand($serial, $Manufacturer = null);
   if (isset($infos) && is_array($infos) && array_key_exists('fabricant', $infos) && $infos['fabricant'] != null){
        $resultats[] = [
            'serial' => $serial,
            'fabricant' => $infos['fabricant'] ?? '',
            'warranty_status' => $infos['warranty_status'] ?? ''
        ];
   }else{
        $resultats[] = [
            'serial' => $serial,
            'warranty_status' => 'Inconnu'
        ];
   }
}

header('Content-Type: application/json');
echo json_encode($resultats);