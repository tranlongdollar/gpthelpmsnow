<?php
declare(strict_types=1);
/**
 * /public_html/admin/orders.php
 * Danh sách & quản lý nhanh trạng thái đơn đặt lịch (booking)
 * - Dùng session login giống cities.php: auth_require_login()
 * - Có tìm kiếm nhanh theo tên/email/phone, lọc theo thành phố & trạng thái
 * - Đổi trạng thái inline (có CSRF)
 */

require __DIR__ . '/../../app/config.php';
require __DIR__ . '/../../app/db.php';
require __DIR__ . '/../../app/auth.php';

auth_require_login();
$u = auth_user();

if (!function_exists('e')) {
  function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}

/* ---------------- Filters ---------------- */
$q        = trim($_GET['q'] ?? '');
$status   = trim($_GET['status'] ?? '');
$city_id  = isset($_GET['city_id']) ? (int)$_GET['city_id'] : 0;
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 30;
$offset   = ($page - 1) * $perPage;

$params = [];
$where  = [];

if ($q !== '') {
  $where[] = '(b.customer_name LIKE ? OR b.email LIKE ? OR b.phone LIKE ?)';
  $params[] = "%{$q}%";
  $params[] = "%{$q}%";
  $params[] = "%{$q}%";
}
if ($status !== '') {
  $where[] = 'b.status = ?';
  $params[] = $status;
}
if ($city_id > 0) {
  $where[] = 'b.city_id = ?';
  $params[] = $city_id;
}

$wsql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* ---------------- Change status (inline) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action']==='set_status') {
  // CSRF
  if (!csrf_verify($_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }
  $id = (int)($_POST['id'] ?? 0);
  $new = (string)($_POST['status'] ?? '');
  $allow = ['new','confirmed','in_progress','done','canceled'];
  if ($id>0 && in_array($new, $allow, true)) {
    $st = pdo()->prepare("UPDATE bookings SET status=? WHERE id=?");
    $st->execute([$new, $id]);
  }
  // quay lại trang hiện tại (giữ filter)
  $qs = $_SERVER['QUERY_STRING'] ?? '';
  header('Location: /admin/orders.php' . ($qs ? ('?'.$qs) : ''));
  exit;
}

/* ---------------- Query data ---------------- */
$sqlCount = "SELECT COUNT(*) FROM bookings b {$wsql}";
$total = (int)db_value($sqlCount, $params);

$sql = "
  SELECT b.*, c.name AS city_name
  FROM bookings b
  JOIN cities c ON c.id=b.city_id
  {$wsql}
  ORDER BY b.created_at DESC
  LIMIT {$perPage} OFFSET {$offset}
";
$rows = db_select($sql, $params);

$cities = db_select("SELECT id, name FROM cities ORDER BY name ASC");
$csrf = csrf_token();

// For pager
$totalPages = max(1, (int)ceil($total / $perPage));
function badge_status(string $s): string {
  $map = [
    'new'         => ['Mới',        '#0ea5e9'],
    'confirmed'   => ['Xác nhận',   '#10b981'],
    'in_progress' => ['Đang làm',   '#f59e0b'],
    'done'        => ['Hoàn thành', '#111827'],
    'canceled'    => ['Huỷ',        '#ef4444'],
  ];
  $txt = $map[$s][0] ?? $s;
  $bg  = $map[$s][1] ?? '#6b7280';
  return '<span style="display:inline-block;padding:3px 8px;border-radius:999px;background:'.$bg.';color:#fff;font-size:12px">'.$txt.'</span>';
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đơn hàng (Bookings)</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;max-width:1200px;margin:20px auto;padding:0 16px}
    .top{display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap}
    .muted{color:#666;font-size:13px}
    .card{border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;margin-top:12px}
    .filters{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    input[type="text"],select{padding:8px 10px;border:1px solid #ddd;border-radius:8px}
    button.btn,a.btn{padding:8px 12px;border-radius:8px;background:#111;color:#fff;text-decoration:none;border:0;cursor:pointer}
    a.btn.gray,button.btn.gray{background:#6b7280}
    a.btn.link{background:transparent;color:#111;padding:0 4px}
    table{width:100%;border-collapse:collapse;margin-top:10px;font-size:14px}
    th,td{border-bottom:1px solid #eee;padding:8px 6px;text-align:left;vertical-align:top}
    th{background:#fafafa}
    .nowrap{white-space:nowrap}
    .pager a{display:inline-block;padding:6px 10px;border:1px solid #ddd;border-radius:8px;margin-right:6px;text-decoration:none;color:#111}
    .pager strong{display:inline-block;padding:6px 10px;border:1px solid #111;border-radius:8px;background:#111;color:#fff;margin-right:6px}
    .actions a{margin-right:8px}
  </style>
</head>
<body>
  <div class="top">
    <div>
      <h1 style="margin:0">Đơn đặt lịch</h1>
      <div class="muted">Xin chào, <?= e($u['name']) ?> — <a class="btn link" href="/admin/">Dashboard</a></div>
    </div>
    <div>
      <a class="btn gray" href="/admin/cities.php">Quản lý Thành phố</a>
    </div>
  </div>

  <div class="card">
    <form class="filters" method="get" action="/admin/orders.php">
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Tên / Email / SĐT...">
      <select name="status">
        <option value="">-- Trạng thái --</option>
        <?php foreach (['new'=>'Mới','confirmed'=>'Đã xác nhận','in_progress'=>'Đang xử lý','done'=>'Hoàn thành','canceled'=>'Hủy'] as $k=>$v): ?>
          <option value="<?= e($k) ?>" <?= $status===$k?'selected':'' ?>><?= e($v) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="city_id">
        <option value="0">-- Tất cả thành phố --</option>
        <?php foreach ($cities as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $city_id===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn" type="submit">Lọc</button>
      <?php if ($q!=='' || $status!=='' || $city_id>0): ?>
        <a class="btn gray" href="/admin/orders.php">Xoá lọc</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <strong>Kết quả: <?= (int)$total ?> đơn</strong>
      <div class="pager">
        <?php if ($totalPages>1): ?>
          <?php for ($p=1;$p<=$totalPages;$p++): 
            $qs = $_GET; $qs['page'] = $p;
            $url = '/admin/orders.php?'.http_build_query($qs); ?>
            <?= $p===$page ? '<strong>'.$p.'</strong>' : '<a href="'.e($url).'">'.$p.'</a>' ?>
          <?php endfor; ?>
        <?php endif; ?>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th class="nowrap">ID</th>
          <th class="nowrap">Thời gian</th>
          <th>Thành phố</th>
          <th>Lang</th>
          <th>Khách hàng</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Ghi chú</th>
          <th>Trạng thái</th>
          <th class="nowrap">Hành động</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="10" class="muted">Không có đơn nào.</td></tr>
        <?php else: foreach ($rows as $r): ?>
          <tr>
            <td class="nowrap"><?= (int)$r['id'] ?></td>
            <td class="nowrap"><?= e($r['created_at']) ?></td>
            <td><?= e($r['city_name']) ?></td>
            <td class="nowrap"><?= e($r['lang_code']) ?></td>
            <td><?= e($r['customer_name']) ?></td>
            <td><?= e($r['email']) ?></td>
            <td><?= e($r['phone']) ?></td>
            <td style="max-width:420px;white-space:normal;line-height:1.5"><?= nl2br(e($r['note'])) ?></td>
            <td class="nowrap">
              <div style="margin-bottom:6px"><?= badge_status($r['status']) ?></div>
              <form method="post" action="">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="set_status">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <select name="status" onchange="this.form.submit()">
                  <option value="new"         <?= $r['status']==='new'?'selected':'' ?>>Mới</option>
                  <option value="confirmed"   <?= $r['status']==='confirmed'?'selected':'' ?>>Đã xác nhận</option>
                  <option value="in_progress" <?= $r['status']==='in_progress'?'selected':'' ?>>Đang xử lý</option>
                  <option value="done"        <?= $r['status']==='done'?'selected':'' ?>>Hoàn thành</option>
                  <option value="canceled"    <?= $r['status']==='canceled'?'selected':'' ?>>Huỷ</option>
                </select>
              </form>
            </td>
            <td class="nowrap actions">
              <a class="btn link" href="/admin/order-view.php?id=<?= (int)$r['id'] ?>">Xem</a>
              <!-- Có thể bổ sung nút xoá ở đây nếu cần -->
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
