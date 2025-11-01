<?php
declare(strict_types=1);
require __DIR__ . '/../../app/auth.php';
auth_require_login();
$u = auth_user();
$langs = db_select("SELECT code, name, is_default FROM languages ORDER BY is_default DESC, code");
$rows = db_select("SELECT id, photo_url, order_no, active FROM staff ORDER BY order_no ASC, id ASC");
$csrf = csrf_token();
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Quản lý Nhân sự</title>
<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:24px auto;max-width:1100px;padding:0 16px}.top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.card{border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;margin-top:12px}.muted{color:#666;font-size:13px}table{width:100%;border-collapse:collapse;margin-top:14px}th,td{border-bottom:1px solid #eee;padding:8px 6px;text-align:left;font-size:14px}th{background:#fafafa}a.btn,button.btn{display:inline-block;padding:8px 10px;border-radius:8px;background:#111;color:#fff;text-decoration:none;border:0;cursor:pointer}.btn.gray{background:#6b7280}.btn.warn{background:#b91c1c}input[type=text],input[type=number]{width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:8px}select{padding:8px 10px;border:1px solid #ddd;border-radius:8px;width:100%}form.inline{display:inline}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:12px}img.thumb{width:64px;height:64px;object-fit:cover;border-radius:10px;border:1px solid #eee}</style></head><body>
<div class="top"><div><h1 style="margin:0">Nhân sự</h1><div class="muted">Xin chào, <?= e($u['name']) ?> — <a href="/admin/">Về Dashboard</a></div></div></div>
<div class="grid">
  <div class="card"><h3 style="margin:0 0 8px 0;">Thêm nhân sự</h3>
    <form method="post" action="/admin/staff_save.php" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <label>Ảnh (URL)<input type="text" name="photo_url" placeholder="https://..."></label>
      <div style="display:flex;gap:8px;">
        <label>Thứ tự<input type="number" name="order_no" value="100"></label>
        <label>Active<select name="active"><option value="1">YES</option><option value="0">NO</option></select></label>
      </div>
      <details style="margin-top:8px;"><summary>Nhập tên/chức danh/tagline theo ngôn ngữ</summary>
        <?php foreach ($langs as $l): $code=$l['code']; ?>
        <fieldset style="border:1px dashed #e5e7eb;padding:8px;border-radius:8px;margin-top:8px;">
          <legend style="color:#666"><?= e($code) ?> (<?= e($l['name']) ?>)</legend>
          <label>Tên<input type="text" name="name[<?= e($code) ?>]" placeholder="VD: Lan"></label>
          <label>Chức danh<input type="text" name="title[<?= e($code) ?>]" placeholder="VD: Senior Therapist"></label>
          <label>Tagline<input type="text" name="tagline[<?= e($code) ?>]" placeholder="VD: 7 năm kinh nghiệm..."></label>
        </fieldset>
        <?php endforeach; ?>
      </details>
      <div style="margin-top:8px;"><button class="btn" type="submit">Lưu & chỉnh chi tiết</button></div>
    </form>
  </div>
  <div class="card"><strong>Danh sách (<?= count($rows) ?>)</strong>
    <table><thead><tr><th width="60">ID</th><th>Ảnh</th><th width="90">Order</th><th width="80">Active</th><th width="280">Hành động</th></tr></thead><tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?php if ($r['photo_url']): ?><img class="thumb" src="<?= e($r['photo_url']) ?>" alt=""><?php endif; ?></td>
        <td><?= (int)$r['order_no'] ?></td>
        <td><?= (int)$r['active'] ? 'YES' : 'NO' ?></td>
        <td class="nowrap">
          <a class="btn" href="/admin/staff_edit.php?id=<?= (int)$r['id'] ?>">Sửa & Kỹ năng</a>
          <form class="inline" method="post" action="/admin/staff_delete.php" onsubmit="return confirm('Xoá nhân sự này?');">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn warn" type="submit">Xoá</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody></table>
  </div>
</div>
</body></html>
