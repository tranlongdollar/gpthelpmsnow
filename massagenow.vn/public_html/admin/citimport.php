<?php
declare(strict_types=1);
require __DIR__.'/../../app/config.php';
require __DIR__.'/../../app/db.php';
// require __DIR__.'/../../app/auth.php'; auth_require_login(); // bật nếu có module login

header('Content-Type: text/plain; charset=utf-8');

$csv = __DIR__ . '/city_names.csv';
if (!is_file($csv)) { echo "Không thấy file: $csv\n"; exit; }

$pdo = pdo();
$pdo->beginTransaction();

$fh = fopen($csv, 'r');
$header = fgetcsv($fh);
$expect = ['slug','vi','en','ja','ko','th','zh','ru'];
if (!$header || array_map('strtolower',$header) !== $expect) {
  echo "Header CSV phải là: ".implode(',', $expect)."\n"; exit;
}

$selCity = $pdo->prepare("SELECT id FROM cities WHERE slug=? LIMIT 1");
$selI18n = $pdo->prepare("SELECT id FROM city_i18n WHERE city_id=? AND lang_code=? LIMIT 1");
$insI18n = $pdo->prepare("INSERT INTO city_i18n (city_id, lang_code, name) VALUES (?,?,?)");
$updI18n = $pdo->prepare("UPDATE city_i18n SET name=? WHERE id=?");

$rows = 0; $ok=0; $miss=0;
while (($r = fgetcsv($fh)) !== false) {
  $rows++;
  [$slug,$vi,$en,$ja,$ko,$th,$zh,$ru] = $r;
  $slug = trim($slug);
  if ($slug==='') continue;

  $selCity->execute([$slug]);
  $cityId = (int)($selCity->fetchColumn() ?: 0);
  if (!$cityId) { echo "MISSING city slug: $slug\n"; $miss++; continue; }

  $data = [
    'vi' => trim($vi),
    'en' => trim($en),
    'ja' => trim($ja),
    'ko' => trim($ko),
    'th' => trim($th),
    'zh' => trim($zh),
    'ru' => trim($ru),
  ];

  foreach ($data as $lang => $name) {
    if ($name==='') continue; // bỏ qua rỗng
    $selI18n->execute([$cityId, $lang]);
    $id = (int)($selI18n->fetchColumn() ?: 0);
    if ($id) {
      $updI18n->execute([$name, $id]);
    } else {
      $insI18n->execute([$cityId, $lang, $name]);
    }
  }
  $ok++;
}
fclose($fh);
$pdo->commit();

echo "Processed rows: $rows\n";
echo "Updated cities: $ok\n";
echo "Missing slug: $miss\n";
echo "Done.\n";
