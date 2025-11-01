<?php
declare(strict_types=1);
/**
 * /public_html/admin/city_links.php — "Tạo trang": xuất link /{lang}/{slug} cho city đã publish
 */
require __DIR__ . '/../../app/auth.php';
auth_require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$city = $id ? db_row("SELECT id,name,slug,status FROM cities WHERE id=?", [$id]) : null;
if (!$city) { header('Location: /admin/cities.php'); exit; }

$langs  = db_select("SELECT code, is_default FROM languages ORDER BY is_default DESC, code");
$links = [];
foreach ($langs as $l) {
  $links[] = [
    'code' => $l['code'],
    'url'  => rtrim(BASE_URL, '/') . '/' . $l['code'] . '/' . $city['slug'],
  ];
}
$csrf = csrf_token();
?><!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tạo trang — <?= e($city['name']) ?></title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 24px auto; max-width: 820px; padding: 0 16px; }
    .card { border:1px solid #e5e7eb; border-radius:12px; padding:16px; }
    code { background:#f6f8fa; padding:2px 6px; border-radius:6px; }
    .muted{ color:#666; font-size:13px; }
    .row { display:flex; justify-content:space-between; gap:8px; align-items:center; border-bottom:1px solid #eee; padding:8px 0; }
    .btn { display:inline-block; padding:8px 10px; border-radius:8px; background:#111; color:#fff; text-decoration:none; border:0; cursor:pointer; }
  </style>
</head>
<body>
  <h1 style="margin:0 0 8px 0;">Tạo trang cho: <?= e($city['name']) ?></h1>
  <?php if ($city['status'] !== 'published'): ?>
    <div class="card" style="margin-bottom:12px;background:#fff7ed;border-color:#fed7aa;">
      <strong>Chú ý:</strong> City đang ở trạng thái <code><?= e($city['status']) ?></code>. 
      <form method="post" action="/admin/city_save.php" style="display:inline;">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="id" value="<?= (int)$city['id'] ?>">
        <input type="hidden" name="action" value="publish">
        <button class="btn" type="submit">Publish ngay</button>
      </form>
    </div>
  <?php endif; ?>

  <div class="card">
    <h3 style="margin-top:0">Link truy cập theo ngôn ngữ</h3>
    <?php foreach ($links as $l): ?>
      <div class="row">
        <div><strong><?= e($l['code']) ?>:</strong> <code><?= e($l['url']) ?></code></div>
        <button class="btn" onclick="navigator.clipboard.writeText('<?= e($l['url']) ?>')">Copy</button>
      </div>
    <?php endforeach; ?>
  </div>

  <p class="muted" style="margin-top:12px;">Không cần tạo file tĩnh — router động sẽ hiển thị nội dung ngay khi city ở trạng thái <code>published</code>.</p>
  <p><a href="/admin/cities.php">← Quay lại danh sách</a></p>
</body>
</html>
