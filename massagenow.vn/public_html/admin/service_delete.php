<?php
declare(strict_types=1);
/** /public_html/admin/service_delete.php — delete service */
require __DIR__ . '/../../app/auth.php';
auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
if (!csrf_verify($_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }

$id = (int)($_POST['id'] ?? 0);
if (!$id) { header('Location: /admin/services.php'); exit; }

try {
  // delete i18n first (FK may not exist, but safe)
  db_exec("DELETE FROM service_i18n WHERE service_id=?", [$id]);
  db_exec("DELETE FROM services WHERE id=?", [$id]);
  header('Location: /admin/services.php');
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo "Không thể xoá: " . e($e->getMessage());
}
