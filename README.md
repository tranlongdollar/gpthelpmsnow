# HƯỚNG DẪN KỸ THUẬT (huongdankythuat.md / massagenow.md)

Tài liệu này tóm tắt kiến trúc, database, routing, phân quyền, và các luồng chính của hệ thống **massagenow.vn** để dev hoặc admin sau này đọc tiếp tục làm mà không cần lịch sử chat.
không được bịa mysql phải tự đọc lại và kiểm tra file mysql trước khi đưa ra các câu lệnh sql và code:
https://raw.githubusercontent.com/tranlongdollar/gpthelpmsnow/refs/heads/main/mysql.sql
Ngoài kiến thức chuyên môn lập trình không được kiếm thông tin từ nguồn khác.
chỉ được tìm kiếm thông tin tại repo tôi cung cấp : https://github.com/tranlongdollar/gpthelpmsnow/
có thể tìm kiếm rawfile link tại: https://raw.githubusercontent.com/tranlongdollar/gpthelpmsnow/refs/heads/main/rawlink.md
sau mỗi lần hưỡng dẫn tôi thành công cần tạo markdown dạng code để tôi cập nhật cho file : https://raw.githubusercontent.com/tranlongdollar/gpthelpmsnow/refs/heads/main/Todolist.md và bạn sẽ dùng file này để kiểm tra xem bạn đã giúp tôi cái gì rồi.
lưu ý quan trọng: không được bịa dữ liệu, không làm được nói không làm được, không có thông tin báo không có thông tin tôi sẽ cung cấp thông tin cho bạn để bạn giúp tôi.
---

## 1. Mục tiêu sản phẩm

Massagenow là nền tảng đặt lịch massage tại nhà theo thành phố, đa ngôn ngữ.

Khách truy cập:
- Chọn ngôn ngữ (vi, en, ru, ja, ko, th, zh ...).
- Chọn thành phố.
- Xem nhân viên và dịch vụ ở thành phố đó.
- Gửi form đặt lịch (booking).

Admin nội bộ:
- Quản lý thành phố, dịch vụ, nhân viên, đơn đặt lịch (booking), user admin.
- Dashboard xem nhanh số liệu + biểu đồ.
- Có thể đổi trạng thái đơn hàng (new, confirmed, in_progress, done, canceled).

SEO:
- URL dạng `/vi/ha-noi`, `/ru/nha-trang`.
- Title/Description và nội dung trang city theo ngôn ngữ.
- Sitemap và robots.txt.

---

## 2. Cấu trúc thư mục quan trọng

```text
/public_html/
  index.php            ← router chính (frontend khách)
  /.htaccess           ← rewrite rule cho OpenLiteSpeed/Apache
  /api/booking.php     ← API tạo đơn hàng (booking)
  /admin/
    index.php          ← dashboard (AdminLTE)
    orders.php         ← danh sách booking
    order-view.php     ← chi tiết 1 booking
    cities.php         ← CRUD Thành phố
    ...                ← các file admin khác (services.php, staff.php,...)
##3. Database (MySQL)
Lưu ý: tất cả id AUTO_INCREMENT. Collation utf8mb4_unicode_ci.
-- LANGUAGES
CREATE TABLE IF NOT EXISTS languages (
  code       varchar(10)  NOT NULL PRIMARY KEY,
  name       varchar(100) NOT NULL,
  is_default tinyint(1)   NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO languages(code,name,is_default) VALUES
('vi','Tiếng Việt',1),
('en','English',0),
('ru','Русский',0),
('ja','日本語',0),
('ko','한국어',0),
('th','ไทย',0),
('zh','中文',0);

-- CITIES
CREATE TABLE IF NOT EXISTS cities (
  id     bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  name   varchar(190) NOT NULL,
  slug   varchar(190) NOT NULL UNIQUE,
  status enum('draft','published') NOT NULL DEFAULT 'draft',
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CITY I18N (tên city theo ngôn ngữ)
CREATE TABLE IF NOT EXISTS city_i18n (
  id        bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  city_id   bigint UNSIGNED NOT NULL,
  lang_code varchar(10) NOT NULL,
  name      varchar(190) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY u_city_lang (city_id, lang_code),
  FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE,
  FOREIGN KEY (lang_code) REFERENCES languages(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- STAFF (KTV)
CREATE TABLE IF NOT EXISTS staff (
  id        bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  photo_url varchar(255) DEFAULT NULL,
  order_no  int  NOT NULL DEFAULT 0,
  active    tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_i18n (
  id        bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  staff_id  bigint UNSIGNED NOT NULL,
  lang_code varchar(10) NOT NULL,
  name      varchar(190) DEFAULT '',
  title     varchar(190) DEFAULT '',
  tagline   varchar(255) DEFAULT '',
  PRIMARY KEY (id),
  UNIQUE KEY u_staff_lang (staff_id, lang_code),
  FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
  FOREIGN KEY (lang_code) REFERENCES languages(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_skills (
  id        bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  staff_id  bigint UNSIGNED NOT NULL,
  percent   tinyint UNSIGNED NOT NULL DEFAULT 0,
  order_no  int NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_skill_i18n (
  id        bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  skill_id  bigint UNSIGNED NOT NULL,
  lang_code varchar(10) NOT NULL,
  label     varchar(190) DEFAULT '',
  PRIMARY KEY (id),
  UNIQUE KEY u_skill_lang (skill_id, lang_code),
  FOREIGN KEY (skill_id) REFERENCES staff_skills(id) ON DELETE CASCADE,
  FOREIGN KEY (lang_code) REFERENCES languages(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SERVICES
CREATE TABLE IF NOT EXISTS services (
  id          bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  slug        varchar(190) NOT NULL UNIQUE,
  duration_min int NOT NULL DEFAULT 60,
  order_no    int NOT NULL DEFAULT 0,
  active      tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_i18n (
  id         bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  service_id bigint UNSIGNED NOT NULL,
  lang_code  varchar(10) NOT NULL,
  name       varchar(190) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY u_service_lang (service_id, lang_code),
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  FOREIGN KEY (lang_code) REFERENCES languages(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CITY x STAFF / CITY x SERVICES
CREATE TABLE IF NOT EXISTS city_staff (
  id        bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  city_id   bigint UNSIGNED NOT NULL,
  staff_id  bigint UNSIGNED NOT NULL,
  order_no  int NOT NULL DEFAULT 0,
  active    tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY u_city_staff (city_id, staff_id),
  FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE,
  FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS city_services (
  id         bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  city_id    bigint UNSIGNED NOT NULL,
  service_id bigint UNSIGNED NOT NULL,
  price      int NOT NULL DEFAULT 0,
  order_no   int NOT NULL DEFAULT 0,
  active     tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY u_city_service (city_id, service_id),
  FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BOOKINGS (đơn hàng)
CREATE TABLE IF NOT EXISTS bookings (
  id            bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  city_id       bigint UNSIGNED NOT NULL,
  lang_code     varchar(10) NOT NULL,
  customer_name varchar(190) DEFAULT '',
  email         varchar(190) NOT NULL,
  phone         varchar(50)  DEFAULT '',
  note          text,
  status        enum('new','confirmed','in_progress','done','canceled') NOT NULL DEFAULT 'new',
  created_at    timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE RESTRICT,
  FOREIGN KEY (lang_code) REFERENCES languages(code) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- USERS (admin)
CREATE TABLE IF NOT EXISTS users (
  id            bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  email         varchar(190) NOT NULL UNIQUE,
  name          varchar(190) NOT NULL,
  pass_hash     varchar(255) NOT NULL,
  is_active     tinyint(1) NOT NULL DEFAULT 1,
  created_at    timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
Seed cơ bản: tạo 1 user admin
INSERT INTO users(email,name,pass_hash,is_active)
VALUES ('admin@massagenow.vn','Admin', '{PASSWORD_HASH_HERE}', 1);
-- Tạo hash PHP: password_hash('your-pass', PASSWORD_DEFAULT)
________________________________________
##4. i18n (dịch nội dung)
Bảng key/value
CREATE TABLE IF NOT EXISTS translation_keys (
  id          bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  namespace   varchar(100) NOT NULL,
  tkey        varchar(190) NOT NULL,
  description varchar(255),
  created_at  timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY u_ns_key (namespace, tkey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS translations (
  id         bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  key_id     bigint UNSIGNED NOT NULL,
  lang_code  varchar(10) NOT NULL,
  tvalue     mediumtext NOT NULL,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY u_key_lang (key_id, lang_code),
  FOREIGN KEY (key_id) REFERENCES translation_keys(id) ON DELETE CASCADE,
  FOREIGN KEY (lang_code) REFERENCES languages(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
Key cần dùng (ví dụ cho page.massageteam)
•	meta.title, meta.description
•	nav.brandline, nav.links.team/services/booking/cta
•	hero.heading, hero.paragraph_html, hero.cta
•	team.title, team.subtitle
•	booking.title/subtitle/fields.*
•	booking.button, booking.msg.*
•	footer.copy
•	services.item.format (ví dụ: vi: %service_name% tại %thanh_pho%, ru: %service_name% в %thanh_pho%…)
________________________________________
##5. File /app
/app/config.php
<?php
declare(strict_types=1);

define('DB_DSN', 'mysql:host=127.0.0.1;dbname=massagenow;charset=utf8mb4');
define('DB_USER', 'dbuser');
define('DB_PASS', 'dbpass');

define('BASE_URL', 'https://massagenow.vn');
define('ADMIN_EMAIL', 'admin@massagenow.vn'); // để mail booking
/app/db.php
<?php
declare(strict_types=1);

function pdo(): PDO {
  static $pdo;
  if (!$pdo) {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  }
  return $pdo;
}
function db_select(string $sql, array $params=[]): array {
  $st = pdo()->prepare($sql); $st->execute($params); return $st->fetchAll();
}
function db_row(string $sql, array $params=[]): ?array {
  $st = pdo()->prepare($sql); $st->execute($params); $r=$st->fetch(); return $r?:null;
}
function db_value(string $sql, array $params=[]) {
  $st = pdo()->prepare($sql); $st->execute($params); return $st->fetchColumn();
}
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
/app/auth.php (session)
<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function auth_user(): ?array {
  if (!empty($_SESSION['admin_id'])) {
    return db_row("SELECT id,email,name FROM users WHERE id=? AND is_active=1 LIMIT 1", [$_SESSION['admin_id']]);
  }
  return null;
}
function auth_require_login(): void {
  if (!auth_user()) {
    header('Location: /admin/login.php'); exit;
  }
}
function auth_login(string $email, string $password): bool {
  $u = db_row("SELECT * FROM users WHERE email=? AND is_active=1 LIMIT 1", [$email]);
  if ($u && password_verify($password, $u['pass_hash'])) {
    $_SESSION['admin_id']=$u['id']; return true;
  }
  return false;
}
function auth_logout(): void {
  $_SESSION = []; session_destroy();
  header('Location: /admin/login.php'); exit;
}
/app/i18n.php (loader)
<?php
declare(strict_types=1);

function i18n_load(PDO $pdo, string $ns, string $lang): array {
  $def = db_value("SELECT code FROM languages WHERE is_default=1 LIMIT 1") ?: 'vi';
  $keys = db_select("SELECT id,tkey FROM translation_keys WHERE namespace=?", [$ns]);
  if (!$keys) return [];
  $ids = array_column($keys,'id');
  $ph = implode(',', array_fill(0,count($ids),'?'));

  $cur = db_select("SELECT key_id,tvalue FROM translations WHERE lang_code=? AND key_id IN ($ph)",
    array_merge([$lang],$ids));
  $fb  = db_select("SELECT key_id,tvalue FROM translations WHERE lang_code=? AND key_id IN ($ph)",
    array_merge([$def], $ids));

  $mCur=[]; foreach($cur as $r){ $mCur[(int)$r['key_id']]=$r['tvalue']; }
  $mFb =[]; foreach($fb  as $r){ $mFb [(int)$r['key_id']]=$r['tvalue']; }
  $out=[];
  foreach($keys as $k){
    $id=(int)$k['id']; $tkey=$k['tkey'];
    $out[$tkey] = $mCur[$id] ?? ($mFb[$id] ?? '');
  }
  return $out;
}
function i18n_render(string $tpl, array $vars=[]): string {
  foreach($vars as $k=>$v){ $tpl=str_replace('%'.$k.'%', (string)$v, $tpl); }
  return $tpl;
}
function i18n_get(array $map, string $key, array $vars=[], string $def=''): string {
  $val = $map[$key] ?? $def; return i18n_render($val,$vars);
}
________________________________________
##6. Router khách — /public_html/index.php
•	/ → views/home.php
•	/{lang} → views/select-city.php
•	/{lang}/{slug} → views/city.php
•	Legacy /{slug} → 301 → /{defaultLang}/{slug}
<?php
declare(strict_types=1);
require __DIR__.'/../app/config.php';
require __DIR__.'/../app/db.php';
require __DIR__.'/../app/i18n.php';

$pdo = pdo();

function get_default_lang(): string {
  return db_value("SELECT code FROM languages WHERE is_default=1 LIMIT 1") ?: 'vi';
}
function get_all_langs(): array {
  return array_map(fn($r)=>$r['code'], db_select("SELECT code FROM languages ORDER BY is_default DESC, code"));
}
function is_supported_lang(string $c): bool {
  static $L; if($L===null) $L=get_all_langs(); return in_array($c,$L,true);
}
function fetch_city(string $slug): ?array {
  return db_row("SELECT * FROM cities WHERE slug=? AND status='published' LIMIT 1", [$slug]);
}

$uri = parse_url($_SERVER['REQUEST_URI']??'/', PHP_URL_PATH) ?: '/';
$path = trim($uri,'/');
$seg  = $path===''?[]:explode('/',$path);

if (isset($seg[0]) && $seg[0]==='admin') {
  http_response_code(404); require __DIR__.'/../views/404.php'; exit;
}
if (count($seg)===0) { require __DIR__.'/../views/home.php'; exit; }

$first=$seg[0];
if (is_supported_lang($first)) {
  $lang=$first; $slug=$seg[1]??'';
  if ($slug==='') {
    $cities = db_select("SELECT id,name,slug FROM cities WHERE status='published' ORDER BY name ASC");
    require __DIR__.'/../views/select-city.php'; exit;
  }
  $city = fetch_city($slug);
  if (!$city){ http_response_code(404); require __DIR__.'/../views/404.php'; exit; }

  // Load i18n + data cho city page
  $txt = i18n_load($pdo,'page.massageteam',$lang);
  // Services theo city/lang
  $services = db_select("
    SELECT s.id,s.slug,s.duration_min AS duration
    , COALESCE(si.name, s.slug) AS name
    FROM city_services cs
    JOIN services s ON s.id=cs.service_id AND s.active=1
    LEFT JOIN service_i18n si ON si.service_id=s.id AND si.lang_code=?
    WHERE cs.city_id=? AND cs.active=1
    ORDER BY cs.order_no,s.order_no,s.slug
  ", [$lang, (int)$city['id']]);

  // Staff theo city/lang
  $staff = db_select("
    SELECT st.id, st.photo_url, COALESCE(i18n.name,'') name, COALESCE(i18n.title,'') title, COALESCE(i18n.tagline,'') tagline
    FROM city_staff cs
    JOIN staff st ON st.id=cs.staff_id AND st.active=1
    LEFT JOIN staff_i18n i18n ON i18n.staff_id=st.id AND i18n.lang_code=?
    WHERE cs.city_id=? AND cs.active=1
    ORDER BY cs.order_no, st.order_no, st.id
  ", [$lang,(int)$city['id']]);

  // Tên city theo lang
  $city_local = db_value("SELECT name FROM city_i18n WHERE city_id=? AND lang_code=? LIMIT 1", [(int)$city['id'], $lang]) ?: $city['name'];

  // Canonical/hreflang
  $host=$_SERVER['HTTP_HOST']??'localhost';
  $scheme=!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off' ? 'https' : 'http';
  $base=$scheme.'://'.$host;
  $canonical=$base.'/'.$lang.'/'.$city['slug'];
  $hreflangs=[]; foreach(get_all_langs() as $lc){ $hreflangs[$lc]=$base.'/'.$lc.'/'.$city['slug']; }

  extract(compact('city','lang','txt','services','staff','canonical','hreflangs'));
  $city_name_local = $city_local;
  require __DIR__.'/../views/city.php'; exit;
}

$legacy = $seg[0]??'';
if ($legacy!=='') {
  $def = get_default_lang();
  header('Location: /'.rawurlencode($def).'/'.$legacy, true, 301); exit;
}
http_response_code(404); require __DIR__.'/../views/404.php';
________________________________________
##7. View city — /public_html/views/city.php
Quan trọng: giữ nguyên giao diện hiện có; chỉ thay phần tên city theo ngôn ngữ và format hiển thị dịch vụ.
Trong phần Services thay:
<?php
$format = $txt['services.item.format'] ?? '%service_name% tại %thanh_pho%';
?>
...
<?php foreach ($services as $sv): ?>
  <?php
    $svcTitle = strtr($format, [
      '%service_name%' => $sv['name'],
      '%thanh_pho%'    => $city_name_local, // tên TP theo lang
    ]);
  ?>
  <div class="border border-gray-200 rounded-xl p-4">
    <div class="font-medium">
      <?= e($svcTitle) ?> (<?= (int)$sv['duration'] ?>′)
    </div>
  </div>
<?php endforeach; ?>
Form booking cần ẩn city_id và lang_code:
<form id="bookingForm" method="post" action="/api/booking.php">
  <input type="hidden" name="city_id" value="<?= (int)$city['id'] ?>">
  <input type="hidden" name="lang_code" value="<?= e($lang) ?>">
  ...
</form>
________________________________________
##8. API Booking — /public_html/api/booking.php (gồm gửi mail + CC)
<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';

$DEBUG = isset($_GET['debug']) && $_GET['debug']==='1';
function jexit(array $p, int $code=200){ http_response_code($code); echo json_encode($p, JSON_UNESCAPED_UNICODE); exit; }

try {
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') jexit(['ok'=>false,'error'=>'Method Not Allowed'],405);

  $city_id   = (int)($_POST['city_id'] ?? 0);
  $lang_code = trim((string)($_POST['lang_code'] ?? 'vi'));
  $name      = trim((string)($_POST['name'] ?? ''));
  $email     = trim((string)($_POST['email'] ?? ''));
  $phone     = trim((string)($_POST['phone'] ?? ''));
  $note      = trim((string)($_POST['note'] ?? ''));

  if ($city_id<=0) throw new RuntimeException('Thiếu hoặc sai city_id');
  if ($email==='' || !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email bắt buộc và hợp lệ');

  $pdo=pdo();
  $city=db_row("SELECT id,name FROM cities WHERE id=? AND status='published' LIMIT 1",[$city_id]);
  if(!$city) throw new RuntimeException('Thành phố không tồn tại hoặc chưa publish');

  $st=$pdo->prepare("INSERT INTO bookings(city_id,lang_code,customer_name,email,phone,note,status,created_at)
                     VALUES(?,?,?,?,?,?, 'new', NOW())");
  $st->execute([$city_id,$lang_code,$name,$email,$phone,$note]);
  $orderId=(int)$pdo->lastInsertId();

  // Gửi email
  $admin = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : '';
  if ($admin!=='') {
    $subject = "[Booking] #$orderId — ".$city['name'];
    $body =
      "Đơn đặt lịch mới:\n".
      "- ID: #{$orderId}\n".
      "- Thành phố: {$city['name']} (ID {$city_id})\n".
      "- Ngôn ngữ: {$lang_code}\n".
      "- Khách hàng: {$name}\n".
      "- Email: {$email}\n".
      "- Phone: {$phone}\n".
      "- Ghi chú:\n{$note}\n".
      "- Thời gian: ".date('Y-m-d H:i:s');

    $from = 'noreply@'.($_SERVER['HTTP_HOST'] ?? 'massagenow.vn');
    $headers = "From: {$from}\r\n".
               "Reply-To: {$email}\r\n".
               "Cc: {$email}, tranlong.dollar@gmail.com\r\n".
               "Content-Type: text/plain; charset=UTF-8\r\n";

    @mail($admin, '=?UTF-8?B?'.base64_encode($subject).'?=', $body, $headers);
  }

  jexit(['ok'=>true,'id'=>$orderId],200);

} catch(Throwable $e){
  error_log("[booking.php] ".$e->getMessage());
  jexit(['ok'=>false,'error'=>$DEBUG?$e->getMessage():'Server error'],400);
}
________________________________________
##9. Admin — /public_html/admin/index.php (Dashboard + session)
Thêm xác thực: auth_require_login()
<?php
declare(strict_types=1);
require __DIR__.'/../../app/auth.php';
auth_require_login();
$u = auth_user();

$stats = [
  'cities'   => (int)db_value("SELECT COUNT(*) FROM cities"),
  'services' => (int)db_value("SELECT COUNT(*) FROM services"),
  'staff'    => (int)db_value("SELECT COUNT(*) FROM staff"),
  'users'    => (int)db_value("SELECT COUNT(*) FROM users WHERE is_active=1"),
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard - Massagenow</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item d-none d-sm-inline-block"><a href="/admin/" class="nav-link">Trang chủ</a></li>
      <li class="nav-item d-none d-sm-inline-block"><a href="/admin/logout.php" class="nav-link">Đăng xuất</a></li>
    </ul>
    <span class="ml-3">Xin chào, <?= e($u['name']) ?></span>
  </nav>

  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="/admin/" class="brand-link">
      <span class="brand-text font-weight-light">Massagenow</span>
    </a>
    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column">
          <li class="nav-item"><a href="/admin/index.php" class="nav-link active"><i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p></a></li>
          <li class="nav-item"><a href="/admin/cities.php" class="nav-link"><i class="nav-icon fas fa-city"></i><p>Thành phố</p></a></li>
          <li class="nav-item"><a href="/admin/services.php" class="nav-link"><i class="nav-icon fas fa-cogs"></i><p>Dịch vụ</p></a></li>
          <li class="nav-item"><a href="/admin/orders.php" class="nav-link"><i class="nav-icon fas fa-box"></i><p>Đơn hàng</p></a></li>
          <li class="nav-item"><a href="/admin/staff.php" class="nav-link"><i class="nav-icon fas fa-users"></i><p>Nhân viên</p></a></li>
          <li class="nav-item"><a href="/admin/users.php" class="nav-link"><i class="nav-icon fas fa-user"></i><p>Người dùng</p></a></li>
        </ul>
      </nav>
    </div>
  </aside>

  <div class="content-wrapper">
    <section class="content-header"><div class="container-fluid"><h1>Dashboard</h1></div></section>
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3><?= $stats['cities'] ?></h3><p>Thành phố</p></div><div class="icon"><i class="fas fa-city"></i></div><a href="/admin/cities.php" class="small-box-footer">Quản lý <i class="fas fa-arrow-circle-right"></i></a></div></div>
          <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3><?= $stats['services'] ?></h3><p>Dịch vụ</p></div><div class="icon"><i class="fas fa-cogs"></i></div><a href="/admin/services.php" class="small-box-footer">Quản lý <i class="fas fa-arrow-circle-right"></i></a></div></div>
          <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3><?= $stats['staff'] ?></h3><p>Nhân viên</p></div><div class="icon"><i class="fas fa-users"></i></div><a href="/admin/staff.php" class="small-box-footer">Quản lý <i class="fas fa-arrow-circle-right"></i></a></div></div>
          <div class="col-lg-3 col-6"><div class="small-box bg-danger"><div class="inner"><h3><?= $stats['users'] ?></h3><p>Người dùng</p></div><div class="icon"><i class="fas fa-user"></i></div><a href="/admin/users.php" class="small-box-footer">Quản lý <i class="fas fa-arrow-circle-right"></i></a></div></div>
        </div>

        <?php
        // ví dụ biểu đồ booking theo tháng (6 tháng gần nhất)
        $rows = db_select("
          SELECT DATE_FORMAT(created_at,'%Y-%m') ym, COUNT(*) cnt
          FROM bookings
          WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
          GROUP BY ym ORDER BY ym
        ");
        $labels=[];$data=[];
        foreach($rows as $r){ $labels[]=$r['ym']; $data[]=(int)$r['cnt']; }
        ?>
        <div class="row"><div class="col-12">
          <div class="card">
            <div class="card-header"><h3 class="card-title">Đơn đặt lịch theo tháng</h3></div>
            <div class="card-body"><canvas id="revenueChart" style="height:400px;"></canvas></div>
          </div>
        </div></div>
      </div>
    </section>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
<script>
var ctx=document.getElementById('revenueChart').getContext('2d');
new Chart(ctx,{type:'line',data:{
  labels: <?= json_encode($labels) ?>,
  datasets:[{label:'Bookings',data:<?= json_encode($data) ?>,fill:false,borderColor:'#4e73df',tension:0.1}]
}});
</script>
</body>
</html>
________________________________________
##10. Admin Orders — /public_html/admin/orders.php (session + đổi trạng thái)
<?php
declare(strict_types=1);
require __DIR__.'/../../app/auth.php';
auth_require_login();
$u = auth_user();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['status'], $_POST['id'])) {
  $st = $_POST['status'];
  $id = (int)$_POST['id'];
  if (in_array($st, ['new','confirmed','in_progress','done','canceled'], true)) {
    $q = pdo()->prepare("UPDATE bookings SET status=? WHERE id=?");
    $q->execute([$st,$id]);
  }
  header('Location: /admin/orders.php'); exit;
}

$rows = db_select("
  SELECT b.*, c.name city_name
  FROM bookings b JOIN cities c ON c.id=b.city_id
  ORDER BY b.created_at DESC LIMIT 200
");
?>
<!doctype html>
<html lang="vi"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Quản lý đơn hàng</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
<style>table{font-size:14px} td,th{white-space:nowrap;vertical-align:top}</style>
</head><body class="container">
<h3>Đơn đặt lịch (200 gần nhất)</h3>
<table role="grid"><thead><tr>
<th>#</th><th>Thời gian</th><th>Thành phố</th><th>Lang</th><th>Tên</th><th>Email</th><th>Phone</th><th>Ghi chú</th><th>Trạng thái</th><th>Chi tiết</th>
</tr></thead><tbody>
<?php foreach($rows as $r): ?>
<tr>
  <td><?= (int)$r['id'] ?></td>
  <td><?= e($r['created_at']) ?></td>
  <td><?= e($r['city_name']) ?></td>
  <td><?= e($r['lang_code']) ?></td>
  <td><?= e($r['customer_name']) ?></td>
  <td><?= e($r['email']) ?></td>
  <td><?= e($r['phone']) ?></td>
  <td style="max-width:420px;white-space:normal"><?= nl2br(e($r['note'])) ?></td>
  <td>
    <form method="post" action="/admin/orders.php">
      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
      <select name="status" onchange="this.form.submit()">
        <?php
          $opts=['new'=>'Mới','confirmed'=>'Đã xác nhận','in_progress'=>'Đang xử lý','done'=>'Hoàn thành','canceled'=>'Hủy'];
          foreach($opts as $k=>$v){ $sel=$r['status']===$k?'selected':''; echo "<option value=\"$k\" $sel>$v</option>";}
        ?>
      </select>
    </form>
  </td>
  <td><a href="/admin/order-view.php?id=<?= (int)$r['id'] ?>">Xem</a></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</body></html>
________________________________________
##11. Admin Order View — /public_html/admin/order-view.php
<?php
declare(strict_types=1);
require __DIR__.'/../../app/auth.php';
auth_require_login();
$u = auth_user();

$id = (int)($_GET['id'] ?? 0);
$row = db_row("
  SELECT b.*, c.name city_name
  FROM bookings b JOIN cities c ON c.id=b.city_id
  WHERE b.id=? LIMIT 1
", [$id]);

if (!$row){ http_response_code(404); echo 'Not found'; exit; }

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['status'])) {
  $st=$_POST['status'];
  if (in_array($st,['new','confirmed','in_progress','done','canceled'], true)) {
    $q=pdo()->prepare("UPDATE bookings SET status=? WHERE id=?");
    $q->execute([$st,$id]);
    header('Location: /admin/order-view.php?id='.$id); exit;
  }
}
?>
<!doctype html>
<html lang="vi"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Đơn #<?= (int)$row['id'] ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
</head><body class="container">
<h3>Đơn #<?= (int)$row['id'] ?></h3>
<ul>
  <li>Thành phố: <b><?= e($row['city_name']) ?></b></li>
  <li>Ngôn ngữ: <?= e($row['lang_code']) ?></li>
  <li>Khách: <?= e($row['customer_name']) ?></li>
  <li>Email: <?= e($row['email']) ?></li>
  <li>Phone: <?= e($row['phone']) ?></li>
  <li>Ghi chú:<br><?= nl2br(e($row['note'])) ?></li>
  <li>Trạng thái: <b><?= e($row['status']) ?></b></li>
  <li>Thời gian: <?= e($row['created_at']) ?></li>
</ul>
<form method="post"><label>Đổi trạng thái
<select name="status">
  <?php
    $opts=['new'=>'Mới','confirmed'=>'Đã xác nhận','in_progress'=>'Đang xử lý','done'=>'Hoàn thành','canceled'=>'Hủy'];
    foreach($opts as $k=>$v){ $sel=$row['status']===$k?'selected':''; echo "<option value=\"$k\" $sel>$v</option>";}
  ?>
</select></label>
<button type="submit">Cập nhật</button></form>
<p><a href="/admin/orders.php">← Quay lại danh sách</a></p>
</body></html>
________________________________________
##12. .htaccess (OpenLiteSpeed/Apache)
# OpenLiteSpeed / Apache
RewriteEngine On

# Cho phép API không dính prefix /{lang}/
RewriteRule ^([a-zA-Z]{2})/api(.*)$ /api$2 [L,QSA]

# Chặn truy cập trực tiếp /app
RewriteRule ^app/ - [F,L]

# Cho phép /admin/* vào thẳng file
RewriteRule ^admin/ - [L]

# Nếu là file/thư mục thật → giữ nguyên
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# sitemap xml (nếu dùng PHP render)
RewriteRule ^sitemap\.xml$ sitemap.xml.php [L]

# Mặc định mọi thứ → index.php
RewriteRule ^ index.php [L]
________________________________________
##13. Các lỗi logic đã gặp & cách fix
•	$sv undefined: render service title ngoài vòng foreach. → Đưa logic vào trong foreach ($services as $sv).
•	Gọi extract() hai lần: gây overwrite biến. → Chỉ extract() 1 lần, hoặc truyền biến cụ thể.
•	Đặt <meta> trong <header>: HTML sai chuẩn. → Đảm bảo <meta>, <link>, <title> nằm trong <head>.
•	/vi/api 404: rewrite không bỏ prefix lang. → Thêm rule ^([a-zA-Z]{2})/api(.*)$ /api$2.
•	Email không CC: bổ sung header Cc: + Reply-To: và UTF-8 encode subject.
•	Không giữ city theo lang: thêm lấy city_i18n.name cho $city_name_local.
________________________________________
##14. Checklist triển khai (prod)
•	Tạo users admin và password_hash.
•	Bật HTTPS; test mail server (sendmail/SMTP).
•	Bảo vệ /app/ bằng rewrite.
•	Kiểm tra sitemap.xml, robots.txt.
•	Kiểm tra form booking thực sự lưu DB + gửi mail + CC.
•	Kiểm tra Admin login/session, đổi trạng thái đơn hàng.
________________________________________
##15. Flow tổng quan
1.	User → / → chọn ngôn ngữ → tag cloud city.
2.	/vi/ha-noi → load city, staff, services (theo lang) + form booking (hidden city_id, lang_code).
3.	Submit form → POST /api/booking.php → lưu DB → mail Admin + CC khách + CC tranlong.dollar@gmail.com.
4.	Admin → login session → /admin/ dashboard → /admin/orders.php để theo dõi & đổi trạng thái → /admin/order-view.php xem chi tiết.

