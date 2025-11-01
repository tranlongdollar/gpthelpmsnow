<?php
declare(strict_types=1);
/**
 * views/city.php — URL dạng /{lang}/{slug}
 * Biến nhận từ index.php: $city, $lang, $txt, $staff, $services
 * Yêu cầu: /app/config.php, /app/db.php, /app/i18n.php đã được require
 */

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/i18n.php';

// ------------ Chuẩn bị dữ liệu chung (KHÔNG in HTML tại đây) ------------
$slug = $city['slug'] ?? '';
$year = date('Y');

// Tên thành phố theo ngôn ngữ (fallback về tên gốc nếu chưa có bản dịch)
$city_name_local = db_value(
  "SELECT name FROM city_i18n WHERE city_id=? AND lang_code=? LIMIT 1",
  [$city['id'], $lang]
) ?: ($city['name'] ?? '');
$cityName = $city_name_local; // dùng đồng nhất bên dưới

// SEO title & description
$title = i18n_get($txt, 'meta.title', ['thanh_pho' => $cityName, 'year' => $year], 'Massagenow');
$desc  = i18n_get($txt, 'meta.description', ['thanh_pho' => $cityName, 'year' => $year], '');

// Canonical & hreflang
$base = rtrim(BASE_URL ?? ('http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off' ? 's' : '') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), '/');
$canonical = $base . '/' . $lang . '/' . $slug;

if (function_exists('i18n_hreflang_links_prefix')) {
  $hreflangs = i18n_hreflang_links_prefix(pdo(), $base, '/' . $slug);
} else {
  $rows = db_select("SELECT code FROM languages ORDER BY is_default DESC, code");
  $hreflangs = [];
  foreach ($rows as $r) {
    $code = $r['code'];
    $hreflangs[] = ['code' => $code, 'url' => $base . '/' . $code . '/' . $slug, 'rel' => 'alternate'];
  }
}

// Nav/hero/team/booking/footer i18n text
$navTeam     = i18n_get($txt, 'nav.links.team', [], 'Nhân viên');
$navServices = i18n_get($txt, 'nav.links.services', [], 'Dịch vụ');
$navBooking  = i18n_get($txt, 'nav.links.booking', [], 'Booking');
$navCTA      = i18n_get($txt, 'nav.links.cta', [], 'Đặt lịch');
$brandline   = i18n_get($txt, 'nav.brandline', ['thanh_pho' => $cityName], '');

$heroHeading = i18n_get($txt, 'hero.heading', ['thanh_pho' => $cityName], $cityName);
$heroPara    = i18n_get($txt, 'hero.paragraph_html', ['thanh_pho' => $cityName], '');
$heroCTA     = i18n_get($txt, 'hero.cta', [], 'Đặt lịch ngay');

$teamTitle = i18n_get($txt, 'team.title', [], 'Nhân viên massage gần đây:');
$teamSub   = i18n_get($txt, 'team.subtitle', [], '');

$bkTitle   = i18n_get($txt, 'booking.title', [], 'Form Booking');
$bkSub     = i18n_get($txt, 'booking.subtitle', [], 'Điền thông tin, chúng tôi sẽ liên hệ xác nhận.');
$fName     = i18n_get($txt, 'booking.fields.name', [], 'Họ và tên');
$fEmail    = i18n_get($txt, 'booking.fields.email', [], 'Email');
$fPhone    = i18n_get($txt, 'booking.fields.phone', [], 'Số điện thoại');
$fNote     = i18n_get($txt, 'booking.fields.note', [], 'Vui Lòng để lại địa chỉ, thông tin địa điểm phục vụ và thời gian phục vụ:');
$btnSend   = i18n_get($txt, 'booking.button', [], 'Gửi đặt lịch');
$msgSending= i18n_get($txt, 'booking.msg.sending', [], 'Đang gửi…');
$msgOK     = i18n_get($txt, 'booking.msg.success', [], 'Cảm ơn! Chúng tôi sẽ liên hệ bạn sớm.');
$msgFail   = i18n_get($txt, 'booking.msg.fail', [], 'Gửi thất bại, vui lòng thử lại.');
$msgNet    = i18n_get($txt, 'booking.msg.network', [], 'Lỗi mạng, thử lại sau.');

$footerCopy = i18n_get($txt, 'footer.copy', ['thanh_pho' => $cityName, 'year' => $year], '© '.$year.' Massagenow');
// Lấy danh sách ngôn ngữ
$_langs = db_select("SELECT code,name,is_default FROM languages ORDER BY is_default DESC, code");
if (!$_langs) $_langs = [
  ['code'=>'vi','name'=>'Tiếng Việt','is_default'=>1],
  ['code'=>'en','name'=>'English','is_default'=>0],
  ['code'=>'ja','name'=>'日本語','is_default'=>0],
  ['code'=>'ko','name'=>'한국어','is_default'=>0],
  ['code'=>'ru','name'=>'Русский','is_default'=>0],
  ['code'=>'th','name'=>'ไทย','is_default'=>0],
  ['code'=>'zh','name'=>'中文','is_default'=>0],
];
$flag = ['vi'=>'🇻🇳','en'=>'🇺🇸','ja'=>'🇯🇵','ko'=>'🇰🇷','ru'=>'🇷🇺','th'=>'🇹🇭','zh'=>'🇨🇳'];
$curLangCode = isset($lang) ? $lang : ($_GET['lang'] ?? ($_langs[0]['code'] ?? 'vi'));
$curFlag = $flag[$curLangCode] ?? '🌐';
?>
<!doctype html>
<html lang="<?= e($lang) ?>">

<header class="sticky top-0 z-30 bg-white/80 backdrop-blur border-b border-gray-100">
  <!-- Meta thông tin SEO -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <?php if ($desc): ?><meta name="description" content="<?= e($desc) ?>"><?php endif; ?>
  <link rel="canonical" href="<?= e($canonical) ?>">
  <?php foreach ($hreflangs as $h): ?>
    <link rel="alternate" hreflang="<?= e($h['code']) ?>" href="<?= e($h['url']) ?>">
  <?php endforeach; ?>
    <!-- Floating vertical language picker -->
  <link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <!-- Open Graph cơ bản -->
  <meta property="og:title" content="<?= e($title) ?>">
  <?php if ($desc): ?><meta property="og:description" content="<?= e($desc) ?>"><?php endif; ?>
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= e($canonical) ?>">

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
  /* Floating Language Button */
  .langfab{position:fixed;right:16px;bottom:16px;z-index:9999}
  .langfab-btn{
    width:56px;height:56px;border-radius:50%;border:none;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    background:#ffffff;color:#111;box-shadow:0 12px 28px rgba(0,0,0,.18);
    border:1px solid rgba(17,24,39,.08);font-size:26px;line-height:1
  }
  .langfab-list{position:absolute;right:6px;bottom:66px;display:flex;flex-direction:column;gap:10px}
  .langfab-item{
    width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;
    background:#ffffff;border:1px solid rgba(17,24,39,.12);box-shadow:0 8px 20px rgba(0,0,0,.14);
    font-size:22px;color:#111;text-decoration:none;transform:translateY(6px) scale(.98);opacity:0;
    transition:.18s ease
  }
  .langfab.open .langfab-item{transform:translateY(0) scale(1);opacity:1}
  .langfab-item[data-active="1"]{outline:3px solid rgba(17,24,39,.15)}
  .langfab-backdrop{position:fixed;inset:0;background:transparent;display:none;z-index:9998}
  .langfab.open + .langfab-backdrop{display:block}
  @media (hover:hover){
    .langfab-item:hover{transform:translateY(0) scale(1.05)}
  }
</style>
</header>
<body class="bg-white text-gray-900">
  <!-- NAV -->
  <header class="sticky top-0 z-30 bg-white/80 backdrop-blur border-b border-gray-100">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
      <a href="/" class="font-semibold tracking-tight text-lg">Massagenow</a>
	      <!-- Menu ngôn ngữ (hiển thị icon flag cho các ngôn ngữ) -->
    <nav class="hidden md:flex items-center gap-6 text-sm">
      <a href="javascript:void(0)" class="lang-switcher" data-lang="en">
        <img src="https://massagenow.vn/upload/en.png" alt="English" class="inline-block w-6 h-6">
      </a>
      <a href="javascript:void(0)" class="lang-switcher" data-lang="ru">
        <img src="https://massagenow.vn/upload/ru.png" alt="Russian" class="inline-block w-6 h-6">
      </a>
      <a href="javascript:void(0)" class="lang-switcher" data-lang="ja">
        <img src="https://massagenow.vn/upload/ja.png" alt="Japanese" class="inline-block w-6 h-6">
      </a>
      <a href="javascript:void(0)" class="lang-switcher" data-lang="zh">
        <img src="https://massagenow.vn/upload/zh.png" alt="Chinese" class="inline-block w-6 h-6">
      </a>
      <a href="javascript:void(0)" class="lang-switcher" data-lang="th">
        <img src="https://massagenow.vn/upload/th.png" alt="Thai" class="inline-block w-6 h-6">
      </a>
      <a href="javascript:void(0)" class="lang-switcher" data-lang="ko">
        <img src="https://massagenow.vn/upload/ko.png" alt="Korean" class="inline-block w-6 h-6">
      </a>
    </nav>
      <nav class="hidden md:flex items-center gap-6 text-sm">
	    <a href="https://massagenow.vn/<?= e($lang) ?>/" class="hover:underline">Back to List City</a>
        <a href="#team" class="hover:underline"><?= e($navTeam) ?></a>
        <a href="#services" class="hover:underline"><?= e($navServices) ?></a>
        <a href="#booking" class="hover:underline"><?= e($navBooking) ?></a>
      </nav>
      <a href="#booking" class="inline-flex items-center px-4 py-2 rounded-xl bg-black text-white text-sm"><?= e($navCTA) ?></a>
    </div>
    <?php if ($brandline): ?>
    <div class="border-t border-gray-100">
      <div class="max-w-6xl mx-auto px-4 py-2 text-xs text-gray-600">
        <?= e($brandline) ?>
      </div>
    </div>
    <?php endif; ?>
  </header>

  <!-- HERO -->
  <section class="relative">
    <div class="max-w-6xl mx-auto px-4 py-12 grid md:grid-cols-2 gap-8 items-center">
      <div>
        <h1 class="text-3xl md:text-5xl font-bold tracking-tight"><?= e($heroHeading) ?></h1>
        <div class="mt-4 text-gray-700 leading-relaxed">
	 <!-- Menu ngôn ngữ (hiển thị icon flag cho các ngôn ngữ) -->
      <nav class="hidden md:flex items-center gap-6 text-sm">
      <a href="javascript:void(0)" class="lang-switcher" data-lang="en">
        <img src="https://massagenow.vn/upload/en.png" alt="English" class="inline-block w-6 h-6">
      </a>
      <a href="javascript:void(0)" class="lang-switcher" data-lang="ru">
        <img src="https://massagenow.vn/upload/ru.png" alt="Russian" class="inline-block w-6 h-6">
      </a>
      <a href="javascript:void(0)" class="lang-switcher" data-lang="ja">
        <img src="https://massagenow.vn/upload/ja.png" alt="Japanese" class="inline-block w-6 h-6">
      </a>
      <a href="javascript:void(0)" class="lang-switcher" data-lang="zh">
        <img src="https://massagenow.vn/upload/zh.png" alt="Chinese" class="inline-block w-6 h-6">
      </a>
      <a href="javascript:void(0)" class="lang-switcher" data-lang="th">
        <img src="https://massagenow.vn/upload/th.png" alt="Thai" class="inline-block w-6 h-6">
      </a>
      <a href="javascript:void(0)" class="lang-switcher" data-lang="ko">
        <img src="https://massagenow.vn/upload/ko.png" alt="Korean" class="inline-block w-6 h-6">
      </a>
    </nav>
		<br><br><?= $heroPara /* giữ HTML */ ?><br>

		</div>
        <div class="mt-6">
          <a href="#booking" class="inline-flex items-center px-5 py-3 rounded-xl bg-black text-white"><?= e($heroCTA) ?></a>
        </div>
      </div>
      <div class="relative">
        <?php if (!empty($staff) && !empty($staff[0]['photo_url'])): ?>
          <img src="<?= e($staff[0]['photo_url']) ?>" alt="<?= e($staff[0]['name'] ?: $cityName) ?>" class="w-full h-auto rounded-2xl shadow">
        <?php else: ?>
          <div class="aspect-[4/3] rounded-2xl bg-gray-100"></div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- TEAM -->
  <section id="team" class="max-w-6xl mx-auto px-4 py-12">
    <h2 class="text-2xl md:text-3xl font-semibold tracking-tight"><?= e($teamTitle) ?></h2>
    <?php if ($teamSub): ?><p class="mt-2 text-gray-600"><?= e($teamSub) ?></p><?php endif; ?>

    <?php if (empty($staff)): ?>
      <p class="mt-6 text-gray-500 text-sm">Chưa có nhân sự để hiển thị.</p>
    <?php else: ?>
      <div class="mt-8 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($staff as $s): ?>
          <article class="border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
            <?php if (!empty($s['photo_url'])): ?>
              <img src="<?= e($s['photo_url']) ?>" alt="<?= e($s['name'] ?: $cityName) ?>" class="w-full h-56 object-cover">
            <?php endif; ?>
            <div class="p-5">
              <h3 class="text-lg font-semibold"><?= e($s['name'] ?: '') ?></h3>
              <p class="text-sm text-gray-600"><?= e($s['title'] ?: '') ?></p>
              <?php if (!empty($s['tagline'])): ?>
                <p class="mt-2 text-sm text-gray-700"><?= e($s['tagline']) ?></p>
              <?php endif; ?>
              <?php if (!empty($s['skills'])): ?>
                <ul class="mt-4 space-y-3">
                  <?php foreach ($s['skills'] as $sk):
                    $p = max(0, min(100, (int)$sk['percent'])); ?>
                    <li>
                      <div class="flex items-center justify-between text-sm">
                        <span><?= e($sk['label']) ?></span>
                        <span><?= e((string)$p) ?>%</span>
                      </div>
                      <div class="skill-bar mt-1"><div class="skill-fill" style="width: <?= $p ?>%"></div></div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- BOOKING -->
<section id="booking" class="bg-gray-50">
  <div class="max-w-6xl mx-auto px-4 py-12">
    <h2 class="text-2xl md:text-3xl font-semibold tracking-tight"><?= e($bkTitle) ?></h2>
    <?php if ($bkSub): ?><p class="mt-2 text-gray-600"><?= e($bkSub) ?></p><?php endif; ?>

    <form id="bookingForm" class="mt-6 grid md:grid-cols-2 gap-4" method="post" action="/api/booking.php">
      <input type="hidden" name="city_id" value="<?= e((string)$city['id']) ?>">
      <input type="hidden" name="lang_code" value="<?= e($lang) ?>">

      <label class="block">
        <span class="block text-sm text-gray-700 mb-1"><?= e($fName) ?></span>
        <input class="w-full border border-gray-300 rounded-xl px-3 py-2" type="text" name="name" required>
      </label>

      <label class="block">
        <span class="block text-sm text-gray-700 mb-1"><?= e($fEmail) ?></span>
        <input class="w-full border border-gray-300 rounded-xl px-3 py-2" type="email" name="email" required>
      </label>

      <label class="block">
        <span class="block text-sm text-gray-700 mb-1"><?= e($fPhone) ?></span>
        <input class="w-full border border-gray-300 rounded-xl px-3 py-2" type="text" name="phone">
      </label>

      <label class="block md:col-span-2">
        <span class="block text-sm text-gray-700 mb-1"><?= e($fNote) ?></span>
        <textarea class="w-full border border-gray-300 rounded-xl px-3 py-2" name="note" rows="4"></textarea>
      </label>

      <div class="md:col-span-2 flex items-center gap-3">
        <button class="inline-flex items-center px-5 py-3 rounded-xl bg-black text-white" type="submit"><?= e($btnSend) ?></button>
        <span id="bookingMsg" class="text-sm text-gray-600"></span>
      </div>
    </form>

    <script>
      (function(){
        var form = document.getElementById('bookingForm');
        var msg  = document.getElementById('bookingMsg');
        if (!form || !window.fetch) return;

        form.addEventListener('submit', function(ev){
          ev.preventDefault();
          msg.textContent = <?= json_encode($msgSending, JSON_UNESCAPED_UNICODE) ?>;
          fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json' }
          }).then(function(r){
            if (!r.ok) throw new Error('HTTP '+r.status);
            return r.json();
          }).then(function(data){
            msg.textContent = (data && data.ok)
              ? <?= json_encode($msgOK, JSON_UNESCAPED_UNICODE) ?>
              : (data && data.error ? data.error : <?= json_encode($msgFail, JSON_UNESCAPED_UNICODE) ?>);
          }).catch(function(){
            msg.textContent = <?= json_encode($msgNet, JSON_UNESCAPED_UNICODE) ?>;
          });
        });
      })();
    </script>
  </div>
</section>


  <!-- SERVICES -->
  <section id="services" class="max-w-6xl mx-auto px-4 py-12">
    <h2 class="text-2xl md:text-3xl font-semibold tracking-tight"><?= e($navServices) ?></h2>

    <?php if (empty($services)): ?>
      <p class="mt-4 text-gray-500 text-sm">Chưa có dịch vụ để hiển thị.</p>
    <?php else: ?>
      <div class="mt-6 grid md:grid-cols-2 gap-4">
        <?php foreach ($services as $sv): ?>
          <?php
            // CHỈ SỬA Ở ĐÂY: format theo ngôn ngữ, không hard-code "tại"
            $format = $txt['services.item.format'] ?? '%service_name% tại %thanh_pho%';
            $svcTitle = strtr($format, [
              '%service_name%' => $sv['name'],
              '%thanh_pho%'    => $cityName,
            ]);
          ?>
          <div class="border border-gray-200 rounded-xl p-4">
            <div class="font-medium">
              <?= e($svcTitle) ?> (<?= e((string)$sv['duration']) ?> phút)
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
<div class="langfab" id="langFab"
     data-langs='<?= e(json_encode(array_map(fn($r)=>$r["code"], $_langs))) ?>'>
  <!-- danh sách lá cờ dọc -->
  <div class="langfab-list" aria-label="Choose language">
    <?php foreach ($_langs as $L): $code=$L['code']; ?>
      <a href="javascript:void(0)"
         class="langfab-item"
         data-lang="<?= e($code) ?>"
         data-active="<?= $code===$curLangCode ? '1':'0' ?>"
         title="<?= e($L['name']) ?>">
        <?= e($flag[$code] ?? '🌐') ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- nút chính: luôn hiện lá cờ đang dùng -->
  <button type="button" class="langfab-btn" id="langFabBtn" aria-label="Change language" aria-expanded="false">
    <span><?= e($curFlag) ?></span>
  </button>
</div>
<div class="langfab-backdrop" id="langFabBackdrop"></div>

  <!-- Nút tròn mở sheet -->
  <button type="button" class="lang-fab-btn" id="langFabBtn" aria-haspopup="true" aria-expanded="false" title="Change language">
    <i class="fa fa-globe" aria-hidden="true"></i>
  </button>
</div>
<script>
(function(){
  var fab = document.getElementById('langFab');
  var btn = document.getElementById('langFabBtn');
  var backdrop = document.getElementById('langFabBackdrop');
  function openFab(){ fab.classList.add('open'); btn.setAttribute('aria-expanded','true'); }
  function closeFab(){ fab.classList.remove('open'); btn.setAttribute('aria-expanded','false'); }

  btn.addEventListener('click', function(e){ e.stopPropagation(); 
    if (fab.classList.contains('open')) closeFab(); else openFab();
  });
  backdrop.addEventListener('click', closeFab);
  document.addEventListener('keydown', function(e){ if (e.key==='Escape') closeFab(); });

  // Đổi ngôn ngữ: giữ nguyên slug city (nếu có)
  var supported = [];
  try { supported = JSON.parse(fab.dataset.langs || '[]'); } catch(_){}
  function switchLang(toLang){
    var seg = (location.pathname||'/').replace(/\/+$/,'').split('/').filter(Boolean); // ['vi','ha-noi']...
    var slug = '';
    if (seg.length>=2 && supported.indexOf(seg[0])!==-1){ slug = seg.slice(1).join('/'); }
    var newUrl = '/' + encodeURIComponent(toLang) + (slug ? '/' + slug : '/');
    if (location.hash) newUrl += location.hash;
    location.href = newUrl;
  }
  document.querySelectorAll('.langfab-item[data-lang]').forEach(function(a){
    a.addEventListener('click', function(){ switchLang(this.getAttribute('data-lang')); });
  });
})();
</script>
  <!-- FOOTER -->
  <footer class="border-t border-gray-100">
    <div class="max-w-6xl mx-auto px-4 py-8 text-sm text-gray-600">
      <?= e($footerCopy) ?>
    </div>
  </footer>
</body>
</html>
