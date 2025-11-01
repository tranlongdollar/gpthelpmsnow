<?php
declare(strict_types=1);
/** /public_html/admin/translation_save.php — insert/update nhiều bản dịch */
require __DIR__ . '/../../app/auth.php';
auth_require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
if (!csrf_verify($_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }

$page = trim($_POST['page'] ?? '');
if ($page === '') { http_response_code(400); exit('Thiếu page'); }

try {
  if (isset($_POST['text_key'])) {
    // Thêm 1 key mới với 1 ngôn ngữ
    $key  = trim($_POST['text_key'] ?? '');
    $code = trim($_POST['lang_code'] ?? '');
    $html = (string)($_POST['text_html'] ?? '');
    if ($key === '' || $code === '') throw new Exception('Thiếu text_key hoặc lang_code');
    db_exec("INSERT INTO i18n_texts (page_key, text_key, lang_code, text_html) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE text_html=VALUES(text_html)", [$page, $key, $code, $html]);
  } else {
    // Cập nhật hàng loạt từ form bảng
    $texts = $_POST['text'] ?? []; // [text_key][lang_code] => text_html
    foreach ($texts as $key => $byLang) {
      foreach ($byLang as $code => $html) {
        $html = (string)$html;
        if ($html === '') continue; // cho trống = bỏ qua (không xoá record)
        db_exec("INSERT INTO i18n_texts (page_key, text_key, lang_code, text_html) VALUES (?,?,?,?)
                 ON DUPLICATE KEY UPDATE text_html=VALUES(text_html)",
                [$page, $key, $code, $html]);
      }
    }
  }
  header('Location: /admin/translations.php?page=' . urlencode($page));
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo 'Lỗi: ' . e($e->getMessage());
}
