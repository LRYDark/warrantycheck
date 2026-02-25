<?php
/**
 * AJAX — Liste paginée + recherche (GLPI) — JSON only & blindé
 * GET: page, pageSize, q, prefix(id|ticket|sn|''), exact(0|1), diag(0|1)
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// 1) Bufferise toute sortie (pour ne pas envoyer d'HTML par erreur)
ob_start();

// 2) Bootstrap GLPI
$inc = __DIR__ . '/../../../inc/includes.php';
if (!file_exists($inc)) {
  http_response_code(500);
  $buf = ob_get_clean();
  echo json_encode(['error'=>true,'message'=>'GLPI include not found','path'=>$inc,'debug_html'=>trim($buf)]);
  exit;
}
require $inc;

// 3) Convertit TOUTE erreur/notice en exception → on renvoie JSON propre
set_error_handler(function($no,$str,$file,$line){ throw new ErrorException($str,0,$no,$file,$line); });
set_exception_handler(function($e){
  http_response_code(500);
  $buf = ob_get_clean();
  echo json_encode([
    'error'      => true,
    'message'    => $e->getMessage(),
    'debug_html' => $buf ? mb_substr(strip_tags($buf),0,2000) : null
  ]);
  exit;
});

try {
  Session::checkLoginUser(); // évite la redirection HTML vers login
  if (!Session::haveRight('config', UPDATE)) {
    http_response_code(403);
    $buf = ob_get_clean();
    echo json_encode(['error' => true, 'message' => 'Forbidden', 'debug_html' => trim($buf)]);
    exit;
  }
  global $DB;

  // Mode diagnostic rapide si besoin
  if (!empty($_GET['diag'])) {
    $buf = ob_get_clean();
    echo json_encode([
      'ok'       => true,
      'user_id'  => (int)Session::getLoginUserID(),
      'cwd'      => getcwd(),
      'dir'      => __DIR__,
      'debug_html' => $buf ? mb_substr(strip_tags($buf),0,2000) : null
    ]);
    exit;
  }

  // ---- Params
  $page      = max(1, (int)($_GET['page'] ?? 1));
  $pageSize  = min(200, max(10, (int)($_GET['pageSize'] ?? 50)));
  $searchRaw = trim((string)($_GET['q'] ?? ''));
  $prefix    = trim((string)($_GET['prefix'] ?? ''));  // id|ticket|sn|''
  $exact     = (int)($_GET['exact'] ?? 0);
  $prefix    = in_array($prefix, ['id', 'ticket', 'sn', ''], true) ? $prefix : '';
  if (mb_strlen($searchRaw, 'UTF-8') > 255) {
    $searchRaw = mb_substr($searchRaw, 0, 255, 'UTF-8');
  }
  $offset    = ($page - 1) * $pageSize;

  $table = 'glpi_plugin_warrantycheck_tickets';
  $cols  = 'id, tickets_id, serial_number';

  // Helper LIKE (échappe \ % _)
  $like = static function(string $s): string {
    return str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $s);
  };

  // ---- WHERE
  $where = '1=1';
  if ($searchRaw !== '') {
    $q      = mb_strtolower($searchRaw, 'UTF-8');
    $qLike  = $like($q);
    $qSql   = $DB->escape($q);
    $qLikeSql = $DB->escape($qLike);

    if ($prefix === 'id') {
      $where .= $exact
        ? " AND id = ".(int)$q
        : " AND CAST(id AS CHAR) LIKE '%{$qLikeSql}%'";
    } elseif ($prefix === 'ticket') {
      if ($exact) {
        $qInt = (int)$q;          // on normalise le token en entier
        $qTok = (string)$qInt;    // puis on compare en CHAÎNE pour éviter les casts DECIMAL
        // tickets_id = '123' OU liste CSV contenant '123' (espaces tolérés)
        $qTokSql = $DB->escape($qTok);
        $where .= " AND (tickets_id = '{$qTokSql}' OR FIND_IN_SET('{$qTokSql}', REPLACE(tickets_id,' ','')) > 0)";
      } else {
        $where .= " AND CAST(tickets_id AS CHAR) LIKE '%{$qLikeSql}%'";
      }
    } elseif ($prefix === 'sn') {
      $where .= $exact
        ? " AND LOWER(serial_number) = '{$qSql}'"
        : " AND LOWER(serial_number) LIKE '%{$qLikeSql}%'";
    } else {
      $where .= " AND (
         LOWER(serial_number) LIKE '%{$qLikeSql}%'
         OR CAST(tickets_id AS CHAR) LIKE '%{$qLikeSql}%'
         OR CAST(id AS CHAR) LIKE '%{$qLikeSql}%'
      )";
    }
  }

  // ---- COUNT
  $r = $DB->doQuery("SELECT COUNT(*) AS total FROM `{$table}` WHERE {$where}");
  if (!$r) { throw new RuntimeException('COUNT error: '.$DB->error()); }
  $row   = $DB->fetchAssoc($r);
  $total = (int)($row['total'] ?? 0);

  // ---- LIST
  $limit = (int)$pageSize;
  $offs  = (int)$offset;
  $sql   = "SELECT {$cols} FROM `{$table}` WHERE {$where} ORDER BY id DESC LIMIT {$limit} OFFSET {$offs}";
  $r = $DB->doQuery($sql);
  if (!$r) { throw new RuntimeException('SELECT error: '.$DB->error()); }

  $rows = [];
  while ($rec = $DB->fetchAssoc($r)) { $rows[] = $rec; }

  // ---- OK
  $buf = ob_get_clean();
  $out = ['page'=>$page,'pageSize'=>$pageSize,'total'=>$total,'rows'=>$rows];
  if ($buf && trim($buf) !== '') { $out['debug_html'] = mb_substr(strip_tags($buf),0,2000); }
  echo json_encode($out);
  exit;

} catch (Throwable $e) {
  // Recatch → handler global renvoie JSON
  throw $e;
}
