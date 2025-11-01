<?php
declare(strict_types=1);
require __DIR__ . '/../../app/auth.php';
auth_require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
if (!csrf_verify($_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }
$id = (int)($_POST['id'] ?? 0);
if (!$id) { header('Location: /admin/staff.php'); exit; }
try {
  $skillIds = db_select("SELECT id FROM staff_skills WHERE staff_id=?", [$id]);
  if ($skillIds) {
    $ids = array_map(fn($r)=>(int)$r['id'], $skillIds);
    $ph = implode(',', array_fill(0,count($ids),'?'));
    db_exec("DELETE FROM staff_skill_i18n WHERE skill_id IN ($ph)", $ids);
    db_exec("DELETE FROM staff_skills WHERE id IN ($ph)", $ids);
  }
  db_exec("DELETE FROM staff_i18n WHERE staff_id=?", [$id]);
  db_exec("DELETE FROM staff WHERE id=?", [$id]);
  header('Location: /admin/staff.php'); exit;
} catch (Throwable $e) { http_response_code(500); echo 'Không thể xoá: '.e($e->getMessage()); }
