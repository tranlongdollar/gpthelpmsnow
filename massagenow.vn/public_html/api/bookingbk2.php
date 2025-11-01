<?php
declare(strict_types=1);

// API đặt lịch
require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/mail.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'Method Not Allowed']); exit;
  }

  $pdo = pdo();

  // Nhận dữ liệu
  $city_id   = (int)($_POST['city_id'] ?? 0);
  $lang_code = trim($_POST['lang_code'] ?? '');
  $name      = trim($_POST['name'] ?? '');
  $email     = trim($_POST['email'] ?? '');
  $phone     = trim($_POST['phone'] ?? '');
  $note      = trim($_POST['note'] ?? '');

  // Validate
  if ($city_id <= 0 || $name === '' || $email === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'missing_fields']); exit;
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'invalid_email']); exit;
  }

  // City
  $city = db_row("SELECT id,name,slug FROM cities WHERE id=? LIMIT 1", [$city_id]);
  if (!$city) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'city_not_found']); exit;
  }

  // Insert booking
  $stmt = $pdo->prepare("INSERT INTO bookings (city_id, lang_code, customer_name, email, phone, note, status, created_at) VALUES (?,?,?,?,?,?, 'new', NOW())");
  $stmt->execute([$city['id'], $lang_code, $name, $email, $phone, $note]);
  $orderId = (int)$pdo->lastInsertId();

  // Email ADMIN
  $adminTo = defined('MAIL_ADMIN_TO') ? MAIL_ADMIN_TO : '';
  if ($adminTo === '') {
    // Nếu chưa cấu hình, báo lỗi rõ
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'admin_email_not_set']); exit;
  }

  $subjectAdmin = 'New booking #' . $orderId . ' — ' . $city['name'];
  $htmlAdmin = '
    <h3>New Booking: #' . (int)$orderId . '</h3>
    <p><b>City:</b> ' . htmlspecialchars($city['name']) . '</p>
    <p><b>Lang:</b> ' . htmlspecialchars($lang_code) . '</p>
    <p><b>Name:</b> ' . htmlspecialchars($name) . '</p>
    <p><b>Email:</b> ' . htmlspecialchars($email) . '</p>
    <p><b>Phone:</b> ' . htmlspecialchars($phone) . '</p>
    <p><b>Note:</b><br>' . nl2br(htmlspecialchars($note)) . '</p>
    <p>View: <a href="' . (defined('BASE_URL') ? rtrim(BASE_URL,'/') : '') . '/admin/order-view.php?id=' . $orderId . '">Order #' . $orderId . '</a></p>
  ';
  $sendAdmin = mail_send_html($adminTo, $subjectAdmin, $htmlAdmin);

  if (!$sendAdmin['ok']) {
    // Rollback trạng thái hoặc giữ, nhưng trả lỗi vì yêu cầu "bắt buộc gửi mail"
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'mail_admin_failed']); exit;
  }

  // Email KH xác nhận (best-effort: KHÔNG fail API nếu cái này lỗi)
  $subjectCus = 'Xác nhận đặt lịch #' . $orderId;
  $htmlCus = '
    <p>Xin chào ' . htmlspecialchars($name) . ',</p>
    <p>Chúng tôi đã nhận được yêu cầu đặt lịch của bạn tại <b>' . htmlspecialchars($city['name']) . '</b>.</p>
    <p>Thông tin tóm tắt:</p>
    <ul>
      <li>Email: ' . htmlspecialchars($email) . '</li>
      <li>Phone: ' . htmlspecialchars($phone) . '</li>
      <li>Ghi chú: ' . nl2br(htmlspecialchars($note)) . '</li>
    </ul>
    <p>Chúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất.</p>
    <p>Trân trọng, MassageNow</p>
  ';
  mail_send_html($email, $subjectCus, $htmlCus);

  echo json_encode(['ok'=>true,'order_id'=>$orderId]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'server_error']); // an toàn
}
