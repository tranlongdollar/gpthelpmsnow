<?php
declare(strict_types=1);

/**
 * Massagenow Router — URL dạng /{lang}/{slug}
 * Ví dụ: /vi/ha-noi, /en/ho-chi-minh
 * - Kiểm tra & chọn ngôn ngữ
 * - Tải city theo slug (published)
 * - Nạp i18n (page.massageteam) + thay biến %thanh_pho%, %year%
 * - Nạp services/staff theo city (Bước 18)
 * - Chuẩn bị canonical + hreflang
 * - Gọi view /views/city.php để render
 */

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/i18n.php';

/* -----------------------
   Helpers (fallback)
------------------------*/
// escape HTML
if (!function_exists('e')) {
  function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}
// base URL
if (!function_exists('base_url')) {
  function base_url(): string {
    if (defined('BASE_URL') && BASE_URL) return rtrim(BASE_URL, '/');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
  }
}
// to_slug (đề phòng cần dùng thêm)
if (!function_exists('to_slug')) {
  function to_slug(string $s): string {
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    $s = preg_replace('/[^a-z0-9]+/i', '-', $s);
    return trim($s, '-') ?: 'n-a';
  }
}
// Fallback i18n loader nếu app/i18n.php chưa có i18n_load_page(...)
if (!function_exists('i18n_load_page')) {
  function i18n_load_page(PDO $pdo, string $pageKey, string $langCode): array {
    $stmt = $pdo->prepare("SELECT text_key, text_html FROM i18n_texts WHERE page_key=? AND lang_code=?");
    $stmt->execute([$pageKey, $langCode]);
    $out = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) $out[$r['text_key']] = (string)$r['text_html'];
    return $out;
  }
}
// Thay biến trong text i18n
if (!function_exists('i18n_replace_vars')) {
  function i18n_replace_vars(string $text, array $vars): string {
    foreach ($vars as $k => $v) $text = str_replace('%'.$k.'%', (string)$v, $text);
    return $text;
  }
}

/* -------------------------------------------------
   1) Phân tích URL: /{lang}/{slug}[/*... bỏ qua]
--------------------------------------------------*/
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$uri  = trim($path, '/');             // "vi/ha-noi" hoặc "vi"
$seg  = $uri === '' ? [] : explode('/', $uri);

// Lấy danh sách mã ngôn ngữ (default là record đầu tiên)
$langRows = db_select("SELECT code, is_default FROM languages ORDER BY is_default DESC, code");
$langList = array_map(fn($r) => $r['code'], $langRows);
$defaultLang = $langList[0] ?? 'vi';

// Nếu không có segment → redirect về lang mặc định (có thể sau này là trang chủ theo lang)
if (count($seg) === 0) {
  header("Location: /{$defaultLang}/", true, 302);
  exit;
}

// Segment 1 phải là mã ngôn ngữ
$lang = $seg[0] ?? '';
if (!in_array($lang, $langList, true)) {
  // Không đúng mã ngôn ngữ → chuyển về lang mặc định, giữ phần còn lại
  $rest = implode('/', array_slice($seg, 1));
  $dest = '/' . $defaultLang . ($rest ? '/' . $rest : '/');
  header("Location: {$dest}", true, 302);
  exit;
}

// Nếu chỉ có /{lang} → (tuỳ bạn xử lý landing theo ngôn ngữ). Tạm 404 gọn.
if (count($seg) === 1) {
  http_response_code(404);
  echo "Not Found";
  exit;
}

/* -------------------------------------------------
   2) Lấy slug & bản ghi city (published)
--------------------------------------------------*/
$slug = $seg[1] ?? '';
$city = null;
if ($slug !== '') {
  $city = db_row("SELECT id, name, slug, status FROM cities WHERE slug=? AND status='published' LIMIT 1", [$slug]);
}
if (!$city) {
  http_response_code(404);
  echo "City not found";
  exit;
}

/* -------------------------------------------------
   3) Nạp i18n cho page hiển thị city
--------------------------------------------------*/
$pageKey = 'page.massageteam';
$txt = i18n_load_page(pdo(), $pageKey, $lang);

// Chuẩn bị biến thay thế trong text i18n
$vars = [
  'thanh_pho' => $city['name'],
  'year'      => date('Y'),
];
// Hàm tiện: lấy text kèm thay biến, fallback rỗng
if (!function_exists('t')) {
  function t(array $txt, string $key, array $vars = []): string {
    $val = $txt[$key] ?? '';
    return $vars ? i18n_replace_vars($val, $vars) : $val;
  }
}

/* -------------------------------------------------
   4) Bước 18 — NẠP DỮ LIỆU city: services + staff (+ skills)
--------------------------------------------------*/
// SERVICES theo city (đã gán & active) + tên theo ngôn ngữ
$services = [];
$rows = db_select("
  SELECT s.id, s.slug, s.duration_min, cs.order_no, cs.price
  FROM city_services cs
  JOIN services s ON s.id = cs.service_id AND s.active=1
  WHERE cs.city_id=? AND cs.active=1
  ORDER BY cs.order_no ASC, s.order_no ASC, s.slug ASC, s.duration_min ASC
", [$city['id']]);

foreach ($rows as $r) {
  $name = db_value(
    "SELECT name FROM service_i18n WHERE service_id=? AND lang_code=? LIMIT 1",
    [$r['id'], $lang]
  );
  $services[] = [
    'id'       => (int)$r['id'],
    'slug'     => $r['slug'],
    'duration' => (int)$r['duration_min'],
    'name'     => $name ?: $r['slug'],
    'price'    => (int)$r['price'],
  ];
}

// STAFF theo city (đã gán & active) + i18n + skills i18n
$staff = [];
$st = db_select("
  SELECT st.id, st.photo_url, cs.order_no
  FROM city_staff cs
  JOIN staff st ON st.id = cs.staff_id AND st.active=1
  WHERE cs.city_id=? AND cs.active=1
  ORDER BY cs.order_no ASC, st.order_no ASC, st.id ASC
", [$city['id']]);

foreach ($st as $row) {
  $trow = db_row(
    "SELECT name, title, tagline FROM staff_i18n WHERE staff_id=? AND lang_code=? LIMIT 1",
    [$row['id'], $lang]
  ) ?: ['name'=>'','title'=>'','tagline'=>''];

  $skills = [];
  $sk = db_select(
    "SELECT id, percent FROM staff_skills WHERE staff_id=? ORDER BY order_no ASC, id ASC",
    [$row['id']]
  );
  foreach ($sk as $srow) {
    $label = db_value(
      "SELECT label FROM staff_skill_i18n WHERE skill_id=? AND lang_code=? LIMIT 1",
      [$srow['id'], $lang]
    );
    if ($label !== null && $label !== '') {
      $skills[] = ['label' => $label, 'percent' => (int)$srow['percent']];
    }
  }

  $staff[] = [
    'id'        => (int)$row['id'],
    'photo_url' => $row['photo_url'],
    'order_no'  => (int)$row['order_no'],
    'name'      => $trow['name'],
    'title'     => $trow['title'],
    'tagline'   => $trow['tagline'],
    'skills'    => $skills,
  ];
}

/* -------------------------------------------------
   5) SEO: canonical + hreflang (dùng dạng prefix /{lang}/{slug})
--------------------------------------------------*/
$canonical = base_url() . '/' . $lang . '/' . $city['slug'];
$hreflangs = [];
foreach ($langList as $code) {
  $hreflangs[$code] = base_url() . '/' . $code . '/' . $city['slug'];
}

/* -------------------------------------------------
   6) Gọi view hiển thị
   - View /views/city.php dùng: $city, $lang, $txt, $services, $staff
   - Có thể dùng $canonical, $hreflangs nếu view hỗ trợ
--------------------------------------------------*/
require __DIR__ . '/../views/city.php';
if (isset($_GET['debug'])) {
  header('Content-Type: text/plain; charset=utf-8');
  echo "PATH=" . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
  echo "Segments=" . json_encode($seg) . "\n";
  echo "Lang=" . $lang . " | Slug=" . $slug . "\n";
  echo "City=" . json_encode($city, JSON_UNESCAPED_UNICODE) . "\n";
  echo "services=" . count($services ?? []) . " | staff=" . count($staff ?? []) . "\n";
  exit;
}

