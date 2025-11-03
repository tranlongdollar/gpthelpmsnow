<?php
/**
 * Generate rawlink-top.txt — only the minimal runtime set for bots:
 *   - mysql.sql
 *   - app/*.php
 *   - public_html/index.php
 *   - public_html/api/*.php
 *   - public_html/admin/*.php
 *   - views/*.php
 * No docs, no workflows, no markdown.
 */
parse_str(implode('&', array_slice($argv, 1)), $a);
$owner = $a['owner'] ?? '';
$repo  = $a['repo']  ?? '';
$ref   = $a['ref']   ?? 'main';
if (!$owner || !$repo) { fwrite(STDERR, "ERR: owner/repo required\n"); exit(1); }

$token = getenv('GITHUB_TOKEN') ?: getenv('GH_TOKEN') ?: '';
$hdrs  = [
  'Accept: application/vnd.github+json',
  'User-Agent: rawlinks-generator',
];
if ($token) $hdrs[] = "Authorization: Bearer $token";

$treesUrl = "https://api.github.com/repos/$owner/$repo/git/trees/".rawurlencode($ref)."?recursive=1";
$ch = curl_init($treesUrl);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => 1,
  CURLOPT_HTTPHEADER     => $hdrs,
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($code !== 200 || !$resp) { fwrite(STDERR,"ERR: tree ($code)\n"); exit(1); }

$tree = json_decode($resp, true)['tree'] ?? [];
$raw  = "https://raw.githubusercontent.com/$owner/$repo/refs/heads/$ref/";

$keep = [
  '~^mysql\.sql$~',
  '~^massagenow\.vn/app/.*\.php$~',
  '~^massagenow\.vn/public_html/index\.php$~',
  '~^massagenow\.vn/public_html/api/.*\.php$~',
  '~^massagenow\.vn/public_html/admin/.*\.php$~',
  '~^massagenow\.vn/views/.*\.php$~',
];

$lines = [];
foreach ($tree as $n) {
  if (($n['type'] ?? '') !== 'blob') continue;
  $p = $n['path'] ?? '';
  foreach ($keep as $rg) {
    if (preg_match($rg, $p)) { $lines[] = $raw.$p; break; }
  }
}
sort($lines, SORT_NATURAL | SORT_FLAG_CASE);
file_put_contents('rawlink-top.txt', implode("\n", $lines)."\n");
