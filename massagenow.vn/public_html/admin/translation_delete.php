<?php
declare(strict_types=1);
/** /public_html/admin/translation_delete.php — xoá toàn bộ bản dịch của 1 key trên mọi ngôn ngữ */
require __DIR__ . '/../../app/auth.php';
auth_require_login();

$page = trim($_GET['page'] ?? '');
$key  = trim($_GET['key'] ?? '');
if ($page === '' || $key === '') { header('Location: /admin/translations.php'); exit; }

try {
  db_exec("DELETE FROM i18n_texts WHERE page_key=? AND text_key=?", [$page, $key]);
  header('Location: /admin/translations.php?page=' . urlencode($page));
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo 'Không thể xoá: ' . e($e->getMessage());
}
