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
                }
            }
        }
    });
    
    // Status chart
    var statusData = [
        {
            label: "Orijinal",
            value: '.$stats['by_status']['original'].',
            color: "#28a745"
        },
        {
            label: "Satılık",
            value: '.$stats['by_status']['for_sale'].',
            color: "#17a2b8"
        },
        {
            label: "Satıldı",
            value: '.$stats['by_status']['sold'].',
            color: "#ffc107"
        },
        {
            label: "Sahte",
            value: '.$stats['by_status']['fake'].',
            color: "#dc3545"
        },
        {
            label: "Bilinmiyor",
            value: '.$stats['by_status']['unknown'].',
            color: "#6c757d"
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

<!-- Doğrulama Kayıtları Tablosu -->
<div class="card">
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
                    <th>Tarayıcı</th>
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
                            <strong><?php echo htmlspecialchars($log['artwork_title']); ?></strong>
                            <br><small><?php echo htmlspecialchars($log['artist_name']); ?></small>
                        <?php else: ?>
                            <span class="text-muted">Eser bulunamadı (ID: <?php echo htmlspecialchars($log['artwork_id'] ?? 'N/A'); ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                    <td><?php echo htmlspecialchars($log['user_agent']); ?></td>
                    <td>
                        <?php if ($log['artwork_id'] && $log['status']): ?>
                            <?php if ($log['status'] == 'original' || $log['status'] == 'for_sale' || $log['status'] == 'sold'): ?>
                                <span class="badge badge-success">Orijinal</span>
                            <?php elseif ($log['status'] == 'fake'): ?>
                                <span class="badge badge-danger">Sahte</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Bilinmiyor</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-warning">Belirsiz</span>
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
 
 
 
 