<?php
declare(strict_types=1);
/**
 * /public_html/admin/services.php — Bước 15: CRUD Dịch vụ + bản dịch tên theo ngôn ngữ
 * Bảng: services (slug, duration_min, order_no, active)
 *        service_i18n (service_id, lang_code, name)
 */
require __DIR__ . '/../../app/auth.php';
auth_require_login();
$u = auth_user();

// Fetch languages
$langs = db_select("SELECT code, name, is_default FROM languages ORDER BY is_default DESC, code");

// List services
$q = trim($_GET['q'] ?? '');
$params = [];
$sql = "SELECT s.id, s.slug, s.duration_min, s.order_no, s.active FROM services s";
if ($q !== '') {
  $sql .= " WHERE s.slug LIKE ?";
  $params[] = "%{$q}%";
}
$sql .= " ORDER BY s.order_no ASC, s.slug ASC, s.duration_min ASC";
$rows = db_select($sql, $params);

// Editing
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = $editId ? db_row("SELECT * FROM services WHERE id=?", [$editId]) : null;
$names = [];
if ($editRow) {
  $i18n = db_select("SELECT lang_code, name FROM service_i18n WHERE service_id=?", [$editRow['id']]);
  foreach ($i18n as $r) $names[$r['lang_code']] = $r['name'];
}

$csrf = csrf_token();
function yesno($b){ return $b ? 'YES' : 'NO'; }
?><!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Quản lý Dịch vụ</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 24px auto; max-width: 1100px; padding: 0 16px; }
    .top { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
    .grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(320px,1fr)); gap:12px; }
    .card { border:1px solid #e5e7eb; border-radius:12px; padding:14px 16px; }
    .muted{ color:#666; font-size:13px; }
    table { width:100%; border-collapse: collapse; margin-top: 14px; }
    th, td { border-bottom: 1px solid #eee; padding: 8px 6px; text-align:left; font-size: 14px; }
    th { background:#fafafa; }
    a.btn, button.btn { display:inline-block; padding:8px 10px; border-radius:8px; background:#111; color:#fff; text-decoration:none; border:0; cursor:pointer; }
    .btn.gray { background:#6b7280; }
    .btn.warn { background:#b91c1c; }
    input[type="text"], input[type="number"] { width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:8px; }
    select { padding:8px 10px; border:1px solid #ddd; border-radius:8px; width:100%; }
    form.inline { display:inline; }
    .flex { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .nowrap { white-space: nowrap; }
    .lang-badges span { display:inline-block; padding:2px 6px; border:1px solid #eee; border-radius:6px; margin-right:6px; font-size:12px;}
    .row-actions a { margin-right: 6px; }
    .tab { display:inline-block; padding:6px 10px; border:1px solid #e5e7eb; border-bottom:0; border-radius:8px 8px 0 0; margin-right:6px; background:#fafafa; }
    .tab.active { background:#fff; font-weight:600; }
    .tabpane { border:1px solid #e5e7eb; padding:10px; border-radius:0 8px 8px 8px; }
  </style>
</head>
<body>
  <div class="top">
    <div>
      <h1 style="margin:0">Dịch vụ</h1>
      <div class="muted">Xin chào, <?= e($u['name']) ?> — <a href="/admin/">Về Dashboard</a></div>
    </div>
    <form method="get" class="flex">
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Tìm theo slug...">
      <button class="btn" type="submit">Tìm</button>
    </form>
  </div>

  <div class="grid" style="margin-top:12px;">
    <!-- Form thêm mới -->
    <div class="card">
      <h3 style="margin:0 0 8px 0;">Thêm dịch vụ</h3>
      <form method="post" action="/admin/service_save.php" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <div class="flex">
          <label style="flex:1">Slug
            <input type="text" name="slug" id="new_slug" placeholder="vd: aroma" required>
          </label>
          <label>Thời lượng (phút)
            <select name="duration_min">
              <option value="60">60</option>
              <option value="90">90</option>
              <option value="120">120</option>
            </select>
          </label>
        </div>
        <div class="flex">
          <label>Thứ tự
            <input type="number" name="order_no" value="100">
          </label>
          <label>Active
            <select name="active">
              <option value="1">YES</option>
              <option value="0">NO</option>
            </select>
          </label>
        </div>
        <details style="margin-top:8px;">
          <summary>Nhập tên hiển thị cho từng ngôn ngữ</summary>
          <?php foreach ($langs as $l): ?>
            <label><?= e($l['code']) ?> (<?= e($l['name']) ?>)
              <input type="text" name="name[<?= e($l['code']) ?>]" placeholder="Tên dịch vụ (<?= e($l['code']) ?>)">
            </label>
          <?php endforeach; ?>
        </details>
        <div class="flex" style="margin-top:8px;">
          <button class="btn" type="submit">Lưu</button>
        </div>
      </form>
    </div>

    <!-- Form sửa (nếu có) -->
    <div class="card">
      <h3 style="margin:0 0 8px 0;">Sửa dịch vụ</h3>
      <?php if (!$editRow): ?>
        <div class="muted">Chọn <em>Sửa</em> ở danh sách để chỉnh.</div>
      <?php else: ?>
        <form method="post" action="/admin/service_save.php" autocomplete="off">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">
          <div class="flex">
            <label style="flex:1">Slug
              <input type="text" name="slug" value="<?= e($editRow['slug']) ?>" required>
            </label>
            <label>Thời lượng (phút)
              <select name="duration_min">
                <?php foreach ([60,90,120] as $d): ?>
                  <option value="<?= $d ?>"<?= ($editRow['duration_min']==$d?' selected':'') ?>><?= $d ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>
          <div class="flex">
            <label>Thứ tự
              <input type="number" name="order_no" value="<?= (int)$editRow['order_no'] ?>">
            </label>
            <label>Active
              <select name="active">
                <option value="1"<?= $editRow['active']?' selected':'' ?>>YES</option>
                <option value="0"<?= !$editRow['active']?' selected':'' ?>>NO</option>
              </select>
            </label>
          </div>

          <div style="margin-top:8px;">
            <div>
              <?php foreach ($langs as $i=>$l): $code=$l['code']; ?>
                <a class="tab<?= $i===0?' active':'' ?>" href="#" onclick="return S.tab(event,'tab-<?= e($code) ?>')"><?= e($code) ?></a>
              <?php endforeach; ?>
            </div>
            <div class="tabpane">
              <?php foreach ($langs as $i=>$l): $code=$l['code']; ?>
                <div id="tab-<?= e($code) ?>" style="<?= $i===0?'':'display:none' ?>">
                  <label>Tên (<?= e($code) ?>)
                    <input type="text" name="name[<?= e($code) ?>]" value="<?= e($names[$code] ?? '') ?>">
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="flex" style="margin-top:8px;">
            <button class="btn" type="submit">Cập nhật</button>
            <a class="btn gray" href="/admin/services.php">Hủy</a>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="card" style="margin-top:12px;">
    <strong>Danh sách (<?= count($rows) ?>)</strong>
    <table>
      <thead>
        <tr>
          <th width="60">ID</th>
          <th>Slug</th>
          <th width="90">Phút</th>
          <th width="90">Order</th>
          <th width="80">Active</th>
          <th width="250">Tên (<?= e($langs[0]['code'] ?? 'vi') ?>)</th>
          <th width="280" class="nowrap">Hành động</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r):
        $nameDef = db_value("SELECT name FROM service_i18n WHERE service_id=? AND lang_code=? LIMIT 1", [$r['id'], $langs[0]['code'] ?? 'vi']);
      ?>
        <tr>
          <td class="nowrap"><?= (int)$r['id'] ?></td>
          <td><?= e($r['slug']) ?></td>
          <td><?= (int)$r['duration_min'] ?></td>
          <td><?= (int)$r['order_no'] ?></td>
          <td><?= yesno((int)$r['active']) ?></td>
          <td><?= e($nameDef ?? '') ?></td>
          <td class="row-actions nowrap">
            <a class="btn gray" href="/admin/services.php?edit=<?= (int)$r['id'] ?>">Sửa</a>
            <form class="inline" method="post" action="/admin/service_delete.php" onsubmit="return confirm('Xoá dịch vụ này?');">
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn warn" type="submit">Xoá</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <script>
    // tabs for edit form
    var S = {
      tab: function(ev, id){
        ev.preventDefault();
        document.querySelectorAll('.tab').forEach(function(a){ a.classList.remove('active'); });
        document.querySelectorAll('[id^="tab-"]').forEach(function(p){ p.style.display='none'; });
        ev.target.classList.add('active');
        var el = document.getElementById(id); if (el) el.style.display = '';
        return false;
      }
    };
  </script>
</body>
</html>
