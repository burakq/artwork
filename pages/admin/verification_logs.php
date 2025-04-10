<?php
session_start();
require_once '../../includes/functions.php';

// Giriş yapılmış mı kontrol et
if (!isLoggedIn()) {
    redirect('../../login.php');
}

// Admin yetkisi kontrolü
if (!isAdmin()) {
    redirect('../../index.php');
}

// Veritabanı bağlantısı
$conn = connectDB();

// İstatistikler için verileri getir
$stats = [];

// Toplam doğrulama sayısı
$total_result = $conn->query("SELECT COUNT(*) as total FROM verification_logs WHERE deleted_at IS NULL");
$stats['total'] = ($total_result && $row = $total_result->fetch_assoc()) ? $row['total'] : 0;

// Eser durumlarına göre doğrulama sayıları
$status_result = $conn->query("
    SELECT a.status, COUNT(vl.id) as count 
    FROM verification_logs vl
    LEFT JOIN artworks a ON vl.artwork_id = a.id
    WHERE vl.deleted_at IS NULL
    GROUP BY a.status
");

$stats['by_status'] = [
    'original' => 0,
    'for_sale' => 0,
    'sold' => 0,
    'fake' => 0,
    'unknown' => 0
];

if ($status_result) {
    while ($row = $status_result->fetch_assoc()) {
        if ($row['status'] !== null) {
            $stats['by_status'][$row['status']] = (int)$row['count'];
        } else {
            $stats['by_status']['unknown'] += (int)$row['count'];
        }
    }
}

// Son 7 günlük doğrulama sayıları
$daily_result = $conn->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM verification_logs 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND deleted_at IS NULL
    GROUP BY DATE(created_at)
    ORDER BY date
");

$stats['daily'] = [];
if ($daily_result) {
    while ($row = $daily_result->fetch_assoc()) {
        $stats['daily'][$row['date']] = (int)$row['count'];
    }
}

// Doğrulama kayıtlarını getir
$logs = [];
$sql = "SELECT vl.*, a.title as artwork_title, a.artist_name, a.status
        FROM verification_logs vl
        LEFT JOIN artworks a ON vl.artwork_id = a.id
        WHERE vl.deleted_at IS NULL
        ORDER BY vl.created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
} else {
    // Sorgu hatası durumunda hata mesajını loga yaz
    error_log("Verification logs sorgu hatası: " . $conn->error);
}

// Bağlantıyı kapat
$conn->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Artwork Auth | Doğrulama Kayıtları</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css">
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Custom CSS -->
    <link href="../../assets/css/style.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="dashboard.php" class="nav-link">Ana Sayfa</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="../../logout.php" role="button">
                    <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                </a>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="dashboard.php" class="brand-link">
            <span class="brand-text font-weight-light">Artwork Auth</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="info">
                    <a href="#" class="d-block"><?php echo $_SESSION['user_name']; ?></a>
                </div>
            </div>

            <?php include 'sidebar_menu.php'; ?>
        </div>
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Doğrulama Kayıtları</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
                            <li class="breadcrumb-item active">Doğrulama Kayıtları</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- İstatistik Kutuları -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?php echo $stats['total']; ?></h3>
                                <p>Toplam Doğrulama</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-search-check"></i>
                            </div>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?php echo $stats['by_status']['original'] + $stats['by_status']['for_sale'] + $stats['by_status']['sold']; ?></h3>
                                <p>Orijinal Eser Doğrulaması</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3><?php echo $stats['by_status']['fake']; ?></h3>
                                <p>Sahte Eser Doğrulaması</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?php echo $stats['by_status']['unknown']; ?></h3>
                                <p>Bilinmeyen Eser Doğrulaması</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-question-circle"></i>
                            </div>
                        </div>
                    </div>
                    <!-- ./col -->
                </div>
                <!-- /.row -->

                <!-- Grafikler -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Doğrulama Durumları</h3>
                            </div>
                            <div class="card-body">
                                <div style="height: 300px;">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Son 7 Gün Doğrulama İstatistikleri</h3>
                            </div>
                            <div class="card-body">
                                <div style="height: 300px;">
                                    <canvas id="dailyChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.row -->
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Eser Doğrulama Kayıtları</h3>
                                <div class="card-tools">
                                    <div class="alert alert-info p-1 mb-0">
                                        <small><i class="fas fa-info-circle"></i> Doğrulama kayıtları güvenlik sebebiyle silinemez</small>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="dataTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Eser</th>
                                            <th>Durum</th>
                                            <th>Doğrulama Kodu</th>
                                            <th>IP Adresi</th>
                                            <th>Cihaz</th>
                                            <th>Tarayıcı</th>
                                            <th>Platform</th>
                                            <th>Tarih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo $log['id']; ?></td>
                                            <td>
                                                <?php if (!empty($log['artwork_title'])): ?>
                                                <a href="artwork_edit.php?id=<?php echo $log['artwork_id']; ?>">
                                                    <?php echo htmlspecialchars($log['artwork_title'] ?? ''); ?>
                                                </a>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($log['artist_name'] ?? ''); ?></small>
                                                <?php else: ?>
                                                <span class="text-muted">Eser silinmiş</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $status = $log['status'] ?? 'unknown';
                                                switch ($status) {
                                                    case 'original':
                                                        echo '<span class="badge badge-primary">Orijinal</span>';
                                                        break;
                                                    case 'for_sale':
                                                        echo '<span class="badge badge-success">Satışta</span>';
                                                        break;
                                                    case 'sold':
                                                        echo '<span class="badge badge-warning">Satıldı</span>';
                                                        break;
                                                    case 'fake':
                                                        echo '<span class="badge badge-danger">Sahte</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge badge-secondary">Bilinmiyor</span>';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['verification_code'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($log['device_type'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($log['browser'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($log['platform'] ?? ''); ?></td>
                                            <td><?php echo ($log['created_at'] ? date('d.m.Y H:i', strtotime($log['created_at'])) : ''); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                    <!-- /.col -->
                </div>
                <!-- /.row -->
            </div>
            <!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <footer class="main-footer">
        <div class="float-right d-none d-sm-block">
            <b>Version</b> 1.0.0
        </div>
        <strong>Copyright &copy; <?php echo date('Y'); ?> <a href="../../index.php">Artwork Auth</a>.</strong> Tüm hakları saklıdır.
    </footer>

</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables & Plugins -->
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- Custom JS -->
<script src="../../assets/js/script.js"></script>

<script>
$(document).ready(function() {
    // DataTable'ı başlat
    // Global olarak zaten başlatılmış olabilir, bu yüzden önce kontrol edelim
    if ($.fn.dataTable.isDataTable('#dataTable')) {
        // Zaten başlatılmış, mevcut instance'ı alalım ve ayarlarını güncelleyelim
        var table = $('#dataTable').DataTable();
        table.order([8, 'desc']).draw(); // Tarih alanına göre azalan sıralama
    } else {
        // Yeni başlat
        $('#dataTable').DataTable({
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.12.1/i18n/tr.json"
            },
            "order": [[8, 'desc']] // Tarih alanına göre azalan sıralama
        });
    }
    
    // Treeview menü
    $('.nav-sidebar .nav-item:has(.nav-treeview)').each(function() {
        $(this).find('> .nav-link').on('click', function(e) {
            e.preventDefault();
            var parent = $(this).parent();
            var treeview = parent.find('.nav-treeview').first();
            
            parent.toggleClass('menu-open');
            if (parent.hasClass('menu-open')) {
                treeview.slideDown();
            } else {
                treeview.slideUp();
            }
        });
        
        // İlk açılışta, aktif menüleri aç
        if ($(this).find('.nav-link.active').length || $(this).find('.nav-treeview .nav-link.active').length) {
            $(this).addClass('menu-open');
            $(this).find('.nav-treeview').first().slideDown();
        } else {
            $(this).find('.nav-treeview').first().slideUp();
        }
    });
    
    // Durum Grafiği
    var statusCtx = document.getElementById('statusChart').getContext('2d');
    var statusChart = new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: ['Orijinal', 'Satışta', 'Satıldı', 'Sahte', 'Bilinmiyor'],
            datasets: [{
                data: [
                    <?php echo $stats['by_status']['original']; ?>,
                    <?php echo $stats['by_status']['for_sale']; ?>,
                    <?php echo $stats['by_status']['sold']; ?>,
                    <?php echo $stats['by_status']['fake']; ?>,
                    <?php echo $stats['by_status']['unknown']; ?>
                ],
                backgroundColor: [
                    '#007bff',
                    '#28a745',
                    '#ffc107',
                    '#dc3545',
                    '#6c757d'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 2,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.label || '';
                            var value = context.raw || 0;
                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                            var percentage = Math.round((value / total) * 100);
                            return label + ': ' + value + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
    
    // Günlük Doğrulama Grafiği
    var dailyCtx = document.getElementById('dailyChart').getContext('2d');
    var dailyChart = new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                // Son 7 günün tarihlerini oluştur
                $dates = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    $dates[] = $date;
                    echo "'" . date('d.m.Y', strtotime($date)) . "'" . ($i > 0 ? ',' : '');
                }
                ?>
            ],
            datasets: [{
                label: 'Doğrulama Sayısı',
                data: [
                    <?php 
                    // Her gün için doğrulama sayısını al
                    $counts = [];
                    foreach ($dates as $i => $date) {
                        $count = isset($stats['daily'][$date]) ? $stats['daily'][$date] : 0;
                        $counts[] = $count;
                        echo $count . ($i < count($dates) - 1 ? ',' : '');
                    }
                    ?>
                ],
                backgroundColor: 'rgba(60, 141, 188, 0.8)',
                borderColor: 'rgba(60, 141, 188, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 2,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
});
</script>
</body>
</html> 