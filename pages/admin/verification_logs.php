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

// Bugünkü doğrulama sayısı
$today_result = $conn->query("SELECT COUNT(*) as today FROM verification_logs WHERE DATE(created_at) = CURDATE() AND deleted_at IS NULL");
$stats['today'] = ($today_result && $row = $today_result->fetch_assoc()) ? $row['today'] : 0;

// Benzersiz IP sayısı
$unique_ip_result = $conn->query("SELECT COUNT(DISTINCT ip_address) as unique_ips FROM verification_logs WHERE deleted_at IS NULL");
$stats['unique_ips'] = ($unique_ip_result && $row = $unique_ip_result->fetch_assoc()) ? $row['unique_ips'] : 0;

// Eser durumlarına göre doğrulama sayıları
$status_result = $conn->query("
    SELECT a.status, COUNT(vl.id) as count 
    FROM verification_logs vl
    LEFT JOIN artworks a ON vl.artwork_id = a.id
    WHERE vl.deleted_at IS NULL
    GROUP BY a.status
");

$stats['by_status'] = [
    'satista' => 0,
    'satildi' => 0,
    'arsiv' => 0,
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

// Platforma göre doğrulama sayıları
$platform_result = $conn->query("
    SELECT platform, COUNT(*) as count
    FROM verification_logs
    WHERE deleted_at IS NULL AND platform IS NOT NULL
    GROUP BY platform
    ORDER BY count DESC
");

$stats['by_platform'] = [];
if ($platform_result) {
    while ($row = $platform_result->fetch_assoc()) {
        $platform = $row['platform'] ?: 'Bilinmiyor';
        $stats['by_platform'][$platform] = (int)$row['count'];
    }
}

// Tarayıcıya göre doğrulama sayıları
$browser_result = $conn->query("
    SELECT browser, COUNT(*) as count
    FROM verification_logs
    WHERE deleted_at IS NULL AND browser IS NOT NULL
    GROUP BY browser
    ORDER BY count DESC
    LIMIT 5
");

$stats['by_browser'] = [];
if ($browser_result) {
    while ($row = $browser_result->fetch_assoc()) {
        $browser = $row['browser'] ?: 'Bilinmiyor';
        $stats['by_browser'][$browser] = (int)$row['count'];
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

// En çok doğrulanan 5 eser
$top_artworks_result = $conn->query("
    SELECT a.id, a.title, a.artist_name, COUNT(vl.id) as verify_count
    FROM verification_logs vl
    JOIN artworks a ON vl.artwork_id = a.id
    WHERE vl.deleted_at IS NULL
    GROUP BY a.id
    ORDER BY verify_count DESC
    LIMIT 5
");

$stats['top_artworks'] = [];
if ($top_artworks_result) {
    while ($row = $top_artworks_result->fetch_assoc()) {
        $stats['top_artworks'][] = $row;
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

// Sayfa başlığı ve diğer bilgileri ayarla
$page_title = "Doğrulama Kayıtları";
$breadcrumb = '<li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
               <li class="breadcrumb-item active">Doğrulama Kayıtları</li>';

// DataTables ve Chart.js için ek CSS
$additional_css = '
<link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css">
<style>
    .log-item {transition: background-color 0.3s;}
    .log-item:hover {background-color: #f8f9fa;}
    .small-box .icon i {font-size: 50px; top: 10px;}
    .stats-title {font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px;}
    .stats-value {font-size: 2rem; font-weight: 600;}
    .browser-icon {font-size: 16px; margin-right: 5px;}
</style>';

// DataTables ve Chart.js için ek JS
$additional_js = '
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    // DataTables
    $("#verification-logs-table").DataTable({
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json"
        },
        "order": [[0, "desc"]]
    });

    // Chart.js - Daily verifications
    var dailyData = '.json_encode($stats['daily']).';
    var labels = Object.keys(dailyData);
    var data = Object.values(dailyData);
    
    new Chart(document.getElementById("daily-chart"), {
        type: "line",
        data: {
            labels: labels,
            datasets: [{
                label: "Günlük Doğrulama Sayısı",
                data: data,
                borderColor: "rgb(75, 192, 192)",
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: "top",
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + " doğrulama";
                        }
                    }
                }
            }
        }
    });
    
    // Status chart
    var statusData = [
        {
            label: "Satışta",
            value: '.$stats['by_status']['satista'].',
            color: "#17a2b8"
        },
        {
            label: "Satıldı",
            value: '.$stats['by_status']['satildi'].',
            color: "#ffc107"
        },
        {
            label: "Arşiv",
            value: '.$stats['by_status']['arsiv'].',
            color: "#6c757d"
        },
        {
            label: "Sahte",
            value: '.$stats['by_status']['fake'].',
            color: "#dc3545"
        },
        {
            label: "Bilinmiyor",
            value: '.$stats['by_status']['unknown'].',
            color: "#28a745"
        }
    ];
    
    new Chart(document.getElementById("status-chart"), {
        type: "doughnut",
        data: {
            labels: statusData.map(item => item.label),
            datasets: [{
                data: statusData.map(item => item.value),
                backgroundColor: statusData.map(item => item.color),
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: "right",
                }
            }
        }
    });
    
    // Platform chart
    var platformData = '.json_encode($stats['by_platform']).';
    var platformLabels = Object.keys(platformData);
    var platformValues = Object.values(platformData);
    
    new Chart(document.getElementById("platform-chart"), {
        type: "bar",
        data: {
            labels: platformLabels,
            datasets: [{
                label: "Doğrulama Sayısı",
                data: platformValues,
                backgroundColor: [
                    "rgba(54, 162, 235, 0.7)",
                    "rgba(255, 99, 132, 0.7)",
                    "rgba(255, 206, 86, 0.7)",
                    "rgba(75, 192, 192, 0.7)",
                    "rgba(153, 102, 255, 0.7)"
                ],
                borderColor: [
                    "rgba(54, 162, 235, 1)",
                    "rgba(255, 99, 132, 1)",
                    "rgba(255, 206, 86, 1)",
                    "rgba(75, 192, 192, 1)",
                    "rgba(153, 102, 255, 1)"
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            indexAxis: "y",
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Browser chart
    var browserData = '.json_encode($stats['by_browser']).';
    var browserLabels = Object.keys(browserData);
    var browserValues = Object.values(browserData);
    
    new Chart(document.getElementById("browser-chart"), {
        type: "pie",
        data: {
            labels: browserLabels,
            datasets: [{
                data: browserValues,
                backgroundColor: [
                    "#FF6384",
                    "#36A2EB",
                    "#FFCE56",
                    "#4BC0C0",
                    "#9966FF"
                ],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: "right",
                }
            }
        }
    });
});
</script>';

// Template header'ı include et
include 'templates/header.php';
?>

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
                <i class="fas fa-search"></i>
            </div>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?php echo $stats['today']; ?></h3>
                <p>Bugünkü Doğrulama</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?php echo $stats['unique_ips']; ?></h3>
                <p>Benzersiz IP Adresi</p>
            </div>
            <div class="icon">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?php echo count($logs); ?></h3>
                <p>Kayıtlı Doğrulama</p>
            </div>
            <div class="icon">
                <i class="fas fa-database"></i>
            </div>
        </div>
    </div>
    <!-- ./col -->
</div><!-- /.row -->

<!-- Grafik Kutuları -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Son 7 Gün Doğrulama Aktivitesi</h3>
            </div>
            <div class="card-body">
                <canvas id="daily-chart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Eser Durumlarına Göre Doğrulamalar</h3>
            </div>
            <div class="card-body">
                <canvas id="status-chart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Platform Dağılımı</h3>
            </div>
            <div class="card-body">
                <canvas id="platform-chart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tarayıcı Dağılımı</h3>
            </div>
            <div class="card-body">
                <canvas id="browser-chart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- En Çok Doğrulanan Eserler -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">En Çok Doğrulanan Eserler</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 10px">#</th>
                        <th>Eser Adı</th>
                        <th>Sanatçı</th>
                        <th style="width: 20%">Doğrulama Sayısı</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($stats['top_artworks'] as $index => $artwork): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><a href="artwork_view.php?id=<?php echo $artwork['id']; ?>"><?php echo htmlspecialchars($artwork['title']); ?></a></td>
                        <td><?php echo htmlspecialchars($artwork['artist_name']); ?></td>
                        <td>
                            <div class="progress progress-xs">
                                <div class="progress-bar bg-success" style="width: <?php echo min(100, ($artwork['verify_count'] / max(1, $stats['total'])) * 100); ?>%"></div>
                            </div>
                            <span class="badge bg-success"><?php echo $artwork['verify_count']; ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Doğrulama Kayıtları Tablosu -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">Tüm Doğrulama Kayıtları</h3>
    </div>
    <div class="card-body">
        <table id="verification-logs-table" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tarih</th>
                    <th>Eser</th>
                    <th>IP Adresi</th>
                    <th>Cihaz / Tarayıcı</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr class="log-item">
                    <td><?php echo htmlspecialchars($log['id']); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?></td>
                    <td>
                        <?php if ($log['artwork_id'] && $log['artwork_title']): ?>
                            <a href="artwork_view.php?id=<?php echo $log['artwork_id']; ?>">
                                <strong><?php echo htmlspecialchars($log['artwork_title']); ?></strong>
                            </a>
                            <br><small><?php echo htmlspecialchars($log['artist_name']); ?></small>
                        <?php else: ?>
                            <span class="text-muted">Eser bulunamadı (ID: <?php echo htmlspecialchars($log['artwork_id'] ?? 'N/A'); ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($log['ip_address']); ?>
                        <?php if (!empty($log['ip_address'])): ?>
                            <a href="https://whatismyipaddress.com/ip/<?php echo htmlspecialchars($log['ip_address']); ?>" target="_blank" class="ml-1" title="IP Bilgisi">
                                <i class="fas fa-info-circle"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($log['device_type'])): ?>
                            <span class="badge badge-primary"><?php echo htmlspecialchars($log['device_type']); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($log['platform'])): ?>
                            <span class="badge badge-secondary"><?php echo htmlspecialchars($log['platform']); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($log['browser'])): ?>
                            <span class="badge badge-info">
                                <?php 
                                $browser_icon = 'fas fa-globe';
                                if (stripos($log['browser'], 'chrome') !== false) $browser_icon = 'fab fa-chrome';
                                elseif (stripos($log['browser'], 'firefox') !== false) $browser_icon = 'fab fa-firefox';
                                elseif (stripos($log['browser'], 'safari') !== false) $browser_icon = 'fab fa-safari';
                                elseif (stripos($log['browser'], 'edge') !== false) $browser_icon = 'fab fa-edge';
                                elseif (stripos($log['browser'], 'opera') !== false) $browser_icon = 'fab fa-opera';
                                elseif (stripos($log['browser'], 'ie') !== false) $browser_icon = 'fab fa-internet-explorer';
                                ?>
                                <i class="<?php echo $browser_icon; ?> browser-icon"></i>
                                <?php echo htmlspecialchars($log['browser']); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($log['artwork_id'] && $log['status']): ?>
                            <?php if ($log['status'] == 'satista'): ?>
                                <span class="badge badge-info">Satışta</span>
                            <?php elseif ($log['status'] == 'satildi'): ?>
                                <span class="badge badge-warning">Satıldı</span>
                            <?php elseif ($log['status'] == 'arsiv'): ?>
                                <span class="badge badge-secondary">Arşivde</span>
                            <?php elseif ($log['status'] == 'fake'): ?>
                                <span class="badge badge-danger">Sahte</span>
                            <?php else: ?>
                                <span class="badge badge-success">Bilinmiyor</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-secondary">Belirsiz</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- /.card-body -->
</div>
<!-- /.card -->

<?php 
// Footer template'i include et
include 'templates/footer.php'; 
?> 
 
 
 
 