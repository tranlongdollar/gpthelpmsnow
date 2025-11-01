<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/mail.php'; // <- thêm helper mail

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

  // ==== GỬI EMAIL THÔNG BÁO ====
  $adminTo = defined('MAIL_ADMIN_TO') ? MAIL_ADMIN_TO : '';
  $fallbackAdmin = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : ''; // tương thích cũ
  $toAdmin = $adminTo !== '' ? $adminTo : $fallbackAdmin;

  if ($toAdmin !== '') {
    // Chủ đề & nội dung
    $subject = "[Booking] #{$orderId} — " . ($city['name'] ?? 'City');

    // Nội dung HTML cho admin
    $htmlAdmin = '
      <h3>Đơn đặt lịch mới</h3>
      <ul>
        <li><b>ID:</b> #' . (int)$orderId . '</li>
        <li><b>Thành phố:</b> ' . htmlspecialchars((string)$city['name']) . ' (ID ' . (int)$city_id . ')</li>
        <li><b>Ngôn ngữ:</b> ' . htmlspecialchars($lang_code) . '</li>
        <li><b>Khách hàng:</b> ' . htmlspecialchars($name) . '</li>
        <li><b>Email:</b> ' . htmlspecialchars($email) . '</li>
        <li><b>Phone:</b> ' . htmlspecialchars($phone) . '</li>
        <li><b>Ghi chú:</b><br>' . nl2br(htmlspecialchars($note)) . '</li>
        <li><b>Thời gian:</b> ' . date('Y-m-d H:i:s') . '</li>
      </ul>';

    // Bản text alternative
    $textAdmin =
      "Đơn đặt lịch mới:\n" .
      "- ID: #{$orderId}\n" .
      "- Thành phố: {$city['name']} (ID {$city_id})\n" .
      "- Ngôn ngữ: {$lang_code}\n" .
      "- Khách hàng: {$name}\n" .
      "- Email: {$email}\n" .
      "- Phone: {$phone}\n" .
      "- Ghi chú:\n{$note}\n" .
      "- Thời gian: " . date('Y-m-d H:i:s');

    // CC: email khách + email phụ
    $ccList = [];
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $ccList[] = $email; // CC khách hàng
    }
    $ccList[] = 'tranlong.dollar@gmail.com'; // CC email phụ

    // Gửi mail admin (best-effort: không fail API)
    @mail_send_html(
      $toAdmin,
      $subject,
      $htmlAdmin,
      $textAdmin,
      $email,     // replyTo: KH
      $ccList,    // CC
      []          // BCC
    );

    // Gửi mail xác nhận riêng cho KH (best-effort, không chặn luồng)
    $subjectCus = 'Xác nhận đặt lịch #' . $orderId;
    $htmlCus = '
      <p>Xin chào ' . htmlspecialchars($name) . ',</p>
      <p>Chúng tôi đã nhận được yêu cầu đặt lịch của bạn tại <b>' . htmlspecialchars((string)$city['name']) . '</b>.</p>
      <p><b>Thông tin tóm tắt:</b></p>
      <ul>
        <li>Email: ' . htmlspecialchars($email) . '</li>
        <li>Phone: ' . htmlspecialchars($phone) . '</li>
        <li>Ghi chú: ' . nl2br(htmlspecialchars($note)) . '</li>
      </ul>
      <p>Chúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất.</p>
      <p>Trân trọng,<br>MassageNow</p>';
    $textCus =
      "Xin chào {$name},\n" .
      "Chúng tôi đã nhận được yêu cầu đặt lịch tại {$city['name']}.\n" .
      "Email: {$email}\nPhone: {$phone}\nGhi chú: {$note}\n" .
      "Chúng tôi sẽ liên hệ lại sớm nhất.\nMassageNow";

    @mail_send_html($email, $subjectCus, $htmlCus, $textCus, $toAdmin);
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
