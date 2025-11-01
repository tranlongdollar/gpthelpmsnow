<?php
require __DIR__.'/../app/config.php';
$dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
try {
  $pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);
  echo "PDO connected to '".DB_NAME."' successfully.";
} catch (Throwable $e) {
  http_response_code(500);
  echo "DB connect error: ".$e->getMessage();
}
