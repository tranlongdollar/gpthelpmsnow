<?php
declare(strict_types=1);
/**
 * Home — chọn ngôn ngữ + giới thiệu + mây thẻ city
 * YÊU CẦU: index.php đã require app/config.php, app/db.php, app/i18n.php
 * Dùng db_select(), tr_load_map()/i18n_load() sẵn có.
 */

// Kiểm tra yêu cầu route /api
if ($_SERVER['REQUEST_URI'] === '/api') {
    // Xử lý API và trả về dữ liệu các thành phố đã xuất bản
    header('Content-Type: application/json');
    $cities = db_select("SELECT name, slug FROM cities WHERE status='published' ORDER BY name ASC");
    echo json_encode($cities);
    exit; // Đảm bảo không tiếp tục xử lý phần còn lại của trang
}

if (!function_exists('e')) {
  function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}

function _t_home(array $txt, string $key, string $fallback=''): string {
  return $txt[$key] ?? $fallback;
}

/* Lấy ngôn ngữ & default */
$langRows = db_select("SELECT code, name, is_default FROM languages ORDER BY is_default DESC, code");
$langList = array_map(fn($r)=>$r['code'], $langRows);
$defaultLang = $langList[0] ?? 'vi';

/* UI language của trang home (dùng ?lang=, mặc định = default) */
$uiLang = $_GET['lang'] ?? $defaultLang;
if (!in_array($uiLang, $langList, true)) $uiLang = $defaultLang;

/* i18n page.home */
$txt = [];
if (function_exists('tr_load_map')) {
  $txt = tr_load_map(pdo(), 'page.home', $uiLang, $defaultLang);
} elseif (function_exists('i18n_load')) {
  $cur = i18n_load(pdo(), 'page.home', $uiLang) ?: [];
  $def = i18n_load(pdo(), 'page.home', $defaultLang) ?: [];
  $txt = array_merge($def, $cur);
}

/* Cities publish */
$cities = db_select("SELECT name, slug FROM cities WHERE status='published' ORDER BY name ASC");

/* Canonical */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$canonical = $scheme.'://'.$host.'/';
?>
<!doctype html>
<html lang="<?= e($uiLang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(_t_home($txt,'meta.title','MassageNow — Đặt massage online nhanh & an toàn')) ?></title>
  <meta name="description" content="<?= e(_t_home($txt,'meta.description','Nền tảng đặt lịch massage online cho nhiều thành phố.')) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <style>
    :root{--bg:#0b1020;--card:rgba(255,255,255,.04);--line:rgba(255,255,255,.1);--muted:#9aa3b2}
    *{box-sizing:border-box}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:#fff}
    .wrap{max-width:1100px;margin:0 auto;padding:24px 16px}
    .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:18px}
    .row{display:flex;flex-wrap:wrap;gap:10px}
    .lang a{display:inline-flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid var(--line);border-radius:999px;text-decoration:none;color:#fff}
    .lang a[aria-current="page"]{background:#fff;color:#111}
    .muted{color:var(--muted)}
    h1{font-size:28px;margin:0 0 10px}
    h2{font-size:22px;margin:0 0 8px}
    p{margin:8px 0 0;line-height:1.6}
    .tagcloud{display:flex;flex-wrap:wrap;gap:8px}
    .tagcloud a{display:inline-block;padding:8px 10px;border-radius:999px;background:#111;border:1px solid var(--line);text-decoration:none;color:#fff}
    .tagcloud a:hover{background:#fff;color:#111}
    .bar{display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px}
    .search{flex:1;min-width:260px}
    .search input{width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#0f1530;color:#fff}
    .foot{margin:22px 0 10px;color:var(--muted);font-size:13px;text-align:center}
  </style>
</head>
<body>
  <div class="wrap">
    <!-- Thanh chọn ngôn ngữ -->
    <div class="bar">
      <div class="lang row">
        <?php foreach ($langRows as $l):
          $href='/?lang='.urlencode($l['code']); ?>
          <a href="<?= e($href) ?>" <?= $uiLang===$l['code']?'aria-current="page"':'' ?>>
            <?= strtoupper(e($l['code'])) ?> — <?= e($l['name']) ?>
          </a>
        <?php endforeach; ?>
      </div>
      <div class="search">
        <input id="q" type="search" placeholder="<?= e(_t_home($txt,'misc.search_placeholder','Tìm thành phố…')) ?>" oninput="filterCities(this.value)">
      </div>
    </div>

    <!-- Giới thiệu -->
    <div class="card" style="margin-bottom:14px">
      <h1><?= _t_home($txt,'hero.heading','Đặt Massage online nhanh & an toàn') ?></h1>
      <p class="muted"><?= _t_home($txt,'hero.paragraph_html','Chọn ngôn ngữ, sau đó chọn <b>thành phố</b> để xem nhân sự và dịch vụ.') ?></p>
    </div>

    <!-- Mây thẻ city -->
    <div class="card">
      <h2 style="margin-bottom:6px"><?= e(_t_home($txt,'cities.title','Chọn thành phố')) ?></h2>
      <p class="muted" style="margin-bottom:10px"><?= e(_t_home($txt,'cities.subtitle','Nhấn vào thành phố để xem nhân sự & dịch vụ.')) ?></p>

      <div id="tags" class="tagcloud">
        <?php foreach ($cities as $c):
          $url = '/'.rawurlencode($uiLang).'/'.rawurlencode($c['slug']);
          $label = 'massage '.mb_strtolower($c['name'],'UTF-8'); ?>
          <a data-name="<?= e(mb_strtolower($c['name'],'UTF-8')) ?>" href="<?= e($url) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="foot">
      © <?= date('Y') ?> MassageNow — <a style="color:#9ee7ff" href="/sitemap.xml">Sitemap</a>
    </div>
  </div>

<script>
function filterCities(q){
  q=(q||'').toLowerCase().trim();
  document.querySelectorAll('#tags a').forEach(a=>{
    a.style.display = (!q || a.dataset.name.includes(q)) ? '' : 'none';
  });
}
</script>
</body>
</html>
