<?php
/**
 * AJAX — Liste paginée + recherche (GLPI) — blindée contre HTML de debug
 * GET: page, pageSize, q, prefix(id|ticket|sn|''), exact(0|1), diag(0|1)
 * JSON: { page, pageSize, total, rows:[...] } | { error:true, message:"...", debug_html:"..." }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// 1) Bufferise TOUTE sortie (bannières HTML, notices, echo perdus, etc.)
ob_start();

// 2) Bootstrap GLPI (chemin relatif depuis plugins/warrantycheck/ajax/)
$inc = __DIR__ . '/../../../inc/includes.php';
if (!file_exists($inc)) {
  http_response_code(500);
  $buf = ob_get_clean();
  echo json_encode(['error'=>true,'message'=>'GLPI include not found','path'=>$inc,'debug_html'=>trim($buf)]);
  exit;
}
require $inc;

// 3) Après include: redirige TOUTE erreur PHP vers une exception (plus de HTML)
set_error_handler(function($errno, $errstr, $errfile, $errline) {
  // Convertit NOTICE/WARNING en exception pour capturer en JSON
  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});
set_exception_handler(function($e){
  http_response_code(500);
  $buf = ob_get_clean(); // Récupère toute sortie HTML/texte
  $out = [
    'error'   => true,
    'message' => $e->getMessage(),
  ];
  if ($buf && trim($buf) !== '') {
    // On envoie un extrait nettoyé pour diagnostic (évite de casser la modale)
    $out['debug_html'] = mb_substr(strip_tags($buf), 0, 2000);
  }
  echo json_encode($out);
  exit;
});

try {
  // 4) Sécurité GLPI (évite redirection HTML vers login)
  Session::checkLoginUser();
  global $DB;

  // Mode diag simple
  if (!empty($_GET['diag'])) {
    $buf = ob_get_clean();
    $payload = [
      'ok'      => true,
      'user_id' => (int)Session::getLoginUserID(),
      'cwd'     => getcwd(),
      'dir'     => __DIR__,
    ];
    if ($buf && trim($buf) !== '') {
      $payload['debug_html'] = mb_substr(strip_tags($buf), 0, 2000);
    }
    echo json_encode($payload);
    exit;
  }

  // 5) Paramètres
  $page      = max(1, (int)($_GET['page'] ?? 1));
  $pageSize  = min(200, max(10, (int)($_GET['pageSize'] ?? 50)));
  $searchRaw = trim((string)($_GET['q'] ?? ''));
  $prefix    = trim((string)($_GET['prefix'] ?? '')); // id|ticket|sn|''
  $exact     = (int)($_GET['exact'] ?? 0);
  $offset    = ($page - 1) * $pageSize;

  // 6) Table/Colonnes (adapte si besoin)
  $table = 'glpi_plugin_warrantycheck_tickets';
  $cols  = 'id, tickets_id, serial_number';

  // 7) WHERE SAFE
  $where = '1=1';
  if ($searchRaw !== '') {
    $q     = mb_strtolower($searchRaw, 'UTF-8');
    // échappe % _ \ pour LIKE
    $qLike = str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $q);

    if ($prefix === 'id') {
      $where .= $exact ? " AND id = ".(int)$q
                       : " AND CAST(id AS CHAR) LIKE '%{$qLike}%'";
    } else if ($prefix === 'ticket') {
      $where .= $exact ? " AND tickets_id = ".(int)$q
                       : " AND CAST(tickets_id AS CHAR) LIKE '%{$qLike}%'";
    } else if ($prefix === 'sn') {
      // Si tu as serial_number_lc indexé, remplace par: serial_number_lc LIKE '%{$qLike}%'
      $where .= $exact ? " AND LOWER(serial_number) = '{$q}'"
                       : " AND LOWER(serial_number) LIKE '%{$qLike}%' ESCAPE '\\\\'";
    } else {
      $where .= " AND (
           LOWER(serial_number) LIKE '%{$qLike}%' ESCAPE '\\\\'
        OR CAST(tickets_id AS CHAR) LIKE '%{$qLike}%'
        OR CAST(id AS CHAR) LIKE '%{$qLike}%'
      )";
    }
  }

  // 8) COUNT
  $countSql = "SELECT COUNT(*) AS total FROM `{$table}` WHERE {$where}";
  $r = $DB->query($countSql);
  if (!$r) { throw new RuntimeException('COUNT error: '.$DB->error()); }
  $row = $DB->fetchAssoc($r);
  $total = (int)($row['total'] ?? 0);

  // 9) LIST
  $limit = (int)$pageSize;
  $offs  = (int)$offset;
  $listSql = "SELECT {$cols} FROM `{$table}` WHERE {$where} ORDER BY id DESC LIMIT {$limit} OFFSET {$offs}";
  $r = $DB->query($listSql);
  if (!$r) { throw new RuntimeException('SELECT error: '.$DB->error()); }

  $rows = [];
  while ($rec = $DB->fetchAssoc($r)) {
    $rows[] = $rec;
  }

  // 10) Succès: on vide le buffer éventuel => si quelque chose reste, on le remonte en debug_html
  $buf = ob_get_clean();
  $payload = [
    'page'     => $page,
    'pageSize' => $pageSize,
    'total'    => $total,
    'rows'     => $rows
  ];
  if ($buf && trim($buf) !== '') {
    $payload['debug_html'] = mb_substr(strip_tags($buf), 0, 2000);
  }
  echo json_encode($payload);
  exit;

} catch (Throwable $e) {
  // Sera attrapé par set_exception_handler ci-dessus
  throw $e;
}
