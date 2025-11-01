<?php
declare(strict_types=1);
/**
 * /app/session.php — Khởi tạo session an toàn
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
  // Cookie settings
  $secure   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  $httponly = true;
  $samesite = 'Lax'; // hoặc 'Strict' nếu admin không nhúng cross-site
  // PHP 7.3+: samesite qua array
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $secure,
    'httponly' => $httponly,
    'samesite' => $samesite,
  ]);
  session_name('MNSESSID');
  session_start();
}
