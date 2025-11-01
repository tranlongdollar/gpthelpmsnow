<?php
declare(strict_types=1);
/** /public_html/admin/service_save.php — create/update + i18n */
require __DIR__ . '/../../app/auth.php';
auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
if (!csrf_verify($_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }

$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$slug   = to_slug(trim($_POST['slug'] ?? ''));
$duration = (int)($_POST['duration_min'] ?? 60);
$order  = (int)($_POST['order_no'] ?? 100);
$active = (int)($_POST['active'] ?? 1);
$names  = $_POST['name'] ?? [];

if ($slug === '' || $duration <= 0) { http_response_code(400); exit('Thiếu slug hoặc duration'); }

try {
  if ($id) {
    // update
    db_exec("UPDATE services SET slug=?, duration_min=?, order_no=?, active=? WHERE id=?",
            [$slug, $duration, $order, $active, $id]);
    $serviceId = $id;
  } else {
    // ensure unique pair (slug, duration_min)
    $exists = db_value("SELECT COUNT(*) FROM services WHERE slug=? AND duration_min=?", [$slug, $duration]);
    if ((int)$exists > 0) {
      // nếu trùng, tăng duration lên 1 như một cách tránh va chạm (hoặc đổi slug)
      $duration = $duration + 1;
    }
    $serviceId = (int)db_insert("INSERT INTO services (slug, duration_min, order_no, active) VALUES (?,?,?,?)",
                                [$slug, $duration, $order, $active]);
  }

  // Upsert i18n names
  foreach ($names as $code => $val) {
    $name = trim((string)$val);
    if ($name === '') continue;
    db_exec("INSERT INTO service_i18n (service_id, lang_code, name) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE name=VALUES(name)",
            [$serviceId, $code, $name]);
  }

  header('Location: /admin/services.php');
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo "Lỗi: " . e($e->getMessage());
}
