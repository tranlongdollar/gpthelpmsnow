<?php
declare(strict_types=1);
/**
 * /public_html/admin/index.php
 * Dashboard Admin (AdminLTE) — login bắt buộc qua session
 */

require __DIR__ . '/../../app/config.php';
require __DIR__ . '/../../app/db.php';
require __DIR__ . '/../../app/auth.php';

auth_require_login();
$u = auth_user();

if (!function_exists('e')) {
  function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}

/* ----------------- THẺ NHANH ----------------- */
$citiesCount   = (int) db_value("SELECT COUNT(*) FROM cities");
$servicesCount = (int) db_value("SELECT COUNT(*) FROM services WHERE active=1");
$staffCount    = (int) db_value("SELECT COUNT(*) FROM staff   WHERE active=1");
$usersCount    = (int) db_value("SELECT COUNT(*) FROM users");

/* ----------------- BIỂU ĐỒ 1: ĐƠN THEO NGÀY + TRẠNG THÁI ----------------- */
$days = 14;
$series = ['new','confirmed','in_progress','done','canceled'];

// Lấy đếm theo ngày + trạng thái trong $days gần nhất
$rows = db_select("
  SELECT DATE(created_at) d, status, COUNT(*) c
  FROM bookings
  WHERE created_at >= (CURDATE() - INTERVAL ? DAY)
  GROUP BY DATE(created_at), status
", [$days-1]);

// Tạo list ngày liên tục
$labels = [];
for ($i = $days-1; $i >= 0; $i--) {
  $labels[] = (new DateTimeImmutable('today'))->sub(new DateInterval('P'.$i.'D'))->format('Y-m-d');
}

// Map dữ liệu: [status][date] => count
$matrix = [];
foreach ($series as $st) {
  $matrix[$st] = array_fill_keys($labels, 0);
}
foreach ($rows as $r) {
  $d = $r['d']; $st = $r['status']; $c = (int)$r['c'];
  if (isset($matrix[$st][$d])) $matrix[$st][$d] = $c;
}

// Chuẩn bị dataset cho Chart.js
$datasets = [];
$palette = [
  'new'         => '#94a3b8', // slate
  'confirmed'   => '#60a5fa', // blue
  'in_progress' => '#f59e0b', // amber
  'done'        => '#34d399', // green
  'canceled'    => '#f87171', // red
];
foreach ($series as $st) {
  $datasets[] = [
    'label' => strtoupper(str_replace('_',' ',$st)),
    'data'  => array_values($matrix[$st]),
    'backgroundColor' => $palette[$st] ?? '#999',
    'borderColor'     => $palette[$st] ?? '#999',
    'borderWidth'     => 1,
    'barPercentage'   => 0.9,
    'categoryPercentage' => 0.7,
    'stack'           => 'orders',
  ];
}
$labelsJson   = json_encode($labels, JSON_UNESCAPED_UNICODE);
$datasetsJson = json_encode($datasets, JSON_UNESCAPED_UNICODE);

/* ----------------- BIỂU ĐỒ 2: TOP THÀNH PHỐ 30 NGÀY ----------------- */
$rowsCity = db_select("
  SELECT c.name AS city, COUNT(*) AS cnt
  FROM bookings b
  JOIN cities c ON c.id=b.city_id
  WHERE b.created_at >= (NOW() - INTERVAL 30 DAY)
  GROUP BY b.city_id
  ORDER BY cnt DESC
  LIMIT 10
");
$cityLabels = array_map(fn($r)=>$r['city'], $rowsCity);
$cityCounts = array_map(fn($r)=>(int)$r['cnt'], $rowsCity);
$cityLabelsJson = json_encode($cityLabels, JSON_UNESCAPED_UNICODE);
$cityCountsJson = json_encode($cityCounts, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Massagenow</title>

  <!-- AdminLTE 3 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

  <style>.small-box .inner h3{font-weight:700}</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item d-none d-sm-inline-block">
        <a href="/admin/index.php" class="nav-link">Trang chủ</a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="/admin/logout.php" class="nav-link">Đăng xuất (<?= e($u['name'] ?? $u['email'] ?? 'User') ?>)</a>
      </li>
    </ul>
  </nav>

  <!-- Sidebar -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="/admin/index.php" class="brand-link">
      <img src="/admin/logo.png" alt="Logo" class="brand-image img-circle elevation-3" style="opacity:.8">
      <span class="brand-text font-weight-light">Massagenow</span>
    </a>

    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item">
            <a href="/admin/index.php" class="nav-link active">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Bảng điều khiển</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="/admin/cities.php" class="nav-link">
              <i class="nav-icon fas fa-city"></i>
              <p>Quản lý Thành phố</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="/admin/services.php" class="nav-link">
              <i class="nav-icon fas fa-cogs"></i>
              <p>Quản lý Dịch vụ</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="/admin/orders.php" class="nav-link">
              <i class="nav-icon fas fa-box"></i>
              <p>Quản lý Đơn hàng</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="/admin/staff.php" class="nav-link">
              <i class="nav-icon fas fa-users"></i>
              <p>Quản lý Nhân viên</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="/admin/users.php" class="nav-link">
              <i class="nav-icon fas fa-user"></i>
              <p>Người dùng</p>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </aside>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <!-- Header -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6"><h1>Bảng điều khiển</h1></div>
        </div>
      </div>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">

        <!-- Cards -->
        <div class="row">
          <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
              <div class="inner"><h3><?= number_format($citiesCount) ?></h3><p>Thành phố</p></div>
              <div class="icon"><i class="fas fa-city"></i></div>
              <a href="/admin/cities.php" class="small-box-footer">Quản lý Thành phố <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
              <div class="inner"><h3><?= number_format($servicesCount) ?></h3><p>Dịch vụ (active)</p></div>
              <div class="icon"><i class="fas fa-cogs"></i></div>
              <a href="/admin/services.php" class="small-box-footer">Quản lý Dịch vụ <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
              <div class="inner"><h3><?= number_format($staffCount) ?></h3><p>Nhân viên (active)</p></div>
              <div class="icon"><i class="fas fa-users"></i></div>
              <a href="/admin/staff.php" class="small-box-footer">Quản lý Nhân viên <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
              <div class="inner"><h3><?= number_format($usersCount) ?></h3><p>Người dùng</p></div>
              <div class="icon"><i class="fas fa-user"></i></div>
              <a href="/admin/users.php" class="small-box-footer">Quản lý Người dùng <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
        </div>

        <!-- Charts -->
        <div class="row">
          <!-- Chart 1 -->
          <div class="col-lg-8">
            <div class="card">
              <div class="card-header"><h3 class="card-title">Đơn theo ngày (<?= (int)$days ?> ngày gần nhất, theo trạng thái)</h3></div>
              <div class="card-body"><canvas id="ordersByDay" style="height:420px"></canvas></div>
            </div>
          </div>
          <!-- Chart 2 -->
          <div class="col-lg-4">
            <div class="card">
              <div class="card-header"><h3 class="card-title">Top 10 thành phố (30 ngày)</h3></div>
              <div class="card-body"><canvas id="topCities" style="height:420px"></canvas></div>
            </div>
          </div>
        </div>

      </div>
    </section>
  </div>

  <aside class="control-sidebar control-sidebar-dark"></aside>
</div>

<!-- jQuery + Bootstrap + AdminLTE -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
  // Chart 1: Orders per day (stacked bars by status)
  var labels   = <?= $labelsJson ?>;
  var datasets = <?= $datasetsJson ?>;
  var ctx1 = document.getElementById('ordersByDay').getContext('2d');
  new Chart(ctx1, {
    type: 'bar',
    data: { labels: labels, datasets: datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'top' } },
      scales: {
        x: { stacked: true, grid: { display:false } },
        y: { stacked: true, beginAtZero: true, ticks: { precision:0 } }
      }
    }
  });

  // Chart 2: Top Cities (last 30 days)
  var cityLabels = <?= $cityLabelsJson ?>;
  var cityCounts = <?= $cityCountsJson ?>;
  var ctx2 = document.getElementById('topCities').getContext('2d');
  new Chart(ctx2, {
    type: 'horizontalBar' in Chart.controllers ? 'horizontalBar' : 'bar',
    data: {
      labels: cityLabels,
      datasets: [{
        label: 'Số đơn',
        data: cityCounts,
        backgroundColor: '#10b981'
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display:false } },
      scales: {
        x: { beginAtZero: true, ticks: { precision:0 } },
        y: { grid: { display:false } }
      }
    }
  });
})();
</script>
</body>
</html>
