<?php
declare(strict_types=1);
/**
 * /public_html/admin/order-view.php
 * Xem chi tiết 1 đơn + đổi trạng thái (có CSRF), dùng session login.
 */

require __DIR__ . '/../../app/config.php';
require __DIR__ . '/../../app/db.php';
require __DIR__ . '/../../app/auth.php';

auth_require_login();
$u = auth_user();

if (!function_exists('e')) {
  function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); exit('Not found'); }

/* ---- Handle status change ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action']==='set_status') {
  if (!csrf_verify($_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }
  $new = (string)($_POST['status'] ?? '');
  $allow = ['new','confirmed','in_progress','done','canceled'];
  if (in_array($new, $allow, true)) {
    $st = pdo()->prepare("UPDATE bookings SET status=? WHERE id=?");
    $st->execute([$new, $id]);
  }
  header('Location: /admin/order-view.php?id='.$id);
  exit;
}

/* ---- Read booking ---- */
$row = db_row("
  SELECT b.*, c.name AS city_name
  FROM bookings b
  JOIN cities c ON c.id=b.city_id
  WHERE b.id=?
  LIMIT 1
", [$id]);

if (!$row) { http_response_code(404); exit('Not found'); }

$csrf = csrf_token();

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
  return '<span style="display:inline-block;padding:4px 10px;border-radius:999px;background:'.$bg.';color:#fff;font-size:12px">'.$txt.'</span>';
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đơn #<?= (int)$row['id'] ?></title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;max-width:900px;margin:20px auto;padding:0 16px}
    .muted{color:#666;font-size:13px}
    .card{border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;margin-top:12px}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    input[type="text"],select,textarea{width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:8px}
    button.btn,a.btn{padding:8px 12px;border-radius:8px;background:#111;color:#fff;text-decoration:none;border:0;cursor:pointer}
    a.btn.gray{background:#6b7280}
    pre.note{white-space:pre-wrap;line-height:1.6;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:10px}
    .kv{display:grid;grid-template-columns:160px 1fr;gap:8px;margin:6px 0}
    .kv b{color:#374151}
  </style>
</head>
<body>
  <div class="row" style="justify-content:space-between">
    <div>
      <h1 style="margin:0">Đơn #<?= (int)$row['id'] ?></h1>
      <div class="muted">Tạo lúc: <?= e($row['created_at']) ?> · Cập nhật: <?= e($row['updated_at']) ?></div>
    </div>
    <div class="row">
      <a class="btn gray" href="/admin/orders.php">← Danh sách</a>
      <a class="btn gray" href="/admin/">Dashboard</a>
    </div>
  </div>

  <div class="card">
    <div class="grid">
      <div>
        <div class="kv"><b>Thành phố</b><div><?= e($row['city_name']) ?></div></div>
        <div class="kv"><b>Ngôn ngữ</b><div><?= e($row['lang_code']) ?></div></div>
        <div class="kv"><b>Khách hàng</b><div><?= e($row['customer_name']) ?></div></div>
        <div class="kv"><b>Email</b><div><?= e($row['email']) ?></div></div>
        <div class="kv"><b>Số điện thoại</b><div><?= e($row['phone']) ?></div></div>
      </div>
      <div>
        <div class="kv"><b>Trạng thái</b>
          <div>
            <?= badge_status($row['status']) ?>
            <form method="post" action="" class="row" style="margin-top:8px">
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <input type="hidden" name="action" value="set_status">
              <select name="status">
                <option value="new"         <?= $row['status']==='new'?'selected':'' ?>>Mới</option>
                <option value="confirmed"   <?= $row['status']==='confirmed'?'selected':'' ?>>Đã xác nhận</option>
                <option value="in_progress" <?= $row['status']==='in_progress'?'selected':'' ?>>Đang xử lý</option>
                <option value="done"        <?= $row['status']==='done'?'selected':'' ?>>Hoàn thành</option>
                <option value="canceled"    <?= $row['status']==='canceled'?'selected':'' ?>>Huỷ</option>
              </select>
              <button class="btn" type="submit">Cập nhật</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div style="margin-top:12px">
      <b>Ghi chú / Địa chỉ & thời gian phục vụ</b>
      <pre class="note"><?= e($row['note']) ?></pre>
    </div>
  </div>
</body>
</html>
