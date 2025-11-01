<?php
declare(strict_types=1);
/**
 * /public_html/admin/cities.php — Bước 14: CRUD Thành phố + nút "Tạo trang" (generate links)
 */
require __DIR__ . '/../../app/auth.php';
auth_require_login();
$u = auth_user();

// Fetch cities & languages
$q = trim($_GET['q'] ?? '');
$params = [];
$sql = "SELECT id, name, slug, status FROM cities";
if ($q !== '') {
  $sql .= " WHERE name LIKE ? OR slug LIKE ?";
  $params[] = "%{$q}%";
  $params[] = "%{$q}%";
}
$sql .= " ORDER BY id DESC";
$cities = db_select($sql, $params);
$langs  = db_select("SELECT code, name, is_default FROM languages ORDER BY is_default DESC, code");

function badge_status(string $s): string {
  $c = $s === 'published' ? '#0a5' : '#666';
  return '<span style="display:inline-block;padding:3px 8px;border-radius:999px;background:#f6f8fa;color:'.$c.';border:1px solid #e5e7eb;">'.e($s).'</span>';
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = $editId ? db_row("SELECT * FROM cities WHERE id=?", [$editId]) : null;
$csrf = csrf_token();
?><!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Quản lý Thành phố</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 24px auto; max-width: 1000px; padding: 0 16px; }
    .top { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
    .grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap:12px; }
    .card { border:1px solid #e5e7eb; border-radius:12px; padding:14px 16px; }
    .muted{ color:#666; font-size:13px; }
    table { width:100%; border-collapse: collapse; margin-top: 14px; }
    th, td { border-bottom: 1px solid #eee; padding: 8px 6px; text-align:left; font-size: 14px; }
    th { background:#fafafa; }
    a.btn, button.btn { display:inline-block; padding:8px 10px; border-radius:8px; background:#111; color:#fff; text-decoration:none; border:0; cursor:pointer; }
    .btn.gray { background:#6b7280; }
    .btn.warn { background:#b91c1c; }
    input[type="text"] { width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:8px; }
    select { padding:8px 10px; border:1px solid #ddd; border-radius:8px; }
    form.inline { display:inline; }
    .flex { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .nowrap { white-space: nowrap; }
    .row-actions a { margin-right: 6px; }
    .lang-badges span { display:inline-block; padding:2px 6px; border:1px solid #eee; border-radius:6px; margin-right:6px; font-size:12px;}
    .search { display:flex; gap:8px; align-items:center; }
  </style>
</head>
<body>
  <div class="top">
    <div>
      <h1 style="margin:0">Thành phố</h1>
      <div class="muted">Xin chào, <?= e($u['name']) ?> — <a href="/admin/">Về Dashboard</a></div>
    </div>
    <form class="search" method="get">
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Tìm theo tên/slug...">
      <button class="btn" type="submit">Tìm</button>
    </form>
  </div>

  <div class="grid" style="margin-top:12px;">
    <!-- Form thêm mới -->
    <div class="card">
      <h3 style="margin:0 0 8px 0;">Thêm thành phố</h3>
      <form method="post" action="/admin/city_save.php" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <label>Tên
          <input type="text" name="name" id="new_name" placeholder="VD: Hà Nội" required>
        </label>
        <div class="flex">
          <label style="flex:1">Slug
            <input type="text" name="slug" id="new_slug" placeholder="vd: ha-noi" required>
          </label>
          <label>Trạng thái
            <select name="status">
              <option value="draft">draft</option>
              <option value="published">published</option>
            </select>
          </label>
        </div>
        <div class="flex" style="margin-top:8px;">
          <button class="btn" type="submit">Lưu</button>
        </div>
      </form>
      <script>
        // Auto slug
        (function(){
          function toSlug(s){
            s = s.toLowerCase()
              .normalize('NFD').replace(/[\u0300-\u036f]/g,'') // bỏ dấu
              .replace(/đ/g,'d').replace(/[^a-z0-9]+/g,'-').replace(/(^-|-$)/g,'');
            return s || 'n-a';
          }
          var name = document.getElementById('new_name');
          var slug = document.getElementById('new_slug');
          if (name && slug) {
            name.addEventListener('input', function(){ if (!slug.value) slug.value = toSlug(name.value); });
          }
        })();
      </script>
    </div>

    <!-- Form sửa (nếu có edit=ID) -->
    <div class="card">
      <h3 style="margin:0 0 8px 0;">Sửa thành phố</h3>
      <?php if (!$editRow): ?>
        <div class="muted">Chọn <em>Sửa</em> ở danh sách để chỉnh.</div>
      <?php else: ?>
        <form method="post" action="/admin/city_save.php" autocomplete="off">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">
          <label>Tên
            <input type="text" name="name" value="<?= e($editRow['name']) ?>" required>
          </label>
          <div class="flex">
            <label style="flex:1">Slug
              <input type="text" name="slug" value="<?= e($editRow['slug']) ?>" required>
            </label>
            <label>Trạng thái
              <select name="status">
                <option value="draft"<?= $editRow['status']==='draft'?' selected':'' ?>>draft</option>
                <option value="published"<?= $editRow['status']==='published'?' selected':'' ?>>published</option>
              </select>
            </label>
          </div>
          <div class="flex" style="margin-top:8px;">
            <button class="btn" type="submit">Cập nhật</button>
            <a class="btn gray" href="/admin/cities.php">Hủy</a>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="card" style="margin-top:12px;">
    <div class="flex" style="justify-content:space-between;">
      <strong>Danh sách (<?= count($cities) ?>)</strong>
      <div class="lang-badges">
        <?php foreach ($langs as $l): ?>
          <span><?= e($l['code']) ?><?= $l['is_default']?'*':'' ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <table>
      <thead>
        <tr>
          <th width="60">ID</th>
          <th>Tên</th>
          <th>Slug</th>
          <th width="120">Trạng thái</th>
          <th width="320" class="nowrap">Hành động</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cities as $c): ?>
          <tr>
            <td class="nowrap"><?= (int)$c['id'] ?></td>
            <td><?= e($c['name']) ?></td>
            <td class="nowrap"><?= e($c['slug']) ?></td>
            <td><?= badge_status($c['status']) ?></td>
            <td class="row-actions nowrap">
              <a class="btn gray" href="/admin/cities.php?edit=<?= (int)$c['id'] ?>">Sửa</a>
              <?php if ($c['status'] !== 'published'): ?>
                <form class="inline" method="post" action="/admin/city_save.php">
                  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <input type="hidden" name="action" value="publish">
                  <button class="btn" type="submit">Publish</button>
                </form>
              <?php else: ?>
                <form class="inline" method="post" action="/admin/city_save.php">
                  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <input type="hidden" name="action" value="unpublish">
                  <button class="btn gray" type="submit">Unpublish</button>
                </form>
              <?php endif; ?>
              <a class="btn" href="/admin/city_links.php?id=<?= (int)$c['id'] ?>">Tạo trang</a>
              <form class="inline" method="post" action="/admin/city_delete.php" onsubmit="return confirm('Xoá thành phố này?');">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button class="btn warn" type="submit">Xoá</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
