<?php
/**
 * gen_rawlinks.php
 * Sinh bảng RAW URL (Markdown) cho toàn repo hoặc dưới 1 thư mục base.
 * Cách chạy:
 *   php scripts/gen_rawlinks.php owner=<owner> repo=<repo> ref=<branch|sha> base=<subdir>
 * Ví dụ:
 *   php scripts/gen_rawlinks.php owner=tranlongdollar repo=gpthelpmsnow ref=main base=massagenow.vn > rawlink.md
 */
parse_str(implode('&', array_slice($argv, 1)), $a);
$owner = $a['owner'] ?? '';
$repo  = $a['repo']  ?? '';
$ref   = $a['ref']   ?? 'main';
$base  = trim($a['base'] ?? '', '/');

if ($owner === '' || $repo === '') {
  fwrite(STDERR, "Usage: php scripts/gen_rawlinks.php owner=<owner> repo=<repo> [ref=<branch|sha>] [base=<subdir>]\n");
  exit(1);
}

// Dùng Git Trees API (recursive=1) để liệt kê toàn bộ cây file
$api = "https://api.github.com/repos/{$owner}/{$repo}/git/trees/".rawurlencode($ref)."?recursive=1";
$ch = curl_init($api);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_USERAGENT      => 'rawlinks-generator',
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_TIMEOUT        => 30,
  CURLOPT_HTTPHEADER     => ['Accept: application/vnd.github+json'],
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($code !== 200 || !$resp) {
  fwrite(STDERR, "GitHub API error: HTTP {$code}\n");
  exit(2);
}

$json = json_decode($resp, true);
$tree = $json['tree'] ?? [];
$files = [];
foreach ($tree as $node) {
  if (($node['type'] ?? '') !== 'blob') continue;
  $path = $node['path'] ?? '';
  if ($base !== '' && strpos($path, $base.'/') !== 0 && $path !== $base) continue;
  $files[] = $path;
}
sort($files, SORT_NATURAL);

$rawBase = "https://raw.githubusercontent.com/{$owner}/{$repo}/refs/heads/{$ref}/";

echo "# RAW Links — MassageNow\n\n";
echo "Owner/Repo: {$owner} / {$repo}  \n";
echo "Branch/Ref: {$ref}\n\n";
echo "| Purpose | Repo path | RAW URL |\n|---|---|---|\n";

foreach ($files as $p) {
  $purpose = 'File';
  if (preg_match('~/public_html/index\.php$~', $p))   $purpose = 'Front controller';
  elseif (preg_match('~/public_html/admin/~', $p))    $purpose = 'Admin';
  elseif (preg_match('~/public_html/api/~', $p))      $purpose = 'API';
  elseif (preg_match('~/views/~', $p))                $purpose = 'View';
  elseif (preg_match('~/app/~', $p))                  $purpose = 'App core';
  elseif (preg_match('~\.sql$~', $p))                 $purpose = 'SQL';

  $url = $rawBase . $p;
  echo '| ' . $purpose . ' | ' . $p . ' | ' . $url . " |\n";
}

echo "\n## Ghi chú\n";
echo "- Sinh tự động từ Git Trees API. Chạy lại sau mỗi commit để cập nhật rawlink.md.\n";
