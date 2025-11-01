<?php
declare(strict_types=1);
/**
 * /app/auth.php — Bước 13: helper đăng nhập admin
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

/** Lấy user hiện tại (mảng) hoặc null */
function auth_user(): ?array {
  return $_SESSION['auth_user'] ?? null;
}

/** Bắt buộc đăng nhập, nếu không chuyển tới /admin/login.php */
function auth_require_login(): void {
  if (!auth_user()) {
    header('Location: /admin/login.php');
    exit;
  }
}

/** Đăng xuất */
function auth_logout(): void {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
}

/** CSRF token helpers */
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}
function csrf_verify(string $token): bool {
  return hash_equals($_SESSION['csrf'] ?? '', $token);
}

/** Ghi log đăng nhập */
function auth_log(string $email, string $status, ?int $userId = null): void {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '';
  db_exec("INSERT INTO auth_logins (email, ip, status, user_id, created_at) VALUES (?,?,?,?,NOW())",
          [$email, $ip, $status, $userId]);
}

/** Kiểm tra giới hạn đăng nhập (thất bại) trong khung thời gian */
function auth_too_many_attempts(string $email, int $maxFails = 7, int $minutes = 15): bool {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '';
  $since = date('Y-m-d H:i:s', time() - $minutes*60);
  $failIp = (int)db_value("SELECT COUNT(*) FROM auth_logins WHERE status='fail' AND ip=? AND created_at>=?", [$ip, $since]);
  if ($failIp >= $maxFails) return true;
  $failEmail = (int)db_value("SELECT COUNT(*) FROM auth_logins WHERE status='fail' AND email=? AND created_at>=?", [$email, $since]);
  return $failEmail >= $maxFails;
}

/** Thực hiện đăng nhập */
function auth_login(string $email, string $password): bool {
  $email = trim(mb_strtolower($email));
  if ($email === '' || $password === '') return false;

  // Throttle trước khi chạy truy vấn nặng
  if (auth_too_many_attempts($email)) {
    // Ghi log nhẹ để theo dõi
    auth_log($email, 'fail', null);
    return false;
  }

  $user = db_row("SELECT id, name, email, password_hash, role FROM users WHERE email=? LIMIT 1", [$email]);
  // Timing-safe: luôn verify để tránh timing leak (dùng dummy hash nếu không có)
  $hash = $user['password_hash'] ?? password_hash('dummy', PASSWORD_BCRYPT);
  $ok = password_verify($password, $hash);

  if ($user && $ok) {
    // OK
    session_regenerate_id(true);
    $_SESSION['auth_user'] = [
      'id' => (int)$user['id'],
      'name' => $user['name'],
      'email' => $user['email'],
      'role' => $user['role'],
    ];
    auth_log($email, 'ok', (int)$user['id']);
    return true;
  } else {
    auth_log($email, 'fail', $user['id'] ?? null);
    return false;
  }
}
