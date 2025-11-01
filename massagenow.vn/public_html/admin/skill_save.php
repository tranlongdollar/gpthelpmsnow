<?php
declare(strict_types=1);
require __DIR__ . '/../../app/auth.php';
auth_require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
if (!csrf_verify($_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }
$staffId = (int)($_POST['staff_id'] ?? 0);
$skillId = (int)($_POST['skill_id'] ?? 0);
$percent = max(0, min(100, (int)($_POST['percent'] ?? 0)));
$order   = (int)($_POST['order_no'] ?? 100);
$labels  = $_POST['label'] ?? [];
if ($staffId <= 0) { http_response_code(400); exit('Thiếu staff_id'); }
try {
  if ($skillId) { db_exec("UPDATE staff_skills SET percent=?, order_no=? WHERE id=?", [$percent,$order,$skillId]); }
  else { $skillId=(int)db_insert("INSERT INTO staff_skills (staff_id, percent, order_no) VALUES (?,?,?)", [$staffId,$percent,$order]); }
  foreach ($labels as $code=>$text) {
    $label = trim((string)$text); if ($label==='') continue;
    db_exec("INSERT INTO staff_skill_i18n (skill_id, lang_code, label) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE label=VALUES(label)", [$skillId,$code,$label]);
  }
  header('Location: /admin/staff_edit.php?id='.$staffId); exit;
} catch (Throwable $e) { http_response_code(500); echo 'Lỗi: '.e($e->getMessage()); }
