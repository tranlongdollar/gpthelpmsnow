<?php
declare(strict_types=1);
/**
 * /public_html/admin/translations.php — Bước 17: UI sửa bản dịch theo trang (page_key) + ngôn ngữ
 * Làm việc trực tiếp với bảng i18n_texts(page_key, text_key, lang_code, text_html)
 */
require __DIR__ . '/../../app/auth.php';
auth_require_login();
$u = auth_user();

// Load languages
$langs = db_select("SELECT code, name, is_default FROM languages ORDER BY is_default DESC, code");
$codes = array_map(fn($r) => $r['code'], $langs);
$defaultCode = $codes[0] ?? 'vi';

// Filters
$page = trim($_GET['page'] ?? 'page.massageteam');
$q    = trim($_GET['q'] ?? '');

// Get keys for this page
$params = [$page];
$sql = "SELECT DISTINCT text_key FROM i18n_texts WHERE page_key=?";
if ($q !== '') { $sql .= " AND text_key LIKE ?"; $params[] = "%{$q}%"; }
$sql .= " ORDER BY text_key ASC";
$keys = db_select($sql, $params);

// Build map: [text_key][lang_code] => text_html
$map = [];
if ($keys) {
  $in = implode(',', array_fill(0, count($keys), '?'));
  $params = array_merge([$page], array_map(fn($r) => $r['text_key'], $keys));
  $rows = db_select("SELECT text_key, lang_code, text_html FROM i18n_texts WHERE page_key=? AND text_key IN ($in)", $params);
  foreach ($rows as $r) $map[$r['text_key']][$r['lang_code']] = $r['text_html'];
}

$csrf = csrf_token();
?><!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Translations</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 24px auto; max-width: 1200px; padding: 0 16px; }
    .top { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
    .muted { color:#666; font-size:13px; }
    .grid { display:grid; grid-template-columns: 1fr; gap:12px; }
    .card { border:1px solid #e5e7eb; border-radius:12px; padding:14px 16px; }
    table { width:100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border-bottom:1px solid #eee; padding: 8px 6px; text-align:left; vertical-align: top; }
    th { background:#fafafa; }
    textarea { width:100%; min-height:80px; padding:8px 10px; border:1px solid #ddd; border-radius:8px; font-family: inherit; }
    input[type="text"] { width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:8px; }
    a.btn, button.btn { display:inline-block; padding:8px 10px; border-radius:8px; background:#111; color:#fff; text-decoration:none; border:0; cursor:pointer; }
    .btn.gray { background:#6b7280; }
    .btn.warn { background:#b91c1c; }
    .row-actions a { margin-right: 6px; }
    details > summary { cursor: pointer; }
    code { background:#f6f8fa; padding:2px 6px; border-radius:6px; }
  </style>
</head>
<body>
  <div class="top">
    <div>
      <h1 style="margin:0">Translations</h1>
      <div class="muted">Xin chào, <?= e($u['name']) ?> — <a href="/admin/">Về Dashboard</a></div>
    </div>
    <form method="get" class="muted" style="display:flex; gap:8px; align-items:center;">
      <label>Page
        <input type="text" name="page" value="<?= e($page) ?>" placeholder="VD: page.massageteam">
      </label>
      <label>Tìm key
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="nhập 1 phần text_key...">
      </label>
      <button class="btn" type="submit">Tải</button>
      <a class="btn gray" href="/admin/translations.php?page=<?= urlencode($page) ?>">Reset</a>
    </form>
  </div>

  <div class="grid">
    <div class="card">
      <h3 style="margin:0 0 8px 0;">Thêm key mới cho page: <code><?= e($page) ?></code></h3>
      <form method="post" action="/admin/translation_save.php" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="page" value="<?= e($page) ?>">
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          <label style="flex:1">text_key
            <input type="text" name="text_key" placeholder="VD: hero.heading" required>
          </label>
          <label>lang_code
            <select name="lang_code">
              <?php foreach ($langs as $l): ?>
                <option value="<?= e($l['code']) ?>"><?= e($l['code']) ?> (<?= e($l['name']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
        <label style="margin-top:8px;">text_html (hỗ trợ HTML)
          <textarea name="text_html" placeholder="Nội dung hiển thị..."></textarea>
        </label>
        <div style="margin-top:8px;">
          <button class="btn" type="submit">Thêm</button>
        </div>
      </form>
    </div>

    <div class="card">
      <h3 style="margin:0 0 8px 0;">Danh sách key (<?= count($keys) ?>) — Page: <code><?= e($page) ?></code></h3>
      <?php if (empty($keys)): ?>
        <p class="muted">Chưa có key nào cho page này.</p>
      <?php else: ?>
        <form method="post" action="/admin/translation_save.php">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="page" value="<?= e($page) ?>">
          <table>
            <thead>
              <tr>
                <th width="240">text_key</th>
                <?php foreach ($langs as $l): ?>
                  <th><?= e($l['code']) ?> <span class="muted">(<?= e($l['name']) ?>)</span></th>
                <?php endforeach; ?>
                <th width="120">Hành động</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($keys as $k): $key=$k['text_key']; ?>
                <tr>
                  <td><code><?= e($key) ?></code></td>
                  <?php foreach ($langs as $l): $code=$l['code']; $val=$map[$key][$code] ?? ''; ?>
                    <td>
                      <textarea name="text[<?= e($key) ?>][<?= e($code) ?>]" placeholder="(<?= e($code) ?>) nội dung..."><?= e($val) ?></textarea>
                    </td>
                  <?php endforeach; ?>
                  <td class="row-actions">
                    <a class="btn warn" href="/admin/translation_delete.php?page=<?= urlencode($page) ?>&key=<?= urlencode($key) ?>" onclick="return confirm('Xoá toàn bộ bản dịch của key này trên mọi ngôn ngữ?');">Xoá key</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div style="margin-top:8px;">
            <button class="btn" type="submit">Lưu tất cả thay đổi</button>
          </div>
        </form>
      <?php endif; ?>
    </div>

    <details class="card">
      <summary><strong>Hướng dẫn</strong></summary>
      <ul>
        <li>Bảng dùng: <code>i18n_texts(page_key, text_key, lang_code, text_html)</code>.</li>
        <li>Page mặc định của bạn: <code>page.massageteam</code> (đang dùng ở view city).</li>
        <li>Key gợi ý: <code>meta.title</code>, <code>meta.description</code>, <code>nav.links.team</code>, <code>hero.heading</code>, <code>hero.paragraph_html</code>, <code>booking.fields.name</code>, <code>footer.copy</code>, v.v.</li>
        <li>Trường <em>text_html</em> có thể chứa HTML (ví dụ đoạn hero có thẻ <code>&lt;b&gt;</code>).</li>
      </ul>
    </details>
  </div>
</body>
</html>
