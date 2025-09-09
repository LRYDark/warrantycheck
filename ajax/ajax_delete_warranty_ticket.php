<?php
/**
 * AJAX — Suppression de tickets de garantie (blindé contre sorties HTML)
 * IN  (POST): _glpi_csrf_token, ids[]=<int>...
 * OUT (JSON): { ok:true, requested:n, deleted:n } | { error:true, message:"...", debug_html:"..." }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// 1) Bufferise toute sortie (bannières debug, echoes perdus, etc.)
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

// 3) Après include: convertir toute erreur en exception, puis renvoyer JSON
set_error_handler(function($errno, $errstr, $errfile, $errline){
  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});
set_exception_handler(function($e){
  http_response_code(500);
  $buf = ob_get_clean();
  $out = ['error'=>true,'message'=>$e->getMessage()];
  if ($buf && trim($buf) !== '') {
    $out['debug_html'] = mb_substr(strip_tags($buf), 0, 2000);
  }
  echo json_encode($out);
  exit;
});

try {
  // Sécurité GLPI
  Session::checkLoginUser();
  Session::checkCSRF($_POST);

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $buf = ob_get_clean();
    echo json_encode(['error'=>true,'message'=>'Method Not Allowed','debug_html'=>trim($buf)]);
    exit;
  }

  $ids = $_POST['ids'] ?? [];
  if (!is_array($ids) || empty($ids)) {
    http_response_code(400);
    $buf = ob_get_clean();
    echo json_encode(['error'=>true,'message'=>'No ids','debug_html'=>trim($buf)]);
    exit;
  }

  // Sanitize → int uniques > 0
  $ids = array_values(array_unique(array_map('intval', $ids)));
  $ids = array_filter($ids, static fn($v) => $v > 0);
  if (empty($ids)) {
    http_response_code(400);
    $buf = ob_get_clean();
    echo json_encode(['error'=>true,'message'=>'No valid ids','debug_html'=>trim($buf)]);
    exit;
  }

  global $DB;
  $table = 'glpi_plugin_warrantycheck_tickets'; // adapte si besoin

  // Suppression en chunks (évite des IN trop gros)
  $chunkSize = 1000;
  $requested = count($ids);
  $deleted   = 0;

  foreach (array_chunk($ids, $chunkSize) as $chunk) {
    $in = implode(',', $chunk);

    // On peut supprimer directement; si tu veux compter précis avant, fais un SELECT id IN (...)
    $sqlDel = "DELETE FROM `{$table}` WHERE id IN ($in)";
    if (!$DB->query($sqlDel)) {
      throw new RuntimeException('DELETE error: '.$DB->error());
    }
    // Compte “supposé” supprimé pour ce chunk
    $deleted += count($chunk);
  }

  // Succès — renvoie aussi un éventuel HTML tamponné pour debug si présent (ne devrait pas)
  $buf = ob_get_clean();
  $out = ['ok'=>true, 'requested'=>$requested, 'deleted'=>$deleted];
  if ($buf && trim($buf) !== '') {
    $out['debug_html'] = mb_substr(strip_tags($buf), 0, 2000);
  }
  echo json_encode($out);
  exit;

} catch (Throwable $e) {
  // Géré par set_exception_handler
  throw $e;
}
