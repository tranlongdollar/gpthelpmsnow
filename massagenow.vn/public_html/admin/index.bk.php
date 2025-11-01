<?php
declare(strict_types=1);
/**
 * /public_html/admin/index.php — Admin Dashboard
 */
require __DIR__ . '/../../app/auth.php';
auth_require_login();
$u = auth_user();

// Đếm nhanh
$counts = [
  'cities'   => (int)db_value("SELECT COUNT(*) FROM cities", []),
  'services' => (int)db_value("SELECT COUNT(*) FROM services", []),
  'staff'    => (int)db_value("SELECT COUNT(*) FROM staff", []),
  'users'    => (int)db_value("SELECT COUNT(*) FROM users", []),
  // Tuỳ chọn: i18n keys
  'i18n'     => (int)db_value("SELECT COUNT(DISTINCT CONCAT(page_key, ':', text_key)) FROM i18n_texts", []),
];

?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root { --fg:#111; --muted:#666; --card:#fff; --bd:#e5e7eb; }
    body { font-family: system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; margin:24px auto; max-width:1024px; padding:0 16px; color:var(--fg); }
    .top { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
    .muted { color:var(--muted); font-size:13px; }
    .grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:12px; margin-top:16px; }
    .card { background:var(--card); border:1px solid var(--bd); border-radius:12px; padding:14px 16px; }
    .kpi { font-size:28px; font-weight:700; margin:4px 0 0 0; }
    a.btn { display:inline-block; padding:10px 14px; border-radius:10px; background:#111; color:#fff; text-decoration:none; }
    nav a { text-decoration:none; margin-right:10px; }
    ul.menu { list-style:none; padding:0; margin:0; display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:10px; }
    ul.menu li a { display:block; border:1px solid var(--bd); border-radius:12px; padding:12px 14px; }
    code { background:#f6f8fa; padding:2px 6px; border-radius:6px; }
  </style>
</head>
<body>
  <div class="top">
    <div>
      <h1 style="margin:0;">Bảng điều khiển</h1>
      <div class="muted">
        Xin chào, <?= e($u['name']) ?> (<?= e($u['role']) ?>)
        • <a href="/admin/logout.php">Đăng xuất</a>
      </div>
    </div>
    <div>
      <a class="btn" href="/admin/cities.php">Quản lý Thành phố</a>
    </div>
  </div>

  <div class="grid">
    <div class="card">
      <div>Thành phố</div>
      <div class="kpi"><?= $counts['cities'] ?></div>
      <div class="muted">/admin/cities.php</div>
    </div>
    <div class="card">
      <div>Dịch vụ</div>
      <div class="kpi"><?= $counts['services'] ?></div>
      <div class="muted">/admin/services.php</div>
    </div>
    <div class="card">
      <div>Nhân sự</div>
      <div class="kpi"><?= $counts['staff'] ?></div>
      <div class="muted">/admin/staff.php</div>
    </div>
    <div class="card">
      <div>Người dùng</div>
      <div class="kpi"><?= $counts['users'] ?></div>
      <div class="muted">bảng users</div>
    </div>
    <div class="card">
      <div>Translation keys</div>
      <div class="kpi"><?= $counts['i18n'] ?></div>
      <div class="muted">/admin/translations.php?page=<code>page.massageteam</code></div>
    </div>
  </div>

  <h2>Chức năng nhanh</h2>
  <ul class="menu">
    <li><a href="/admin/cities.php">Quản lý Thành phố</a></li>
    <li><a href="/admin/services.php">Quản lý Dịch vụ</a></li>
    <li><a href="/admin/staff.php">Quản lý Nhân sự & Kỹ năng</a></li>
    <li><a href="/admin/translations.php?page=page.massageteam">Sửa bản dịch (page.massageteam)</a></li>
    <li><a href="/admin/city_assign.php?id=1">Gán nội dung cho City (#1)</a></li>
    <li><a href="/admin/page_massageteam.php">Sửa nội dung (page.massageteam)</a></li>
  </ul>

  <p class="muted" style="margin-top:16px;">
    Gợi ý: đổi số <code>id</code> trên link “Gán nội dung” theo City bạn muốn.
  </p>
</body>
</html>
