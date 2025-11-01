<?php
declare(strict_types=1);

/* QUICK DIAG FOR ROUTER PREFIX /{lang}/{slug} */
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';

header('Content-Type: text/plain; charset=utf-8');

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$uri  = trim($path, '/');
$seg  = $uri === '' ? [] : explode('/', $uri);

echo "REQUEST_URI: {$path}\n";
echo "Segments: " . json_encode($seg, JSON_UNESCAPED_UNICODE) . "\n\n";

/* languages */
$langRows = db_select("SELECT code, is_default FROM languages ORDER BY is_default DESC, code");
$langList = array_map(fn($r)=>$r['code'], $langRows);
$defaultLang = $langList[0] ?? 'vi';
echo "Languages: " . json_encode($langList) . " | default={$defaultLang}\n";

$lang = $seg[0] ?? '';
$slug = $seg[1] ?? '';
echo "Lang: {$lang}\nSlug: {$slug}\n\n";

/* city */
$city = null;
if ($slug !== '') {
  $city = db_row("SELECT id, name, slug, status FROM cities WHERE slug=? LIMIT 1", [$slug]);
}
echo "City row: " . json_encode($city, JSON_UNESCAPED_UNICODE) . "\n";
if ($city && $city['status'] !== 'published') {
  echo "⚠ City tìm thấy nhưng status != 'published'\n";
}

/* thử nạp services + staff giống index.php */
if ($city) {
  $rows = db_select("
    SELECT s.id, s.slug, s.duration_min, cs.order_no, cs.price
    FROM city_services cs
    JOIN services s ON s.id = cs.service_id AND s.active=1
    WHERE cs.city_id=? AND cs.active=1
    ORDER BY cs.order_no ASC, s.order_no ASC, s.slug ASC, s.duration_min ASC
  ", [$city['id']]);
  echo "Services assigned: " . count($rows) . "\n";

  $st = db_select("
    SELECT st.id, st.photo_url, cs.order_no
    FROM city_staff cs
    JOIN staff st ON st.id = cs.staff_id AND st.active=1
    WHERE cs.city_id=? AND cs.active=1
    ORDER BY cs.order_no ASC, st.order_no ASC, st.id ASC
  ", [$city['id']]);
  echo "Staff assigned: " . count($st) . "\n";
}

echo "\nGợi ý:\n";
echo "- Nếu Languages rỗng: INSERT ít nhất 'vi' làm is_default=1.\n";
echo "- Nếu City row = null: sai slug hoặc chưa có record.\n";
echo "- Nếu City có nhưng status != published: UPDATE về 'published'.\n";
echo "- Nếu Services/Staff = 0: vào /admin/city_assign.php?id={city_id} để tick và Lưu.\n";
