<?php
/**
 * gen_rawlinks.php
 * Tạo bảng RAW URL (Markdown) cho toàn bộ repo GitHub (hoặc 1 thư mục con).
 *
 * Cách dùng (khuyến nghị – quét toàn repo, branch=main):
 *   php scripts/gen_rawlinks.php owner=tranlongdollar repo=gpthelpmsnow ref=main
 *
 * Tuỳ chọn:
 *   base=<subdir>   : chỉ liệt kê file dưới thư mục này (vd: base=massagenow.vn)
 *   output=<path>   : đường dẫn file đầu ra (vd: output=massagenow.vn/rawlink.md)
 *   mode=branch|sha : kiểu RAW link (mặc định: branch). Nếu sha, sẽ fix theo commit.
 *
 * Gợi ý:
 *   - Đặt biến môi trường GITHUB_TOKEN để tăng rate-limit API (public repo không bắt buộc).
 */

declare(strict_types=1);

parse_str(implode('&', array_slice($argv, 1)), $a);
$owner  = (string)($a['owner'] ?? '');
$repo   = (string)($a['repo']  ?? '');
$ref    = (string)($a['ref']   ?? 'main');        // nhánh hoặc SHA
$base   = trim((string)($a['base'] ?? ''), '/');  // thư mục con (optional)
$mode   = (string)($a['mode'] ?? 'branch');       // 'branch' (refs/heads/<ref>) hoặc 'sha'
$output = (string)($a['output'] ?? '');           // file đầu ra (optional)
$token  = getenv('GITHUB_TOKEN') ?: '';

if ($owner === '' || $repo === '') {
    fwrite(STDERR, "Usage: php scripts/gen_rawlinks.php owner=<owner> repo=<repo> [ref=<branch|sha>] [base=<subdir>] [output=<path>] [mode=branch|sha]\n");
    exit(1);
}

function curl_json(string $url, string $token = ''): array {
    $ch = curl_init($url);
    $headers = ['Accept: application/vnd.github+json', 'User-Agent: rawlinks-generator'];
    if ($token !== '') $headers[] = "Authorization: Bearer {$token}";
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$resp) {
        fwrite(STDERR, "[ERR] GitHub API $code: $url\n");
        return [];
    }
    $json = json_decode($resp, true);
    return is_array($json) ? $json : [];
}

/** 1) Lấy cây file toàn repo (Git Trees API) */
$treeUrl = "https://api.github.com/repos/{$owner}/{$repo}/git/trees/".rawurlencode($ref)."?recursive=1";
$treeRes = curl_json($treeUrl, $token);
$tree    = $treeRes['tree'] ?? [];
if (!$tree) {
    fwrite(STDERR, "[ERR] Không lấy được tree từ ref={$ref}. Kiểm tra owner/repo/ref.\n");
    exit(2);
}

/** 2) Xác định SHA pinned (để tạo RAW cố định theo commit) */
$shaPinned = '';
// Cách an toàn: hỏi API commits/<ref> để resolve branch -> commit SHA
$commitUrl = "https://api.github.com/repos/{$owner}/{$repo}/commits/".rawurlencode($ref);
$commitRes = curl_json($commitUrl, $token);
if (!empty($commitRes['sha'])) {
    $shaPinned = $commitRes['sha'];
} elseif (!empty($treeRes['sha'])) {
    // fallback: đôi khi /git/trees trả về sha top-level
    $shaPinned = $treeRes['sha'];
}

/** 3) Lọc danh sách file (blob) theo base (nếu có) */
$files = [];
foreach ($tree as $node) {
    if (($node['type'] ?? '') !== 'blob') continue;
    $path = $node['path'] ?? '';
    if ($path === '') continue;
    if ($base !== '' && strpos($path, $base . '/') !== 0 && $path !== $base) continue;
    $files[] = $path;
}
sort($files, SORT_NATURAL);

/** 4) Xác định đường dẫn RAW theo branch & theo sha */
$rawBranchBase = "https://raw.githubusercontent.com/{$owner}/{$repo}/refs/heads/{$ref}/";
$rawShaBase    = $shaPinned ? "https://raw.githubusercontent.com/{$owner}/{$repo}/{$shaPinned}/" : '';

/** 5) Xác định file đầu ra */
if ($output === '') {
    $preferUnder = ($base !== '') ? $base : (is_dir('massagenow.vn') ? 'massagenow.vn' : '');
    $output = $preferUnder !== '' ? "{$preferUnder}/rawlink.md" : "rawlink.md";
}
$outDir = dirname($output);
if ($outDir !== '' && $outDir !== '.' && !is_dir($outDir)) {
    @mkdir($outDir, 0777, true);
}

/** 6) Ghi file Markdown */
$md  = "# RAW Links — {$owner}/{$repo}\n\n";
$md .= "Branch/Ref: {$ref}";
if ($shaPinned) $md .= "  (pinned SHA: `{$shaPinned}`)";
$md .= "\n\n";
$md .= "| Purpose | Repo path | RAW (branch) | RAW (pinned) |\n";
$md .= "|---|---|---|---|\n";

foreach ($files as $p) {
    // Phân loại Purpose
    $purpose = 'File';
    if (preg_match('~/public_html/index\.php$~', $p))   $purpose = 'Front controller';
    elseif (preg_match('~/public_html/admin/~', $p))    $purpose = 'Admin';
    elseif (preg_match('~/public_html/api/~', $p))      $purpose = 'API';
    elseif (preg_match('~/views/~', $p))                $purpose = 'View';
    elseif (preg_match('~/app/~', $p))                  $purpose = 'App core';
    elseif (preg_match('~\.sql$~', $p))                 $purpose = 'SQL';
    elseif (preg_match('~\.md$~i', $p))                 $purpose = 'Doc';

    $rawBranch = $rawBranchBase . $p;
    $rawPinned = $shaPinned ? ($rawShaBase . $p) : '';

    $md .= '| ' . $purpose . ' | ' . $p . ' | ' . $rawBranch . ' | ' . ($rawPinned ?: '-') . " |\n";
}

$md .= "\n## Ghi chú\n";
$md .= "- Tự sinh bởi `scripts/gen_rawlinks.php`.\n";
$md .= "- Sửa `base=` để chỉ liệt kê 1 thư mục con (vd: `base=massagenow.vn`).\n";
$md .= "- Dùng `mode=sha` nếu bạn chỉ muốn cột RAW (pinned) ổn định theo 1 commit.\n";

if (file_put_contents($output, $md) === false) {
    fwrite(STDERR, "[ERR] Không ghi được file: {$output}\n");
    echo $md; // In ra stdout như fallback
    exit(3);
}

fwrite(STDERR, "[OK] Generated: {$output}\n");
