<?php
declare(strict_types=1);
/**
 * /public_html/admin/page_massageteam.php
 * Màn hình chuyên sửa nội dung page.massageteam theo từng ngôn ngữ
 */
require __DIR__ . '/../../app/auth.php';
auth_require_login();

$pageKey = 'page.massageteam';

// Danh sách field cố định cho page.massageteam
// type: text | textarea (textarea cho phép HTML)
$FIELDS = [
  'meta.title'              => ['label' => 'Meta Title',            'type'=>'text'],
  'meta.description'        => ['label' => 'Meta Description',      'type'=>'textarea'],

  'nav.brandline'           => ['label' => 'Brand line',            'type'=>'text'],
  'nav.links.services'      => ['label' => 'Link: Services',        'type'=>'text'],
  'nav.links.team'          => ['label' => 'Link: Team',            'type'=>'text'],
  'nav.links.booking'       => ['label' => 'Link: Booking',         'type'=>'text'],
  'nav.cta'                 => ['label' => 'CTA Text',              'type'=>'text'],

  'hero.heading'            => ['label' => 'Hero Heading',          'type'=>'text'],
  'hero.paragraph_html'     => ['label' => 'Hero Paragraph (HTML)', 'type'=>'textarea'],

  'services.title'          => ['label' => 'Services Title',        'type'=>'text'],
  'services.subtitle'       => ['label' => 'Services Subtitle',     'type'=>'text'],
  'services.card.duration'  => ['label' => 'Services: Duration mask (vd %d phút / %d mins)', 'type'=>'text'],
  'services.card.from_price'=> ['label' => 'Services: From price mask (vd Từ %price%)', 'type'=>'text'],
  'services.cta'            => ['label' => 'Services: CTA',         'type'=>'text'],

  'team.title'              => ['label' => 'Team Title',            'type'=>'text'],
  'team.subtitle'           => ['label' => 'Team Subtitle',         'type'=>'text'],
  'team.card.skills'        => ['label' => 'Team: Skills label',    'type'=>'text'],

  'booking.title'           => ['label' => 'Booking Title',         'type'=>'text'],
  'booking.subtitle'        => ['label' => 'Booking Subtitle',      'type'=>'text'],
  'booking.fields.name'     => ['label' => 'Booking: Field Name',   'type'=>'text'],
  'booking.fields.email'    => ['label' => 'Booking: Field Email',  'type'=>'text'],
  'booking.fields.phone'    => ['label' => 'Booking: Field Phone',  'type'=>'text'],
  'booking.fields.service'  => ['label' => 'Booking: Field Service','type'=>'text'],
  'booking.fields.datetime' => ['label' => 'Booking: Field Datetime','type'=>'text'],
  'booking.fields.note'     => ['label' => 'Booking: Field Note',   'type'=>'text'],
  'booking.button'          => ['label' => 'Booking Button',        'type'=>'text'],
  'booking.msg.sending'     => ['label' => 'Booking Msg Sending',   'type'=>'text'],
  'booking.msg.success'     => ['label' => 'Booking Msg Success',   'type'=>'text'],
  'booking.msg.fail'        => ['label' => 'Booking Msg Fail',      'type'=>'text'],
  'booking.msg.network'     => ['label' => 'Booking Msg Network',   'type'=>'text'],

  'footer.copy'             => ['label' => 'Footer Copyright',      'type'=>'text'],
];

$u = auth_user();
$langs = db_select("SELECT code, name, is_default FROM languages ORDER BY is_default DESC, code");
$codes = array_map(fn($l) => $l['code'], $langs);
$defaultCode = $codes[0] ?? 'vi';

// Đọc toàn bộ bản dịch hiện có cho page_key vào map [$lang][$key] = text_html
$inKeys = implode(',', array_fill(0, count($FIELDS), '?'));
$params = array_merge([$pageKey], array_keys($FIELDS));
$rows = db_select("SELECT text_key, lang_code, text_html FROM i18n_texts WHERE page_key=? AND text_key IN ($inKeys)", $params);
$MAP = [];
foreach ($rows as $r) $MAP[$r['lang_code']][$r['text_key']] = $r['text_html'] ?? '';

$csrf = csrf_token();
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Chỉnh nội dung — page.massageteam</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:24px auto;max-width:1100px;padding:0 16px}
    .top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
    .muted{color:#666;font-size:13px}
    .card{border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;margin-top:12px}
    .tabs a{display:inline-block;padding:8px 12px;border:1px solid #e5e7eb;border-bottom:0;border-radius:10px 10px 0 0;margin-right:6px;background:#fafafa;text-decoration:none;color:#111}
    .tabs a.active{background:#fff;font-weight:700}
    .pane{border:1px solid #e5e7eb;border-radius:0 10px 10px 10px;padding:12px}
    label{display:block;margin:8px 0 4px}
    input[type=text]{width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:8px}
    textarea{width:100%;min-height:90px;padding:8px 10px;border:1px solid #ddd;border-radius:8px;font-family:inherit}
    .row{display:grid;grid-template-columns:1fr;gap:10px}
    .btn{display:inline-block;padding:10px 14px;border-radius:10px;background:#111;color:#fff;text-decoration:none;border:0;cursor:pointer}
    .btn.gray{background:#6b7280}
    code{background:#f6f8fa;padding:2px 6px;border-radius:6px}
  </style>
</head>
<body>
  <div class="top">
    <div>
      <h1 style="margin:0">Chỉnh nội dung: <code><?= $pageKey ?></code></h1>
      <div class="muted">Xin chào, <?= e($u['name']) ?> — <a href="/admin/">Về Dashboard</a></div>
    </div>
    <div>
      <a class="btn gray" href="/admin/translations.php?page=page.massageteam">Bảng dịch nâng cao</a>
    </div>
  </div>

  <div class="card">
    <div class="tabs">
      <?php foreach ($langs as $i=>$l): ?>
        <a href="#" class="<?= $i===0?'active':'' ?>" onclick="return S.tab(event,'tab-<?= e($l['code']) ?>')">
          <?= e($l['code']) ?> (<?= e($l['name']) ?>)
        </a>
      <?php endforeach; ?>
    </div>

    <form method="post" action="/admin/page_massageteam_save.php" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="page_key" value="<?= e($pageKey) ?>">

      <div class="pane">
        <?php foreach ($langs as $i=>$l):
          $code = $l['code'];
          $vals = $MAP[$code] ?? [];
        ?>
          <div id="tab-<?= e($code) ?>" style="<?= $i===0?'':'display:none' ?>">
            <?php foreach ($FIELDS as $key=>$cfg): $val = $vals[$key] ?? ''; ?>
              <label><b><?= e($cfg['label']) ?></b> <span class="muted">(<code><?= e($key) ?></code>)</span></label>
              <?php if ($cfg['type']==='textarea'): ?>
                <textarea name="text[<?= e($code) ?>][<?= e($key) ?>]"><?= e($val) ?></textarea>
              <?php else: ?>
                <input type="text" name="text[<?= e($code) ?>][<?= e($key) ?>]" value="<?= e($val) ?>">
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <div style="margin-top:10px">
        <button class="btn" type="submit">Lưu thay đổi</button>
        <a class="btn gray" href="/admin/">Huỷ</a>
      </div>
    </form>

    <p class="muted" style="margin-top:12px">
      Biến hỗ trợ: <code>%thanh_pho%</code>, <code>%year%</code>. Trường có đuôi <em>(HTML)</em> cho phép thẻ HTML.
    </p>
  </div>

<script>
var S={tab:function(ev,id){
  ev.preventDefault();
  document.querySelectorAll('.tabs a').forEach(a=>a.classList.remove('active'));
  ev.target.classList.add('active');
  document.querySelectorAll('[id^="tab-"]').forEach(p=>p.style.display='none');
  var el=document.getElementById(id); if(el) el.style.display='';
  return false;
}};
</script>
</body>
</html>
