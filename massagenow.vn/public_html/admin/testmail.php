<?php declare(strict_types=1);

require __DIR__ . '/../../app/config.php';
require __DIR__ . '/../../app/mail.php';

$to = defined('MAIL_ADMIN_TO') ? MAIL_ADMIN_TO : '';
if ($to === '') {
    echo "MAIL_ADMIN_TO chưa cấu hình trong config.php";
    exit;
}

$r = mail_send_html(
    $to,
    'Test email từ MassageNow',
    '<b>Xin chào</b><br>Đây là mail test (HTML).',
    "Xin chào\nĐây là mail test (Text).",
    null,
    // CC thêm chính quản trị để thấy luồng (có thể bỏ):
    ['tranlong.dollar@gmail.com'],
    ['quan.nguyenvan.it@gmail.com']
);

var_dump($r);
echo "<hr>Log (nếu fail): /tmp/massagenow_mail.log";
