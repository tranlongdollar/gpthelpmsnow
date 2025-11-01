<?php
declare(strict_types=1);
require __DIR__ . '/../../app/auth.php';
auth_require_login();
$u = auth_user();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$staff = db_row("SELECT * FROM staff WHERE id=?", [$id]);
if (!$staff) { header('Location: /admin/staff.php'); exit; }
$langs = db_select("SELECT code, name, is_default FROM languages ORDER BY is_default DESC, code");
$i18n = db_select("SELECT lang_code, name, title, tagline FROM staff_i18n WHERE staff_id=?", [$id]);
$map = []; foreach ($i18n as $r) $map[$r['lang_code']]=$r;
$skills = db_select("SELECT id, percent, order_no FROM staff_skills WHERE staff_id=? ORDER BY order_no ASC, id ASC", [$id]);
$csrf = csrf_token();
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sửa nhân sự #<?= (int)$id ?></title>
<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:24px auto;max-width:1100px;padding:0 16px}.top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.card{border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;margin-top:12px}.muted{color:#666;font-size:13px}input[type=text],input[type=number]{width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:8px}select{padding:8px 10px;border:1px solid #ddd;border-radius:8px;width:100%}a.btn,button.btn{display:inline-block;padding:8px 10px;border-radius:8px;background:#111;color:#fff;text-decoration:none;border:0;cursor:pointer}.btn.gray{background:#6b7280}.btn.warn{background:#b91c1c}table{width:100%;border-collapse:collapse;margin-top:14px}th,td{border-bottom:1px solid #eee;padding:8px 6px;text-align:left;font-size:14px}th{background:#fafafa}form.inline{display:inline}.tab{display:inline-block;padding:6px 10px;border:1px solid #e5e7eb;border-bottom:0;border-radius:8px 8px 0 0;margin-right:6px;background:#fafafa}.tab.active{background:#fff;font-weight:600}.tabpane{border:1px solid #e5e7eb;padding:10px;border-radius:0 8px 8px 8px}img.thumb{width:120px;height:120px;object-fit:cover;border-radius:12px;border:1px solid #eee}</style></head><body>
<div class="top"><div><h1 style="margin:0">Sửa nhân sự #<?= (int)$id ?></h1><div class="muted"><a href="/admin/staff.php">← Quay lại danh sách</a></div></div>
<div><form class="inline" method="post" action="/admin/staff_delete.php" onsubmit="return confirm('Xoá nhân sự này?');"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="id" value="<?= (int)$id ?>"><button class="btn warn" type="submit">Xoá</button></form></div></div>
<div class="card"><h3 style="margin:0 0 8px;">Thông tin cơ bản</h3>
<form method="post" action="/admin/staff_save.php" autocomplete="off"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="id" value="<?= (int)$id ?>">
<div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;"><div><?php if ($staff['photo_url']): ?><img class="thumb" src="<?= e($staff['photo_url']) ?>" alt=""><?php endif; ?></div>
<div style="flex:1;min-width:280px;"><label>Ảnh (URL)<input type="text" name="photo_url" value="<?= e($staff['photo_url']) ?>"></label>
<div style="display:flex;gap:8px;"><label>Thứ tự<input type="number" name="order_no" value="<?= (int)$staff['order_no'] ?>"></label>
<label>Active<select name="active"><option value="1"<?= $staff['active']?' selected':'' ?>>YES</option><option value="0"<?= !$staff['active']?' selected':'' ?>>NO</option></select></label></div></div></div>
<div style="margin-top:12px;"><?php foreach ($langs as $i=>$l): $code=$l['code']; ?><a class="tab<?= $i===0?' active':'' ?>" href="#" onclick="return S.tab(event,'tab-<?= e($code) ?>')"><?= e($code) ?></a><?php endforeach; ?>
<div class="tabpane"><?php foreach ($langs as $i=>$l): $code=$l['code']; $v=$map[$code] ?? ['name'=>'','title'=>'','tagline'=>'']; ?>
<div id="tab-<?= e($code) ?>" style="<?= $i===0?'':'display:none' ?>"><label>Tên (<?= e($code) ?>)<input type="text" name="name[<?= e($code) ?>]" value="<?= e($v['name']) ?>"></label>
<label>Chức danh (<?= e($code) ?>)<input type="text" name="title[<?= e($code) ?>]" value="<?= e($v['title']) ?>"></label>
<label>Tagline (<?= e($code) ?>)<input type="text" name="tagline[<?= e($code) ?>]" value="<?= e($v['tagline']) ?>"></label></div><?php endforeach; ?>
</div></div><div style="margin-top:8px;"><button class="btn" type="submit">Lưu thông tin</button></div></form></div>
<div class="card"><h3 style="margin:0 0 8px;">Kỹ năng</h3>
<details><summary>Thêm kỹ năng</summary>
<form method="post" action="/admin/skill_save.php" autocomplete="off" style="margin-top:8px;"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="staff_id" value="<?= (int)$id ?>">
<div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;"><label>Phần trăm<input type="number" name="percent" value="80" min="0" max="100"></label><label>Thứ tự<input type="number" name="order_no" value="100"></label></div>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:8px;margin-top:6px;"><?php foreach ($langs as $l): ?><label>Label (<?= e($l['code']) ?>)<input type="text" name="label[<?= e($l['code']) ?>]" placeholder="VD: Deep Tissue"></label><?php endforeach; ?></div>
<div style="margin-top:8px;"><button class="btn" type="submit">Thêm</button></div></form></details>
<table><thead><tr><th width="60">ID</th><th width="90">%</th><th width="90">Order</th><th>Labels</th><th width="260">Hành động</th></tr></thead><tbody>
<?php foreach ($skills as $sk): $labels = db_select("SELECT lang_code, label FROM staff_skill_i18n WHERE skill_id=?", [$sk['id']]); $ml = []; foreach ($labels as $r) $ml[$r['lang_code']]=$r['label']; ?>
<tr><td><?= (int)$sk['id'] ?></td><td><?= (int)$sk['percent'] ?></td><td><?= (int)$sk['order_no'] ?></td>
<td><form class="inline" method="post" action="/admin/skill_save.php" autocomplete="off"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="staff_id" value="<?= (int)$id ?>"><input type="hidden" name="skill_id" value="<?= (int)$sk['id'] ?>">
<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;"><label>%<input type="number" name="percent" value="<?= (int)$sk['percent'] ?>" min="0" max="100"></label><label>Order<input type="number" name="order_no" value="<?= (int)$sk['order_no'] ?>"></label></div>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:8px;margin-top:6px;"><?php foreach ($langs as $l): $code=$l['code']; ?><label><?= e($code) ?><input type="text" name="label[<?= e($code) ?>]" value="<?= e($ml[$code] ?? '') ?>"></label><?php endforeach; ?></div>
<div style="margin-top:6px;"><button class="btn" type="submit">Cập nhật</button></div></form></td>
<td class="nowrap"><form class="inline" method="post" action="/admin/skill_delete.php" onsubmit="return confirm('Xoá kỹ năng này?');"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="skill_id" value="<?= (int)$sk['id'] ?>"><button class="btn warn" type="submit">Xoá</button></form></td></tr>
<?php endforeach; ?></tbody></table></div>
<script>var S={tab:function(ev,id){ev.preventDefault();document.querySelectorAll('.tab').forEach(function(a){a.classList.remove('active')});document.querySelectorAll('[id^="tab-"]').forEach(function(p){p.style.display='none'});ev.target.classList.add('active');var el=document.getElementById(id);if(el)el.style.display='';return false}};</script>
</body></html>
