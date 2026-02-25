<?php
/**
 * Export CSV (UTF-8 + BOM ; séparateur ;)
 * GET: q, prefix(id|ticket|sn|''), exact(0|1)
 */

 // Anti-HTML parasite
ob_start();

$inc = __DIR__ . '/../../../inc/includes.php';
if (!file_exists($inc)) {
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  $buf = ob_get_clean();
  echo json_encode(['error'=>true,'message'=>'GLPI include not found','path'=>$inc,'debug_html'=>trim($buf)]);
  exit;
}
require $inc;

set_error_handler(function($no,$str,$file,$line){ throw new ErrorException($str,0,$no,$file,$line); });
set_exception_handler(function($e){
  http_response_code(500);
  $buf = ob_get_clean();
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error'=>true,'message'=>$e->getMessage(),'debug_html'=>mb_substr(strip_tags($buf),0,2000)]);
  exit;
});

try {
  Session::checkLoginUser();
  if (!Session::haveRight('config', UPDATE)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    $buf = ob_get_clean();
    echo json_encode(['error' => true, 'message' => 'Forbidden', 'debug_html' => trim($buf)]);
    exit;
  }
  if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
  @set_time_limit(0); @ignore_user_abort(true);

  global $DB;

  $searchRaw = trim((string)($_GET['q'] ?? ''));
  $prefix    = trim((string)($_GET['prefix'] ?? ''));
  $exact     = (int)($_GET['exact'] ?? 0);
  $prefix    = in_array($prefix, ['id', 'ticket', 'sn', ''], true) ? $prefix : '';
  if (mb_strlen($searchRaw, 'UTF-8') > 255) {
    $searchRaw = mb_substr($searchRaw, 0, 255, 'UTF-8');
  }

  $table = 'glpi_plugin_warrantycheck_tickets';
  $cols  = 'id, tickets_id, serial_number';

  $like = static function(string $s): string {
    return str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $s);
  };

  $where = '1=1';
  if ($searchRaw !== '') {
    $q       = mb_strtolower($searchRaw, 'UTF-8');
    $qLike   = $like($q);
    $qSql    = $DB->escape($q);
    $qLikeSql = $DB->escape($qLike);

    if ($prefix === 'id') {
      $where .= $exact ? " AND id = ".(int)$q : " AND CAST(id AS CHAR) LIKE '%{$qLikeSql}%'";
    } elseif ($prefix === 'ticket') {
      if ($exact) {
          $qInt = (int)$q;
          $qTok = (string)$qInt;
          $qTokSql = $DB->escape($qTok);
          $where .= " AND (tickets_id = '{$qTokSql}' OR FIND_IN_SET('{$qTokSql}', REPLACE(tickets_id,' ','')) > 0)";
      } else {
          $where .= " AND CAST(tickets_id AS CHAR) LIKE '%{$qLikeSql}%'";
      }
    } elseif ($prefix === 'sn') {
      $where .= $exact ? " AND LOWER(serial_number) = '{$qSql}'"
                       : " AND LOWER(serial_number) LIKE '%{$qLikeSql}%'";
    } else {
      $where .= " AND (
         LOWER(serial_number) LIKE '%{$qLikeSql}%'
         OR CAST(tickets_id AS CHAR) LIKE '%{$qLikeSql}%'
         OR CAST(id AS CHAR) LIKE '%{$qLikeSql}%'
      )";
    }
  }

  // On vide le buffer et on émet le CSV
  ob_end_clean();
  $ts = date('Ymd_His');
  $filename = "warranty_tickets_{$ts}.csv";
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  echo "\xEF\xBB\xBF"; // BOM

  $out = fopen('php://output', 'w');
  fputcsv($out, ['ID', 'Ticket ID', 'Serial Number'], ';');

  $sql = "SELECT {$cols} FROM `{$table}` WHERE {$where} ORDER BY id DESC";
  $res = $DB->doQuery($sql);
  if (!$res) { throw new RuntimeException('SELECT error: '.$DB->error()); }

  while ($row = $DB->fetchAssoc($res)) {
    fputcsv($out, [$row['id'] ?? '', $row['tickets_id'] ?? '', $row['serial_number'] ?? ''], ';');
  }
  fclose($out);
  exit;

} catch (Throwable $e) {
  throw $e;
}
