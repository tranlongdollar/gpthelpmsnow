<?php
declare(strict_types=1);
/**
 * /public_html/admin/login.php — Form đăng nhập
 */
require __DIR__ . '/../../app/auth.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'] ?? '';
  $pass  = $_POST['password'] ?? '';
  $csrf  = $_POST['csrf'] ?? '';
  if (!csrf_verify($csrf)) {
    $err = 'Phiên đăng nhập hết hạn, hãy thử lại.';
  } else {
    if (auth_login($email, $pass)) {
      // Lưu thông tin người dùng vào session sau khi đăng nhập thành công
      $_SESSION['user'] = auth_user(); // Giả sử `auth_user()` trả về thông tin người dùng
      header('Location: /admin/index.php'); // Chuyển hướng tới trang quản trị
      exit;
    } else {
      $err = 'Email hoặc mật khẩu không đúng (hoặc tạm bị khóa do thử sai nhiều).';
    }
  }
}
?><!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đăng nhập Admin</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; max-width: 420px; margin: 56px auto; padding: 0 16px; }
    .card { border: 1px solid #e1e1e1; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    label { display: block; margin-top: 12px; }
    input[type="email"], input[type="password"] { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 14px; }
    .btn { margin-top: 16px; padding: 10px 16px; border: 0; background: #111; color: #fff; border-radius: 8px; cursor: pointer; width: 100%; }
    .err { background: #ffe8e6; color: #8a1f11; padding: 10px 12px; border-radius: 8px; margin-bottom: 10px; }
    .muted { color: #666; font-size: 13px; }
  </style>
</head>
<body>
  <div class="card">
    <h1 style="margin-top:0;font-size:22px;">Đăng nhập quản trị</h1>
    <?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label>Email
        <input type="email" name="email" required>
      </label>
      <label>Mật khẩu
        <input type="password" name="password" required>
      </label>
      <button class="btn" type="submit">Đăng nhập</button>
    </form>
    <p class="muted">Quên mật khẩu? (sẽ thêm ở bước sau)</p>
  </div>
</body>
</html>
