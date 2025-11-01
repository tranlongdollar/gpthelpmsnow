<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';

// Bật debug tạm thời khi test: thêm ?debug=1
$DEBUG = isset($_GET['debug']) && $_GET['debug'] === '1';

function jexit(array $payload, int $code = 200): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jexit(['ok' => false, 'error' => 'Method Not Allowed'], 405);
  }

  // Lấy dữ liệu từ form
  $city_id    = isset($_POST['city_id']) ? (int)$_POST['city_id'] : 0;
  $lang_code  = trim((string)($_POST['lang_code'] ?? 'vi'));
  $name       = trim((string)($_POST['name']      ?? ''));
  $email      = trim((string)($_POST['email']     ?? ''));
  $phone      = trim((string)($_POST['phone']     ?? ''));
  $note       = trim((string)($_POST['note']      ?? ''));

  // Validate cơ bản
  if ($city_id <= 0) {
    throw new RuntimeException('Thiếu hoặc sai city_id');
  }
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new RuntimeException('Email bắt buộc và phải hợp lệ');
  }

  $pdo = pdo();

  // Kiểm tra city publish
  $city = db_row("SELECT id, name FROM cities WHERE id=? AND status='published' LIMIT 1", [$city_id]);
  if (!$city) {
    throw new RuntimeException('Thành phố không tồn tại hoặc chưa publish');
  }

  // Lưu đơn
  $stmt = $pdo->prepare("
    INSERT INTO bookings (city_id, lang_code, customer_name, email, phone, note, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, 'new', NOW())
  ");
  $stmt->execute([$city_id, $lang_code, $name, $email, $phone, $note]);
  $orderId = (int)$pdo->lastInsertId();

  // Gửi email thông báo (nếu cấu hình ADMIN_EMAIL trong app/config.php)
  $admin = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : '';
  if ($admin !== '') {
    $subject = "[Booking] #$orderId — " . ($city['name'] ?? 'City');
    $body =
      "Đơn đặt lịch mới:\n".
      "- ID: #{$orderId}\n".
      "- Thành phố: {$city['name']} (ID {$city_id})\n".
      "- Ngôn ngữ: {$lang_code}\n".
      "- Khách hàng: {$name}\n".
      "- Email: {$email}\n".
      "- Phone: {$phone}\n".
      "- Ghi chú:\n{$note}\n".
      "- Thời gian: " . date('Y-m-d H:i:s');

    $headers = "From: noreply@".$_SERVER['HTTP_HOST']."\r\n".
               "Reply-To: ".$email."\r\n".
               "Content-Type: text/plain; charset=UTF-8\r\n";

    // Không chặn luồng nếu mail lỗi
    @mail($admin, '=?UTF-8?B?'.base64_encode($subject).'?=', $body, $headers);
  }

  jexit(['ok' => true, 'id' => $orderId], 200);

} catch (Throwable $e) {
  // Ghi log vào error_log của PHP để tra cứu
  error_log("[booking.php] ".$e->getMessage()." @ ".$e->getFile().":".$e->getLine());

  if ($DEBUG) {
    jexit([
      'ok'    => false,
      'error' => $e->getMessage(),
      'trace' => $e->getTraceAsString()
    ], 400);
  } else {
    jexit(['ok' => false, 'error' => 'Server error'], 400);
  }
}
