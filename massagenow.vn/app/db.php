<?php
declare(strict_types=1);
/**
 * /app/db.php — Kết nối PDO & helper query (PHP thuần)
 * Yêu cầu: đã có /app/config.php định nghĩa DB_* constants.
 *
 * Cách dùng:
 *   require __DIR__ . '/config.php';
 *   require __DIR__ . '/db.php';
 *   $pdo = pdo(); // lấy kết nối
 *   $rows = db_select('SELECT * FROM cities WHERE status=?', ['published']);
 */

// -------------------------
// Singleton PDO connection
// -------------------------
function pdo(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $host = DB_HOST ?? 'localhost';
  $name = DB_NAME ?? '';
  $charset = DB_CHARSET ?? 'utf8mb4';
  $user = DB_USER ?? '';
  $pass = DB_PASS ?? '';

  $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
  $options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    // Tối ưu nhỏ: giữ persistent nếu cần (có thể bật nếu server ổn định)
    // PDO::ATTR_PERSISTENT => true,
  ];

  try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Thiết lập SQL mode & timezone an toàn (tuỳ nhu cầu)
    $pdo->exec("SET NAMES '{$charset}' COLLATE 'utf8mb4_unicode_ci'");
    $pdo->exec("SET sql_mode = ''"); // tránh STRICT gây lỗi seed (tuỳ chọn)
    return $pdo;
  } catch (Throwable $e) {
    http_response_code(500);
    echo "DB connection failed: " . htmlspecialchars($e->getMessage());
    exit;
  }
}

// -------------------------
// Helper query functions
// -------------------------

/**
 * Thực thi câu lệnh SELECT trả về mảng nhiều dòng.
 */
function db_select(string $sql, array $params = []): array {
  $stmt = pdo()->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchAll();
}

/**
 * Trả về 1 dòng (mảng) hoặc null nếu không có.
 */
function db_row(string $sql, array $params = []): ?array {
  $stmt = pdo()->prepare($sql);
  $stmt->execute($params);
  $row = $stmt->fetch();
  return $row === false ? null : $row;
}

/**
 * Trả về 1 giá trị (cột đầu tiên của dòng đầu tiên) hoặc null.
 */
function db_value(string $sql, array $params = []) {
  $stmt = pdo()->prepare($sql);
  $stmt->execute($params);
  $val = $stmt->fetchColumn();
  return $val === false ? null : $val;
}

/**
 * INSERT/UPDATE/DELETE — trả về số dòng ảnh hưởng.
 */
function db_exec(string $sql, array $params = []): int {
  $stmt = pdo()->prepare($sql);
  $stmt->execute($params);
  return $stmt->rowCount();
}

/**
 * Chèn 1 record và trả về lastInsertId().
 */
function db_insert(string $sql, array $params = []): string {
  $stmt = pdo()->prepare($sql);
  $stmt->execute($params);
  return pdo()->lastInsertId();
}

// -------------------------
// Transaction helper
// -------------------------

/**
 * Chạy 1 closure trong transaction.
 * Ví dụ:
 *   db_tx(function() use($city){ ... });
 */
function db_tx(callable $fn) {
  $pdo = pdo();
  try {
    $pdo->beginTransaction();
    $result = $fn($pdo);
    $pdo->commit();
    return $result;
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
  }
}

// -------------------------
// Tiny helpers
// -------------------------

/**
 * Escape output HTML (dùng khi in ra).
 */
function e(?string $s): string {
  return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Sinh slug đơn giản từ tên (VD cho cities).
 * (Bản đầy đủ sẽ chuyển tiếng Việt có dấu → không dấu; đây chỉ bản tối giản.)
 */
function to_slug(string $str): string {
  $str = mb_strtolower($str, 'UTF-8');
  // Chuyển ký tự có dấu tiếng Việt về không dấu (đơn giản)
  $replacements = [
    'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
    'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
    'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
    'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
    'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
    'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
    'đ'=>'d'
  ];
  $str = strtr($str, $replacements);
  $str = preg_replace('/[^a-z0-9]+/u', '-', $str);
  $str = trim($str, '-');
  return $str ?: 'n-a';
}
