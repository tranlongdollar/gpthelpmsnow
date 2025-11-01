<?php
declare(strict_types=0);

/**
 * Router prefix /{lang}/{slug}
 * - "/"           → views/home.php
 * - "/{lang}/"    → views/select-city.php
 * - "/{lang}/{slug}" → views/city.php
 * Có chế độ debug: thêm ?debug=1 để bật hiển thị lỗi + dump trạng thái.
 */

/* ---------- SAFE REQUIRE ---------- */
function __safe_require(string $file) {
  if (!is_file($file)) { throw new RuntimeException("Missing file: $file"); }
  require $file;
}
try {
  __safe_require(__DIR__ . '/../app/config.php');
  __safe_require(__DIR__ . '/../app/db.php');
  // i18n có thể chưa có các hàm mới → vẫn require và sẽ kiểm tra tồn tại sau
  __safe_require(__DIR__ . '/../app/i18n.php');
} catch (Throwable $e) {
  if ($__DEBUG) { http_response_code(500); echo "BOOT ERROR: ".$e->getMessage(); exit; }
  http_response_code(500); exit;
}

/* ---------- HELPERS ---------- */
if (!function_exists('e')) {
  function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}
if (!function_exists('db_select') || !function_exists('db_row') || !function_exists('db_value')) {
  http_response_code(500);
  echo $__DEBUG ? "db.php is missing helper functions (db_select/db_row/db_value)." : '';
  exit;
}
$pdo = pdo();


/* languages */
function get_default_lang(PDO $pdo): string {
  $row = db_row("SELECT code FROM languages WHERE is_default=1 LIMIT 1");
  return $row['code'] ?? 'vi';
}
function get_all_langs(PDO $pdo): array {
  $rows = db_select("SELECT code FROM languages ORDER BY is_default DESC, code");
  return array_map(fn($r) => $r['code'], $rows);
}
function is_supported_lang(PDO $pdo, string $code): bool {
  static $cache; if ($cache===null) $cache = get_all_langs($pdo);
  return in_array($code, $cache, true);
}

/* i18n loader (tự fallback) */
function load_i18n_page(PDO $pdo, string $ns, string $lang, string $defaultLang): array {
  if (function_exists('tr_load_map')) {
    $map = tr_load_map($pdo, $ns, $lang, $defaultLang);
    return is_array($map) ? $map : [];
  }
  if (function_exists('i18n_load')) {
    $cur = i18n_load($pdo, $ns, $lang) ?: [];
    $def = i18n_load($pdo, $ns, $defaultLang) ?: [];
    return array_merge($def, $cur);
  }
  return []; // không có hệ i18n → trả rỗng, view sẽ xử lý text mặc định
}
function t(array $txt, string $key, array $vars=[]): string {
  $val = $txt[$key] ?? '';
  foreach ($vars as $k=>$v) $val = str_replace('%'.$k.'%', (string)$v, $val);
  return $val;
}

/* city/data */
function fetch_city_by_slug(PDO $pdo, string $slug): ?array {
  return db_row("SELECT id, name, slug, status FROM cities WHERE slug=? AND status='published' LIMIT 1", [$slug]);
}
/*function fetch_services_by_city(PDO $pdo, int $cityId, string $lang): array {
  $rows = db_select("
    SELECT s.id, s.slug, s.duration_min, cs.price, cs.order_no
    FROM city_services cs
    JOIN services s ON s.id=cs.service_id AND s.active=1
    WHERE cs.city_id=? AND cs.active=1
    ORDER BY cs.order_no ASC, s.order_no ASC, s.slug ASC, s.duration_min ASC
  ", [$cityId]);
  if (!$rows) return [];
  $ids = array_map(fn($r)=>(int)$r['id'], $rows);
  $ph = implode(',', array_fill(0, count($ids), '?'));
  $names = db_select("SELECT service_id, name FROM service_i18n WHERE lang_code=? AND service_id IN ($ph)", array_merge([$lang], $ids));
  $map=[]; foreach ($names as $n) $map[(int)$n['service_id']]=$n['name'];
  $out=[];
  foreach ($rows as $r) {
    $out[]=[
      'id'=>(int)$r['id'],
      'slug'=>$r['slug'],
      'duration'=>(int)$r['duration_min'],
      'name'=>$map[(int)$r['id']] ?? $r['slug'],
      'price'=>(int)$r['price'],
    ];
  }
  return $out;
}
*/
function fetch_services_by_city(PDO $pdo, int $cityId, string $lang): array {
  // Thử lấy theo city mapping trước
  $rows = db_select("
    SELECT s.id, s.slug, s.duration_min, cs.price, cs.order_no
    FROM city_services cs
    JOIN services s ON s.id=cs.service_id AND s.active=1
    WHERE cs.city_id=? AND cs.active=1
    ORDER BY cs.order_no ASC, s.order_no ASC, s.slug ASC, s.duration_min ASC
  ", [$cityId]);

  // Nếu chưa mapping → fallback toàn cục
  $fallback = false;
  if (!$rows) {
    $rows = db_select("
      SELECT s.id, s.slug, s.duration_min, 0 AS price, s.order_no
      FROM services s
      WHERE s.active=1
      ORDER BY s.order_no ASC, s.slug ASC, s.duration_min ASC
    ");
    $fallback = true;
  }

  if (!$rows) return [];

  $ids = array_map(fn($r)=>(int)$r['id'], $rows);
  $ph = implode(',', array_fill(0, count($ids), '?'));
  $names = db_select(
    "SELECT service_id, name FROM service_i18n WHERE lang_code=? AND service_id IN ($ph)",
    array_merge([$lang], $ids)
  );
  $map=[]; foreach ($names as $n) $map[(int)$n['service_id']]=$n['name'];

  $out=[];
  foreach ($rows as $r) {
    $out[]=[
      'id'       => (int)$r['id'],
      'slug'     => $r['slug'],
      'duration' => (int)$r['duration_min'],
      'name'     => $map[(int)$r['id']] ?? $r['slug'],
      'price'    => isset($r['price']) ? (int)$r['price'] : 0,
      'fallback' => $fallback ? 1 : 0, // có thể dùng để hiển thị nhắc admin
    ];
  }
  return $out;
}
/*
function fetch_staff_by_city(PDO $pdo, int $cityId, string $lang): array {
  $rows = db_select("
    SELECT st.id, st.photo_url, cs.order_no
    FROM city_staff cs
    JOIN staff st ON st.id=cs.staff_id AND st.active=1
    WHERE cs.city_id=? AND cs.active=1
    ORDER BY cs.order_no ASC, st.order_no ASC, st.id ASC
  ", [$cityId]);
  if (!$rows) return [];
  $ids = array_map(fn($r)=>(int)$r['id'], $rows);
  $ph  = implode(',', array_fill(0, count($ids), '?'));
  $i18n = db_select("SELECT staff_id, name, title, tagline FROM staff_i18n WHERE lang_code=? AND staff_id IN ($ph)", array_merge([$lang], $ids));
  $mapI=[]; foreach ($i18n as $i) $mapI[(int)$i['staff_id']]=$i;

  $skills = db_select("SELECT id, staff_id, percent, order_no FROM staff_skills WHERE staff_id IN ($ph) ORDER BY staff_id, order_no, id", $ids);
  $skillIds = array_map(fn($r)=>(int)$r['id'], $skills);
  $mapSkills=[]; foreach ($skills as $s) $mapSkills[(int)$s['staff_id']][]=$s;
  $mapLabel=[];
  if ($skillIds) {
    $ph2 = implode(',', array_fill(0, count($skillIds), '?'));
    $labels = db_select("SELECT skill_id, label FROM staff_skill_i18n WHERE lang_code=? AND skill_id IN ($ph2)", array_merge([$lang], $skillIds));
    foreach ($labels as $l) $mapLabel[(int)$l['skill_id']]=$l['label'];
  }

  $out=[];
  foreach ($rows as $r) {
    $info = $mapI[(int)$r['id']] ?? ['name'=>'','title'=>'','tagline'=>''];
    $sk=[];
    foreach ($mapSkills[(int)$r['id']] ?? [] as $s) {
      $sk[]=['label'=>$mapLabel[(int)$s['id']] ?? '', 'percent'=>(int)$s['percent']];
    }
    $out[]=[
      'id'=>(int)$r['id'],
      'photo_url'=>$r['photo_url'],
      'order_no'=>(int)$r['order_no'],
      'name'=>$info['name'],
      'title'=>$info['title'],
      'tagline'=>$info['tagline'],
      'skills'=>$sk,
    ];
  }
  return $out;
}
*/
function fetch_staff_by_city(PDO $pdo, int $cityId, string $lang): array {
  // Thử lấy theo city mapping trước
  $rows = db_select("
    SELECT st.id, st.photo_url, cs.order_no
    FROM city_staff cs
    JOIN staff st ON st.id=cs.staff_id AND st.active=1
    WHERE cs.city_id=? AND cs.active=1
    ORDER BY cs.order_no ASC, st.order_no ASC, st.id ASC
  ", [$cityId]);

  // Nếu chưa mapping → fallback toàn cục
  if (!$rows) {
    $rows = db_select("
      SELECT st.id, st.photo_url, st.order_no
      FROM staff st
      WHERE st.active=1
      ORDER BY st.order_no ASC, st.id ASC
    ");
  }

  if (!$rows) return [];

  $ids = array_map(fn($r)=>(int)$r['id'], $rows);
  $ph  = implode(',', array_fill(0, count($ids), '?'));

  // Thông tin i18n theo lang
  $i18n = db_select(
    "SELECT staff_id, name, title, tagline FROM staff_i18n WHERE lang_code=? AND staff_id IN ($ph)",
    array_merge([$lang], $ids)
  );
  $mapI=[]; foreach ($i18n as $i) $mapI[(int)$i['staff_id']]=$i;

  // Kỹ năng + i18n nhãn kỹ năng
  $skills = db_select(
    "SELECT id, staff_id, percent, order_no FROM staff_skills WHERE staff_id IN ($ph) ORDER BY staff_id, order_no, id",
    $ids
  );
  $skillIds = array_map(fn($r)=>(int)$r['id'], $skills);
  $mapSkills=[]; foreach ($skills as $s) $mapSkills[(int)$s['staff_id']][]=$s;

  $mapLabel=[];
  if ($skillIds) {
    $ph2 = implode(',', array_fill(0, count($skillIds), '?'));
    $labels = db_select(
      "SELECT skill_id, label FROM staff_skill_i18n WHERE lang_code=? AND skill_id IN ($ph2)",
      array_merge([$lang], $skillIds)
    );
    foreach ($labels as $l) $mapLabel[(int)$l['skill_id']]=$l['label'];
  }

  $out=[];
  foreach ($rows as $r) {
    $info = $mapI[(int)$r['id']] ?? ['name'=>'','title'=>'','tagline'=>''];
    $sk=[];
    foreach ($mapSkills[(int)$r['id']] ?? [] as $s) {
      $sk[]=['label'=>$mapLabel[(int)$s['id']] ?? '', 'percent'=>(int)$s['percent']];
    }
    $out[]=[
      'id'        => (int)$r['id'],
      'photo_url' => $r['photo_url'],
      'order_no'  => (int)$r['order_no'],
      'name'      => $info['name'],
      'title'     => $info['title'],
      'tagline'   => $info['tagline'],
      'skills'    => $sk,
    ];
  }
  return $out;
}

/* ---------- ROUTER ---------- */
try {
  $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
  $path = trim($uriPath, '/');
  $segments = $path === '' ? [] : explode('/', $path);

  $defaultLang = get_default_lang($pdo);

  // /admin → 404
  if (isset($segments[0]) && $segments[0]==='admin') {
    http_response_code(404);
    __safe_require(__DIR__ . '/../views/404.php'); exit;
  }

  // "/" → home
  if (count($segments) === 0) {
    __safe_require(__DIR__ . '/../views/home.php'); exit;
  }

  /*
  // "/{lang}" hoặc "/{lang}/{slug}"
  $first = $segments[0];
  if (is_supported_lang($pdo, $first)) {
    $lang = $first;
    $slug = $segments[1] ?? '';
    if ($slug === '') {
      $cities = db_select("SELECT id, name, slug FROM cities WHERE status='published' ORDER BY name ASC");
      __safe_require(__DIR__ . '/../views/select-city.php'); exit;
    }
    $city = fetch_city_by_slug($pdo, $slug);
    if (!$city) { http_response_code(404); __safe_require(__DIR__ . '/../views/404.php'); exit; }

    $txt       = load_i18n_page($pdo, 'page.massageteam', $lang, $defaultLang);
    $services  = fetch_services_by_city($pdo, (int)$city['id'], $lang);
    $staff     = fetch_staff_by_city($pdo, (int)$city['id'], $lang);
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base   = $scheme.'://'.$host;
    $canonical = $base.'/'.$lang.'/'.$city['slug'];
    $hreflangs=[]; foreach (get_all_langs($pdo) as $lc) $hreflangs[$lc]=$base.'/'.$lc.'/'.$city['slug'];

    // expose biến cho vien
    extract(compact('city','lang','txt','services','staff','canonical','hreflangs','defaultLang'), EXTR_SKIP);
    $viewCity = __DIR__ . '/../views/city.php';
    if (is_file($viewCity)) { require $viewCity; }
    else { header('Content-Type:text/html; charset=utf-8'); echo "<h1>View city chưa sẵn sàng</h1>"; }
    exit;
  }
*/
// "/{lang}" hoặc "/{lang}/{slug}"
$first = $segments[0];
if (is_supported_lang($pdo, $first)) {
  $lang = $first;
  $slug = $segments[1] ?? '';

  if ($slug === '') {
    $cities = db_select("SELECT id, name, slug FROM cities WHERE status='published' ORDER BY name ASC");
    __safe_require(__DIR__ . '/../views/select-city.php'); exit;
  }

  $city = fetch_city_by_slug($pdo, $slug);
  if (!$city) { http_response_code(404); __safe_require(__DIR__ . '/../views/404.php'); exit; }

  $txt      = load_i18n_page($pdo, 'page.massageteam', $lang, $defaultLang);
  $services = fetch_services_by_city($pdo, (int)$city['id'], $lang);
  $staff    = fetch_staff_by_city($pdo, (int)$city['id'], $lang);

  // tên thành phố theo ngôn ngữ
  $cityLocalName = db_value(
    "SELECT name FROM city_i18n WHERE city_id=? AND lang_code=? LIMIT 1",
    [$city['id'], $lang]
  ) ?: $city['name'];

  // SEO
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $base   = $scheme.'://'.$host;
  $canonical = $base.'/'.$lang.'/'.$city['slug'];
  $hreflangs = [];
  foreach (get_all_langs($pdo) as $lc) {
    $hreflangs[$lc] = $base.'/'.$lc.'/'.$city['slug'];
  }

  // expose biến cho view (GIỮ 1 LẦN extract duy nhất)
  extract(compact('city','lang','txt','services','staff','canonical','hreflangs','defaultLang') + [
    'city_name_local' => $cityLocalName,
  ], EXTR_SKIP);

  $viewCity = __DIR__ . '/../views/city.php';
  if (is_file($viewCity)) { require $viewCity; }
  else { header('Content-Type:text/html; charset=utf-8'); echo "<h1>View city chưa sẵn sàng</h1>"; }
  exit;
}

  // Legacy "/{slug}" → 301
  $legacySlug = $segments[0] ?? '';
  if ($legacySlug !== '') {
    $targetLang = (isset($_GET['lang']) && is_supported_lang($pdo, $_GET['lang'])) ? $_GET['lang'] : $defaultLang;
    header('Location: /'.rawurlencode($targetLang).'/'.$legacySlug, true, 301); exit;
  }

  http_response_code(404);
  __safe_require(__DIR__ . '/../views/404.php'); exit;

} catch (Throwable $e) {
  // Chặn 500 im lặng
  http_response_code(500);
  if ($__DEBUG) {
    echo "FATAL: ".$e->getMessage()."<br><pre>".$e->getTraceAsString()."</pre>";
    // Dump nhanh ngữ cảnh
    echo "<hr><pre>Segments: ".htmlspecialchars(print_r($segments ?? [], true))."</pre>";
  }
  // production: im lặng
  exit;
}