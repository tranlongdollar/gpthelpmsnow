<?php
declare(strict_types=1);
require __DIR__ . '/../../app/auth.php';
auth_require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
if (!csrf_verify($_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }
$skillId = (int)($_POST['skill_id'] ?? 0);
if (!$skillId) { http_response_code(400); exit('Thiếu skill_id'); }
$staffId = (int)db_value("SELECT staff_id FROM staff_skills WHERE id=?", [$skillId]) ?: 0;
try {
  db_exec("DELETE FROM staff_skill_i18n WHERE skill_id=?", [$skillId]);
  db_exec("DELETE FROM staff_skills WHERE id=?", [$skillId]);
  if ($staffId) header('Location: /admin/staff_edit.php?id='.$staffId);
  else header('Location: /admin/staff.php');
  exit;
} catch (Throwable $e) { http_response_code(500); echo 'Không thể xoá: '.e($e->getMessage()); }
