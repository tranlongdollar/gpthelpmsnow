<?php
declare(strict_types=1);
/**
 * /public_html/admin/page_massageteam_save.php
 * Lưu các field của page.massageteam theo từng ngôn ngữ
 */
require __DIR__ . '/../../app/auth.php';
auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
if (!csrf_verify($_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }

$pageKey = trim($_POST['page_key'] ?? '');
$texts   = $_POST['text'] ?? []; // [lang][key] => value

if ($pageKey === '' || !is_array($texts)) { http_response_code(400); exit('Thiếu dữ liệu'); }

try {
  foreach ($texts as $langCode => $kv) {
    foreach ($kv as $k => $v) {
      $html = (string)$v;
      // Có thể cho phép lưu rỗng: nếu rỗng thì xoá record để fallback sang default
      if ($html === '') {
        db_exec("DELETE FROM i18n_texts WHERE page_key=? AND text_key=? AND lang_code=?", [$pageKey,$k,$langCode]);
        continue;
      }
      db_exec("INSERT INTO i18n_texts (page_key, text_key, lang_code, text_html)
               VALUES (?,?,?,?)
               ON DUPLICATE KEY UPDATE text_html=VALUES(text_html)",
               [$pageKey, $k, $langCode, $html]);
    }
  }
  header('Location: /admin/page_massageteam.php');
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo 'Lỗi: '.e($e->getMessage());
}
