<?php
declare(strict_types=1);
require __DIR__.'/../app/config.php';
require __DIR__.'/../app/db.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base   = $scheme.'://'.$host;

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$langs  = db_select("SELECT code FROM languages ORDER BY is_default DESC, code");
$cities = db_select("SELECT slug, IFNULL(updated_at, created_at) ts FROM cities WHERE status='published' ORDER BY id DESC");

$now = gmdate('Y-m-d\TH:i:s\Z');
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($langs as $l): ?>
  <url>
    <loc><?= htmlspecialchars($base.'/'.rawurlencode($l['code']).'/', ENT_XML1) ?></loc>
    <lastmod><?= $now ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.5</priority>
  </url>
<?php endforeach; ?>
<?php foreach ($cities as $c):
  $last = $c['ts'] ? gmdate('Y-m-d\TH:i:s\Z', strtotime($c['ts'])) : $now;
  foreach ($langs as $l):
    $loc = $base.'/'.rawurlencode($l['code']).'/'.rawurlencode($c['slug']);
?>
  <url>
    <loc><?= htmlspecialchars($loc, ENT_XML1) ?></loc>
    <lastmod><?= $last ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
<?php endforeach; endforeach; ?>
</urlset>
