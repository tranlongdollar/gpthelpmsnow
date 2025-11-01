<?php
declare(strict_types=1);
/**
 * /app/i18n.php — I18n loader cho PHP thuần (key-value trong MySQL)
 *
 * Bảng dùng:
 *  - languages(code,name,is_default)
 *  - translation_keys(id,namespace,tkey,description)
 *  - translations(id,key_id,lang_code,tvalue)
 *
 * Hàm chính:
 *  - i18n_load(PDO $pdo, string $namespace, string $lang): array   // nạp map [tkey => tvalue] có fallback
 *  - i18n_get(array $map, string $key, array $vars=[] , string $def=''): string  // lấy & render 1 key
 *  - i18n_render(string $tpl, array $vars=[]): string              // thay %var% trong chuỗi
 *  - i18n_clear_cache(): void                                      // xoá cache APCu (nếu dùng)
 *
 * Sử dụng trong controller/view:
 *   $txt = i18n_load(pdo(), 'page.massageteam', $lang);
 *   $title = i18n_get($txt, 'meta.title', ['thanh_pho' => $city['name']]);
 */

// -------------------------
// Cache helpers (APCu optional)
// -------------------------
function _i18n_cache_get(string $key) {
  return (function_exists('apcu_fetch') && ini_get('apc.enabled')) ? apcu_fetch($key) : false;
}
function _i18n_cache_set(string $key, $val, int $ttl = 300): void {
  if (function_exists('apcu_store') && ini_get('apc.enabled')) {
    apcu_store($key, $val, $ttl);
  }
}
function i18n_clear_cache(): void {
  if (function_exists('apcu_clear_cache') && ini_get('apc.enabled')) {
    apcu_clear_cache();
  }
}

// -------------------------
// Core loader
// -------------------------
function i18n_load(PDO $pdo, string $namespace, string $lang): array {
  $cacheKey = 'i18n:' . $namespace . ':' . $lang;
  $cached = _i18n_cache_get($cacheKey);
  if ($cached !== false && is_array($cached)) return $cached;

  // 1) Lấy code ngôn ngữ mặc định (ví dụ 'vi')
  $stmt = $pdo->query("SELECT code FROM languages WHERE is_default=1 LIMIT 1");
  $defaultLang = ($row = $stmt->fetch()) ? ($row['code'] ?? 'vi') : 'vi';

  // 2) Lấy tất cả keys theo namespace
  $sqlKeys = "SELECT id, tkey FROM translation_keys WHERE namespace = ?";
  $keys = $pdo->prepare($sqlKeys);
  $keys->execute([$namespace]);
  $mapKeys = [];
  while ($r = $keys->fetch()) {
    $mapKeys[(int)$r['id']] = $r['tkey'];
  }
  if (!$mapKeys) return []; // không có key nào

  $ids = array_keys($mapKeys);
  $placeholders = implode(',', array_fill(0, count($ids), '?'));

  // 3) Lấy bản dịch theo $lang hiện tại
  $sqlTr = "SELECT key_id, tvalue FROM translations WHERE lang_code=? AND key_id IN ($placeholders)";
  $stmtTr = $pdo->prepare($sqlTr);
  $stmtTr->execute(array_merge([$lang], $ids));
  $first = [];
  while ($r = $stmtTr->fetch()) {
    $first[(int)$r['key_id']] = $r['tvalue'];
  }

  // 4) Fallback về default lang nếu thiếu
  $missingIds = array_values(array_diff($ids, array_keys($first)));
  $fallback = [];
  if ($missingIds) {
    $ph = implode(',', array_fill(0, count($missingIds), '?'));
    $sqlFb = "SELECT key_id, tvalue FROM translations WHERE lang_code=? AND key_id IN ($ph)";
    $stmtFb = $pdo->prepare($sqlFb);
    $stmtFb->execute(array_merge([$defaultLang], $missingIds));
    while ($r = $stmtFb->fetch()) {
      $fallback[(int)$r['key_id']] = $r['tvalue'];
    }
  }

  // 5) Ghép map cuối cùng theo tkey
  $out = [];
  foreach ($ids as $id) {
    $tkey = $mapKeys[$id];
    if (isset($first[$id])) {
      $out[$tkey] = (string)$first[$id];
    } elseif (isset($fallback[$id])) {
      $out[$tkey] = (string)$fallback[$id];
    } else {
      $out[$tkey] = ''; // chưa có bản dịch
    }
  }

  _i18n_cache_set($cacheKey, $out, 300); // cache 5 phút
  return $out;
}

// -------------------------
// Render helpers
// -------------------------

/**
 * Thay %var% trong template (không đụng tới HTML).
 * - Ví dụ: i18n_render('Xin chào %name%', ['name'=>'Lan'])
 */
function i18n_render(string $tpl, array $vars = []): string {
  if (!$tpl || !$vars) return $tpl;
  // Chuẩn hoá: chấp nhận cả ['year' => 2025] để thay %year%
  foreach ($vars as $k => $v) {
    $tpl = str_replace('%' . $k . '%', (string)$v, $tpl);
  }
  return $tpl;
}

/**
 * Lấy value theo key và render biến.
 * - $def: nếu rỗng và không có key → trả $def
 * - Không escape HTML: tuỳ view dùng echo hay echo nl2br/e()
 */
function i18n_get(array $map, string $key, array $vars = [], string $def = ''): string {
  $val = $map[$key] ?? $def;
  return i18n_render($val, $vars);
}

// -------------------------
// Tiện ích SEO hreflang (tuỳ chọn dùng ở Bước 20)
// -------------------------

/**
 * Sinh danh sách <link rel="alternate" hreflang=".."> dựa vào bảng languages.
 * $path: phần đường dẫn (ví dụ "/ha-noi")
 * Nếu ngôn ngữ đặt qua query (?lang=xx), ta tạo URL dưới dạng "{$base}{$path}?lang={$code}"
 */
function i18n_hreflang_links(PDO $pdo, string $baseUrl, string $path): array {
  $stmt = $pdo->query("SELECT code, is_default FROM languages ORDER BY is_default DESC, code");
  $rows = $stmt->fetchAll();
  $links = [];
  foreach ($rows as $r) {
    $code = $r['code'];
    $url = rtrim($baseUrl, '/') . $path . '?lang=' . urlencode($code);
    $links[] = [
      'code' => $code,
      'url'  => $url,
      'rel'  => 'alternate',
    ];
  }
  return $links;
}
function i18n_hreflang_links_prefix(PDO $pdo, string $baseUrl, string $slugPath): array {
  // $slugPath = '/{slug}', ví dụ '/ha-noi'
  $rows = db_select("SELECT code FROM languages ORDER BY is_default DESC, code");
  $links = [];
  foreach ($rows as $r) {
    $code = $r['code'];
    $url = rtrim($baseUrl, '/') . '/' . $code . $slugPath;
    $links[] = ['code' => $code, 'url' => $url, 'rel' => 'alternate'];
  }
  return $links;
}

