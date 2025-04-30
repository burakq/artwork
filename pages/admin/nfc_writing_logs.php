<?php
session_start();
require_once '../../includes/functions.php';

// Sayfa başlığını ayarla
$current_page = 'nfc_writing_logs.php';

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

// Filtreleme parametreleri
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// İstatistikleri hesapla
$total_writes = 0;
$successful_writes = 0;
$failed_writes = 0;
$error_rate = 0;

// Toplam yazma sayısı
$total_sql = "SELECT COUNT(*) as total FROM nfc_written_logs WHERE DATE(written_at) BETWEEN ? AND ?";
$stmt = $conn->prepare($total_sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$total_result = $stmt->get_result();
if ($total_result && $row = $total_result->fetch_assoc()) {
    $total_writes = $row['total'];
}

// Başarılı yazma sayısı
$success_sql = "SELECT COUNT(*) as success FROM nfc_written_logs WHERE DATE(written_at) BETWEEN ? AND ? AND written_at IS NOT NULL";
$stmt = $conn->prepare($success_sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$success_result = $stmt->get_result();
if ($success_result && $row = $success_result->fetch_assoc()) {
    $successful_writes = $row['success'];
}

// Başarısız yazma sayısı
$failed_sql = "SELECT COUNT(*) as failed FROM nfc_written_logs WHERE DATE(written_at) BETWEEN ? AND ? AND written_at IS NULL";
$stmt = $conn->prepare($failed_sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$failed_result = $stmt->get_result();
if ($failed_result && $row = $failed_result->fetch_assoc()) {
    $failed_writes = $row['failed'];
}

// Hata oranı
if ($total_writes > 0) {
    $error_rate = round(($failed_writes / $total_writes) * 100, 2);
}

// İstatistikleri getir
$stats = [];
$stats_sql = "SELECT 
    COUNT(*) as total_writings,
    COUNT(DISTINCT artwork_id) as total_artworks,
    DATE(written_at) as writing_date
    FROM nfc_written_logs 
    WHERE DATE(written_at) BETWEEN ? AND ?
    GROUP BY DATE(written_at)
    ORDER BY writing_date DESC";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$stats_result = $stmt->get_result();

if ($stats_result && $stats_result->num_rows > 0) {
    while ($row = $stats_result->fetch_assoc()) {
        $stats[] = $row;
    }
}

// Grafik verilerini getir
$chart_data = [];
$chart_sql = "SELECT 
    DATE(written_at) as date,
    COUNT(*) as count
    FROM nfc_written_logs 
    WHERE DATE(written_at) BETWEEN ? AND ?
    GROUP BY DATE(written_at)
    ORDER BY date ASC";
$stmt = $conn->prepare($chart_sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$chart_result = $stmt->get_result();

if ($chart_result && $chart_result->num_rows > 0) {
    while ($row = $chart_result->fetch_assoc()) {
        $chart_data[] = $row;
    }
}

// Logları getir
$logs = [];
$sql = "SELECT 
            n.id,
            n.artwork_id as nfc_id,
            CASE 
                WHEN n.written_at IS NOT NULL THEN 'success'
                ELSE 'failed'
            END as status,
            CASE 
                WHEN n.written_at IS NOT NULL THEN 'Başarılı'
                ELSE 'Başarısız'
            END as error_message,
            n.written_at as created_at,
            a.title as artwork_title,
            a.verification_code
        FROM nfc_written_logs n
        LEFT JOIN artworks a ON n.artwork_id = a.id
        WHERE DATE(n.written_at) BETWEEN ? AND ?
        ORDER BY n.written_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}

// Excel'e aktarma
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="nfc_writing_logs_' . date('Y-m-d') . '.xls"');
    
    echo "ID\tEser Adı\tDoğrulama Kodu\tYazılma Tarihi\n";
    foreach ($logs as $log) {
        echo $log['id'] . "\t" . 
             $log['artwork_title'] . "\t" . 
             $log['verification_code'] . "\t" . 
             date('d.m.Y H:i:s', strtotime($log['created_at'])) . "\n";
    }
    exit;
}

// Bağlantıyı kapat
$conn->close();

// Sayfa başlığı ve breadcrumb
$page_title = "NFC Yazma Logları";
$breadcrumb = '<li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
               <li class="breadcrumb-item active">NFC Yazma Logları</li>';

// DataTables ve Chart.js için ek CSS
$additional_css = '
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
<!-- Datepicker -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<style>
.stats-card {
    background: #fff;
    border-radius: 5px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.stats-number {
    font-size: 24px;
    font-weight: bold;
    color: #007bff;
}
.stats-label {
    color: #6c757d;
    font-size: 14px;
}
.chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 20px;
}
</style>';

// DataTables, Chart.js ve Datepicker için ek JavaScript
$additional_js = '
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
jQuery(document).ready(function($) {
    // DataTable ayarları
    if ($("#logsTable").length) {
        $("#logsTable").DataTable({
            "responsive": true,
            "autoWidth": false,
            "processing": true,
            "pageLength": 25,
            "order": [[0, "desc"]],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json"
            }
        });
    }

    // Grafik oluşturma
    var ctx = document.getElementById("nfcChart");
    if (ctx) {
        var chartData = ' . json_encode($chart_data) . ';
        
        new Chart(ctx, {
            type: "line",
            data: {
                labels: chartData.map(item => item.date),
                datasets: [{
                    label: "Günlük Yazma Sayısı",
                    data: chartData.map(item => item.count),
                    borderColor: "rgb(75, 192, 192)",
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
});
</script>';

// Header'ı dahil et
include 'templates/header.php';
?>

<!-- Filtreler -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Filtreler</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Başlangıç Tarihi</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Bitiş Tarihi</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">Filtrele</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Grafik -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">NFC Yazma İstatistikleri</h3>
            </div>
            <div class="card-body">
                <canvas id="nfcChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- İstatistikler -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo $total_writes; ?></h3>
                <p>Toplam Yazma</p>
            </div>
            <div class="icon">
                <i class="fas fa-pen"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?php echo $successful_writes; ?></h3>
                <p>Başarılı Yazma</p>
            </div>
            <div class="icon">
                <i class="fas fa-check"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?php echo $failed_writes; ?></h3>
                <p>Başarısız Yazma</p>
            </div>
            <div class="icon">
                <i class="fas fa-times"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?php echo $error_rate; ?>%</h3>
                <p>Hata Oranı</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
    </div>
</div>

<!-- Log Tablosu -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">NFC Yazma Logları</h3>
            </div>
            <div class="card-body">
                <table id="logsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>NFC ID</th>
                            <th>Durum</th>
                            <th>Hata Mesajı</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo $log['id']; ?></td>
                            <td><?php echo $log['nfc_id']; ?></td>
                            <td>
                                <?php if ($log['status'] == 'success'): ?>
                                    <span class="badge badge-success"><?php echo $log['status']; ?></span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><?php echo $log['status']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $log['error_message']; ?></td>
                            <td><?php echo $log['created_at']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Footer'ı dahil et
include 'templates/footer.php';
?> 