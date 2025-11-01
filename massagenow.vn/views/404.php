<?php
declare(strict_types=1);
/** views/404.php — 404 tối giản */
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>404 - Không tìm thấy</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 40px auto; max-width: 720px; padding: 0 16px; }
  </style>
</head>
<body>
  <h1>404 — Không tìm thấy</h1>
  <p>Trang bạn truy cập không tồn tại.</p>
  <p><a href="/">Về trang chọn thành phố</a></p>
</body>
</html>
