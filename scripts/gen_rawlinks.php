<?php
/**
 * Generate rawlink.md (FULL) — excludes docs (.md/.yml/.yaml/.json/.lock), keeps runtime files.
 * Adds pinned SHA column. Warns if tree is truncated.
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

$j = json_decode($resp, true);
$truncated = !empty($j['truncated']);
$tree = $j['tree'] ?? [];
usort($tree, fn($x,$y)=>strcmp($x['path']??'', $y['path']??''));

// pinned SHA
$commitUrl = "https://api.github.com/repos/$owner/$repo/commits/".rawurlencode($ref);
$ch = curl_init($commitUrl);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => 1,
  CURLOPT_HTTPHEADER     => $hdrs,
]);
$cres = curl_exec($ch);
curl_close($ch);
$sha = '';
if ($cres) { $cj = json_decode($cres, true); $sha = $cj['sha'] ?? ''; }

$rawBranch = "https://raw.githubusercontent.com/$owner/$repo/refs/heads/$ref/";
$rawSha    = $sha ? "https://raw.githubusercontent.com/$owner/$repo/$sha/" : '';

// skip docs & config junk
$skipExt = '~\.(md|markdown|ya?ml|json|lock)$~i';

$out  = "# RAW Links — $owner/$repo\n\n";
$out .= "Branch/Ref: `$ref`".($sha?"  (pinned SHA: `$sha`)":"")."\n";
if ($truncated) $out .= "\n> **Warning:** Git tree is *truncated* by GitHub API. Some files may be missing.\n";
$out .= "\n| Purpose | Repo path | RAW (branch) | RAW (pinned) |\n|---|---|---|---|\n";

foreach ($tree as $n) {
  if (($n['type'] ?? '') !== 'blob') continue;
  $p = $n['path'] ?? '';
  if ($p === '') continue;
  if (preg_match($skipExt, $p)) continue; // drop docs

  // purpose tags
  $purpose = 'File';
  if (preg_match('~/public_html/index\.php$~', $p))                $purpose = 'Front controller';
  elseif (preg_match('~/public_html/admin/.*\.php$~', $p))         $purpose = 'Admin';
  elseif (preg_match('~/public_html/api/.*\.php$~', $p))           $purpose = 'API';
  elseif (preg_match('~/views/.*\.php$~', $p))                     $purpose = 'View';
  elseif (preg_match('~/app/.*\.php$~', $p))                       $purpose = 'App core';
  elseif (preg_match('~\.sql$~i', $p))                              $purpose = 'SQL';
  elseif (preg_match('~\.(png|jpe?g|webp|ico|gif|svg)$~i', $p))     $purpose = 'Asset';

  $out .= "| $purpose | $p | {$rawBranch}{$p} | ".($sha ? $rawSha.$p : '-') ." |\n";
}
file_put_contents('rawlink.md', $out);
