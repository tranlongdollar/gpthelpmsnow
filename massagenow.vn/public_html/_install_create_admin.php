<?php
declare(strict_types=1);
/**
 * One-time Admin Creator
 * Path suggestion: /home/massagenow/domains/massagenow.vn/public_html/_install_create_admin.php
 * After success, DELETE this file immediately.
 */

require __DIR__ . '/../app/config.php';

// ---------- Simple access protection ----------
// Change this to a long random string, then call the page with ?token=THE_SAME_STRING
const INSTALL_SECRET = '###****#### he lô ai do dung hack tui *** tui mắc cười á';

if (INSTALL_SECRET !== '') {
  $token = $_GET['token'] ?? '';
  if ($token !== INSTALL_SECRET) {
    http_response_code(403);
    echo 'Forbidden. Append ?token=' . INSTALL_SECRET . ' after you change INSTALL_SECRET inside this file.';
    exit;
  }
}

// ---------- PDO Connection ----------
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
try {
  $pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo "DB connect error: " . htmlspecialchars($e->getMessage());
  exit;
}

// ---------- Handle POST ----------
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm = $_POST['confirm'] ?? '';

  if ($name === '') $errors[] = 'Tên bắt buộc.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
  if (strlen($password) < 10) $errors[] = 'Mật khẩu tối thiểu 10 ký tự.';
  if ($password !== $confirm) $errors[] = 'Mật khẩu nhập lại không khớp.';

  if (!$errors) {
    try {
      // Check exists
      $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
      $stmt->execute([$email]);
      if ($stmt->fetch()) {
        $errors[] = 'Email đã tồn tại, vui lòng dùng email khác.';
      } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role,created_at,updated_at) VALUES (?,?,?,?,NOW(),NOW())");
        $stmt->execute([$name, $email, $hash, 'admin']);
        $success = "Tạo tài khoản admin thành công cho " . htmlspecialchars($email);
      }
    } catch (Throwable $e) {
      $errors[] = 'Lỗi khi tạo tài khoản: ' . $e->getMessage();
    }
  }
}

?><!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tạo tài khoản admin (one-time)</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; max-width: 640px; margin: 40px auto; padding: 0 16px; }
    .card { border: 1px solid #e1e1e1; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    h1 { margin-top: 0; font-size: 22px; }
    label { display: block; margin-top: 12px; }
    input[type="text"], input[type="email"], input[type="password"] {
      width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 14px;
    }
    .btn { margin-top: 16px; padding: 10px 16px; border: 0; background: #111; color: #fff; border-radius: 8px; cursor: pointer; }
    .alert { padding: 12px; border-radius: 8px; margin-bottom: 10px; }
    .alert.error { background: #ffe8e6; color: #8a1f11; }
    .alert.ok { background: #e6ffed; color: #0b5d1e; }
    .muted { color: #666; font-size: 13px; }
    .danger { color: #8a1f11; font-weight: 600; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Tạo tài khoản admin</h1>
    <p class="muted">Dùng 1 lần để khởi tạo admin. <span class="danger">XÓA file này ngay sau khi tạo xong.</span></p>

    <?php if ($errors): ?>
      <div class="alert error">
        <ul style="margin:0 0 0 16px;">
          <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert ok">
        <?= $success ?>
      </div>
      <p class="muted">Hãy đăng nhập ở trang quản trị (sẽ cấu hình tại Bước 13). Nhớ xóa file này: <code><?= htmlspecialchars(__FILE__) ?></code></p>
    <?php else: ?>
    <form method="post" autocomplete="off">
      <label>Tên hiển thị
        <input type="text" name="name" placeholder="VD: Admin" required>
      </label>
      <label>Email quản trị
        <input type="email" name="email" placeholder="VD: admin@massagenow.vn" required>
      </label>
      <label>Mật khẩu
        <input type="password" name="password" placeholder="Tối thiểu 10 ký tự" required>
      </label>
      <label>Nhập lại mật khẩu
        <input type="password" name="confirm" placeholder="Nhập lại" required>
      </label>
      <button class="btn" type="submit">Tạo tài khoản admin</button>
    </form>
    <?php endif; ?>
  </div>
</body>
</html>
