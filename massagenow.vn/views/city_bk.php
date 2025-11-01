<?php
declare(strict_types=1);
/**
 * views/city.php — View hiển thị trang thành phố (PHP thuần + i18n từ DB)
 * Biến nhận từ index.php: $city, $lang, $txt, $staff, $services
 * Yêu cầu sẵn: /app/config.php, /app/db.php, /app/i18n.php
 */

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/i18n.php';

$cityName = $city['name'] ?? '';
$slug = $city['slug'] ?? '';
$year = date('Y');

// Title & description (đổi biến %thanh_pho%, %year%)
$title = i18n_get($txt, 'meta.title', ['thanh_pho' => $cityName, 'year' => $year], 'Massagenow');
$desc  = i18n_get($txt, 'meta.description', ['thanh_pho' => $cityName, 'year' => $year], '');

// Canonical & hreflang
$canonical = rtrim(BASE_URL, '/') . '/' . $lang . '/' . $slug;
$hreflangs = i18n_hreflang_links_prefix(pdo(), BASE_URL, '/' . $slug);


// Thẻ nav
$navTeam     = i18n_get($txt, 'nav.links.team', [], 'Nhân viên');
$navServices = i18n_get($txt, 'nav.links.services', [], 'Dịch vụ');
$navBooking  = i18n_get($txt, 'nav.links.booking', [], 'Booking');
$navCTA      = i18n_get($txt, 'nav.links.cta', [], 'Đặt lịch');
$brandline   = i18n_get($txt, 'nav.brandline', ['thanh_pho' => $cityName], '');

// Hero
$heroHeading = i18n_get($txt, 'hero.heading', ['thanh_pho' => $cityName], $cityName);
$heroPara    = i18n_get($txt, 'hero.paragraph_html', ['thanh_pho' => $cityName], '');
$heroCTA     = i18n_get($txt, 'hero.cta', [], 'Đặt lịch ngay');

// Team
$teamTitle   = i18n_get($txt, 'team.title', [], 'Nhân viên massage gần đây:');
$teamSub     = i18n_get($txt, 'team.subtitle', [], '');

// Booking
$bkTitle   = i18n_get($txt, 'booking.title', [], 'Form Booking');
$bkSub     = i18n_get($txt, 'booking.subtitle', [], 'Điền thông tin, chúng tôi sẽ liên hệ xác nhận.');
$fName     = i18n_get($txt, 'booking.fields.name', [], 'Họ và tên');
$fEmail    = i18n_get($txt, 'booking.fields.email', [], 'Email');
$fPhone    = i18n_get($txt, 'booking.fields.phone', [], 'Số điện thoại');
$fNote     = i18n_get($txt, 'booking.fields.note', [], 'Ghi chú');
$btnSend   = i18n_get($txt, 'booking.button', [], 'Gửi đặt lịch');
$msgSending= i18n_get($txt, 'booking.msg.sending', [], 'Đang gửi…');
$msgOK     = i18n_get($txt, 'booking.msg.success', [], 'Cảm ơn! Chúng tôi sẽ liên hệ bạn sớm.');
$msgFail   = i18n_get($txt, 'booking.msg.fail', [], 'Gửi thất bại, vui lòng thử lại.');
$msgNet    = i18n_get($txt, 'booking.msg.network', [], 'Lỗi mạng, thử lại sau.');

// Footer
$footerCopy = i18n_get($txt, 'footer.copy', ['thanh_pho' => $cityName, 'year' => $year], '© '.$year.' Massagenow');
?>
<!doctype html>
<html lang="<?= e($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <?php if ($desc): ?><meta name="description" content="<?= e($desc) ?>"><?php endif; ?>
  <link rel="canonical" href="<?= e($canonical) ?>">
  <?php foreach ($hreflangs as $h): ?>
    <link rel="alternate" hreflang="<?= e($h['code']) ?>" href="<?= e($h['url']) ?>">
  <?php endforeach; ?>

  <!-- Open Graph / Twitter (cơ bản) -->
  <meta property="og:title" content="<?= e($title) ?>">
  <?php if ($desc): ?><meta property="og:description" content="<?= e($desc) ?>"><?php endif; ?>
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <!-- Bạn có thể chọn 1 ảnh nhân sự làm og:image sau -->

  <!-- Tailwind CDN (đơn giản) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .skill-bar { height: 8px; border-radius: 9999px; background: #e5e7eb; overflow: hidden; }
    .skill-fill { height: 100%; background: #111; }
  </style>
</head>
<body class="bg-white text-gray-900">
  <!-- NAV -->
  <header class="sticky top-0 z-30 bg-white/80 backdrop-blur border-b border-gray-100">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
      <a href="/" class="font-semibold tracking-tight text-lg">Massagenow</a>
      <nav class="hidden md:flex items-center gap-6 text-sm">
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
        <div class="mt-4 text-gray-700 leading-relaxed"><?= $heroPara // giữ HTML ?></div>
        <div class="mt-6">
          <a href="#booking" class="inline-flex items-center px-5 py-3 rounded-xl bg-black text-white"><?= e($heroCTA) ?></a>
        </div>
      </div>
      <div class="relative">
        <!-- Ảnh hero: lấy ảnh nhân sự đầu tiên nếu có -->
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
        <label class="block">
          <span class="block text-sm text-gray-700 mb-1"><?= e($fName) ?></span>
          <input class="w-full border border-gray-300 rounded-xl px-3 py-2" type="text" name="name" required>
        </label>
        <label class="block">
          <span class="block text-sm text-gray-700 mb-1"><?= e($fEmail) ?></span>
          <input class="w-full border border-gray-300 rounded-xl px-3 py-2" type="email" name="email">
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
        // JS Submit (Bước 19 sẽ tạo endpoint /api/booking.php)
        (function(){
          var form = document.getElementById('bookingForm');
          var msg  = document.getElementById('bookingMsg');
          if (!form || !window.fetch) return;
          form.addEventListener('submit', function(ev){
            ev.preventDefault();
            msg.textContent = <?= json_encode($msgSending, JSON_UNESCAPED_UNICODE) ?>;
            fetch(form.action, {
              method: 'POST',
              headers: { 'Accept': 'application/json' },
              body: new FormData(form)
            }).then(function(r){
              if (!r.ok) throw new Error('HTTP '+r.status);
              return r.json();
            }).then(function(data){
              msg.textContent = data && data.ok ? <?= json_encode($msgOK, JSON_UNESCAPED_UNICODE) ?> : <?= json_encode($msgFail, JSON_UNESCAPED_UNICODE) ?>;
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
          <div class="border border-gray-200 rounded-xl p-4">
            <div class="font-medium"><?= e($sv['name']) ?> <?= e('tại ' . $cityName) ?> (<?= e((string)$sv['duration']) ?> phút)</div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- FOOTER -->
  <footer class="border-t border-gray-100">
    <div class="max-w-6xl mx-auto px-4 py-8 text-sm text-gray-600">
      <?= e($footerCopy) ?>
    </div>
  </footer>
</body>
</html>
