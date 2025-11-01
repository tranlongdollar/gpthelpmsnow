<?php
declare(strict_types=1);
/** /public_html/admin/city_save.php — handle create/update/publish/unpublish */
require __DIR__ . '/../../app/auth.php';
auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
if (!csrf_verify($_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }

$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = $_POST['action'] ?? '';
$name   = trim($_POST['name'] ?? '');
$slug   = trim($_POST['slug'] ?? '');
$status = $_POST['status'] ?? 'draft';

// Normalize
if ($name !== '' && $slug === '') {
  // fallback slug
  $slug = to_slug($name);
} elseif ($slug !== '') {
  $slug = to_slug($slug);
}

try {
  if ($action === 'publish' && $id) {
    db_exec("UPDATE cities SET status='published' WHERE id=?", [$id]);
  } elseif ($action === 'unpublish' && $id) {
    db_exec("UPDATE cities SET status='draft' WHERE id=?", [$id]);
  } else {
    // create / update
    if ($name === '' || $slug === '') throw new Exception('Thiếu tên hoặc slug');
    if (!in_array($status, ['draft','published'], true)) $status = 'draft';

    if ($id) {
      // update
      db_exec("UPDATE cities SET name=?, slug=?, status=? WHERE id=?",
              [$name, $slug, $status, $id]);
    } else {
      // insert — đảm bảo slug unique
      $exists = db_value("SELECT COUNT(*) FROM cities WHERE slug=?", [$slug]);
      if ((int)$exists > 0) {
        $slug = $slug . '-' . substr(sha1(uniqid('', true)), 0, 6);
      }
      db_exec("INSERT INTO cities (name, slug, status) VALUES (?,?,?)",
              [$name, $slug, $status]);
    }
  }
  header('Location: /admin/cities.php');
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo "Lỗi: " . e($e->getMessage());
}
