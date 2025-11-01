<?php
declare(strict_types=1);
require __DIR__ . '/../../app/auth.php';
auth_require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
if (!csrf_verify($_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }
$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$photo= trim($_POST['photo_url'] ?? '');
$order= (int)($_POST['order_no'] ?? 100);
$active=(int)($_POST['active'] ?? 1);
$names   = $_POST['name'] ?? [];
$titles  = $_POST['title'] ?? [];
$taglines= $_POST['tagline'] ?? [];
try {
  if ($id) { db_exec("UPDATE staff SET photo_url=?, order_no=?, active=? WHERE id=?", [$photo,$order,$active,$id]); $staffId=$id; }
  else { $staffId=(int)db_insert("INSERT INTO staff (photo_url, order_no, active) VALUES (?,?,?)", [$photo,$order,$active]); }
  foreach ($names as $code=>$nm) {
    $name=trim((string)$nm); $title=trim((string)($titles[$code]??'')); $tag=trim((string)($taglines[$code]??''));
    if ($name==='' && $title==='' && $tag==='') continue;
    db_exec("INSERT INTO staff_i18n (staff_id, lang_code, name, title, tagline) VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE name=VALUES(name), title=VALUES(title), tagline=VALUES(tagline)", [$staffId,$code,$name,$title,$tag]);
  }
  header('Location: /admin/staff_edit.php?id='.$staffId); exit;
} catch (Throwable $e) { http_response_code(500); echo 'Lỗi: '.e($e->getMessage()); }
