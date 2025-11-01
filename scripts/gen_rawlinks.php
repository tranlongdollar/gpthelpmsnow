<?php
/**
 * gen_rawlinks.php
 * Quét toàn bộ file dưới 1 thư mục của repo GitHub và in ra bảng RAW URLs (Markdown).
 *
 * Cách chạy (ví dụ):
 *   php scripts/gen_rawlinks.php owner=tranlongdollar repo=gpthelpmsnow ref=main base=massagenow.vn > rawlink.md
 *
 * Tham số:
 *   owner   : chủ sở hữu repo
 *   repo    : tên repo
 *   ref     : nhánh hoặc commit SHA (vd: main)
 *   base    : thư mục gốc cần quét (vd: massagenow.vn) — để trống sẽ quét root
 *
 * Yêu cầu: php có extension curl; repo public (không cần token).
 */

parse_str(implode('&', array_slice($argv, 1)), $args);
$owner = $args['owner'] ?? '';
$repo  = $args['repo']  ?? '';
$ref   = $args['ref']   ?? 'main';
$base  = trim($args['base'] ?? '', '/');

if ($owner === '' || $repo === '') {
    fwrite(STDERR, "Usage: php scripts/gen_rawlinks.php owner=<owner> repo=<repo> ref=<branch|sha> [base=<subdir>]\n");
    exit(1);
}

function gh_api_contents(string $owner, string $repo, string $path, string $ref): array {
    $url = "https://api.github.com/repos/{$owner}/{$repo}/contents/".rawurlencode($path)."?ref=".rawurlencode($ref);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT      => 'rawlinks-generator', // GitHub API cần UA
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $out = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http !== 200 || !$out) return [];
    $json = json_decode($out, true);
    return is_array($json) ? $json : [];
}

function walk(string $owner, string $repo, string $ref, string $path = ''): array {
    $items = gh_api_contents($owner, $repo, $path, $ref);
    $files = [];
    foreach ($items as $it) {
        if (!isset($it['type'], $it['path'])) continue;
        if ($it['type'] === 'file') {
            $files[] = $it['path'];
        } elseif ($it['type'] === 'dir') {
            $files = array_merge($files, walk($owner, $repo, $ref, $it['path']));
        }
    }
    return $files;
}

$root = $base === '' ? '' : $base;
$all  = walk($owner, $repo, $ref, $root);

$header = <<<MD
# RAW Links — MassageNow

Owner/Repo: {$owner} / {$repo}  
Branch/Ref: {$ref}

| Purpose | Repo path | RAW URL (branch) |
|---|---|---|
MD;

echo $header, "\n";

$rawBase = "https://raw.githubusercontent.com/{$owner}/{$repo}/refs/heads/{$ref}/";

foreach ($all as $p) {
    // Bạn có thể tinh chỉnh "Purpose" theo đuôi file/đường dẫn
    $purpose = '';
    if (preg_match('~/public_html/index\.php$~', $p))   $purpose = 'Front controller';
    elseif (preg_match('~/public_html/admin/~', $p))    $purpose = 'Admin';
    elseif (preg_match('~/public_html/api/~', $p))      $purpose = 'API';
    elseif (preg_match('~/views/~', $p))                $purpose = 'View';
    elseif (preg_match('~/app/~', $p))                  $purpose = 'App core';
    elseif (preg_match('~\.sql$~', $p))                 $purpose = 'SQL';
    else                                                $purpose = 'File';

    $url = $rawBase . $p;
    echo '| ', $purpose, ' | ', $p, ' | ', $url, " |\n";
}

echo "\n## Ghi chú\n";
echo "- Bảng này được sinh tự động từ GitHub API. Cập nhật khi repo thay đổi.\n";
