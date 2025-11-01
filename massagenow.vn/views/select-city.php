<?php
declare(strict_types=1);
/** views/select-city.php — Trang chọn thành phố (theo prefix ngôn ngữ) */
header('Content-Type: text/html; charset=utf-8');
$lang = $lang ?? ($_GET['lang'] ?? 'vi');
$cities = db_select("
  SELECT c.id, COALESCE(ci.name, c.name) AS name, c.slug
  FROM cities c
  LEFT JOIN city_i18n ci ON ci.city_id=c.id AND ci.lang_code=?
  WHERE c.status='published'
  ORDER BY name ASC
", [$lang]);
?><!doctype html>
<html lang="<?= e($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Chọn thành phố - Massagenow</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 40px auto; max-width: 820px; padding: 0 16px; }
    h1 { font-size: 24px; margin-bottom: 12px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; margin-top: 16px; }
    .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px 16px; }
    a { color: #111; text-decoration: none; }
    a:hover { text-decoration: underline; }
    .lang { margin-top: 16px; font-size: 14px; color: #555; }
  </style>
</head>
<body>
  <h1>Chọn thành phố</h1>
  <?php if (empty($cities)): ?>
    <p>Chưa có thành phố nào được publish. Hãy vào trang quản trị để tạo.</p>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($cities as $c): ?>
        <div class="card">
          <a href="<?= e('/' . $lang . '/' . $c['slug']) ?>">
            <?= e($c['name']) ?>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="lang">Ngôn ngữ hiện tại: <strong><?= e($lang) ?></strong></div>
</body>
</html>
