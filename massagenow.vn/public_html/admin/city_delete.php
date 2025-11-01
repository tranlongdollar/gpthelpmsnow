<?php
declare(strict_types=1);
/** /public_html/admin/city_delete.php — delete city */
require __DIR__ . '/../../app/auth.php';
auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
if (!csrf_verify($_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }

$id = (int)($_POST['id'] ?? 0);
if (!$id) { header('Location: /admin/cities.php'); exit; }

try {
  db_exec("DELETE FROM cities WHERE id=?", [$id]);
  header('Location: /admin/cities.php');
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo "Không thể xoá: " . e($e->getMessage());
}
