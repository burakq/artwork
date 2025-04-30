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

// İstatistikleri getir
$stats = [
    'users' => 0,
    'artworks' => 0,
    'customers' => 0,
    'orders' => 0,
    'verification_logs' => 0
];

// Kullanıcı sayısı
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result && $row = $result->fetch_assoc()) {
    $stats['users'] = $row['count'];
}

// Sanat eserleri sayısı
$result = $conn->query("SELECT COUNT(*) as count FROM artworks");
if ($result && $row = $result->fetch_assoc()) {
    $stats['artworks'] = $row['count'];
}

// Müşteri sayısı
$result = $conn->query("SELECT COUNT(*) as count FROM customers");
if ($result && $row = $result->fetch_assoc()) {
    $stats['customers'] = $row['count'];
}

// Sipariş sayısı
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
if ($result && $row = $result->fetch_assoc()) {
    $stats['orders'] = $row['count'];
}

// Doğrulama log sayısı
$result = $conn->query("SELECT COUNT(*) as count FROM verification_logs");
if ($result && $row = $result->fetch_assoc()) {
    $stats['verification_logs'] = $row['count'];
}

// Son eklenen eserler
$latest_artworks = [];
$sql = "SELECT * FROM artworks ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $latest_artworks[] = $row;
    }
}

// Son doğrulama logları
$latest_logs = [];
$sql = "SELECT vl.*, a.title, a.artist_name FROM verification_logs vl 
        LEFT JOIN artworks a ON vl.artwork_id = a.id 
        ORDER BY vl.created_at DESC LIMIT 5";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $latest_logs[] = $row;
    }
}

// Grafik verilerini getir
$chart_data = [];
$chart_sql = "SELECT 
    DATE(created_at) as date,
    COUNT(*) as count
    FROM verification_logs 
    WHERE DATE(created_at) BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()
    GROUP BY DATE(created_at)
    ORDER BY date ASC";
$result = $conn->query($chart_sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $chart_data[] = $row;
    }
}

// Bağlantıyı kapat
$conn->close();

// Sayfa başlığı ve breadcrumb
$page_title = 'Yönetim Paneli';
$breadcrumb = '<li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
               <li class="breadcrumb-item active">Yönetim Paneli</li>';

// DataTables ve Chart.js için ek JavaScript
$additional_js = '
<!-- jQuery -->
<script src="../../plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- AdminLTE App -->
<script src="../../dist/js/adminlte.min.js"></script>
<script>
// jQuery yüklendikten sonra çalışacak
jQuery(document).ready(function($) {
    // DataTable ayarları
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

    // Grafik oluşturma
    var ctx = document.getElementById("nfcChart").getContext("2d");
    var chartData = ' . json_encode($chart_data) . ';
    
    new Chart(ctx, {
        type: "line",
        data: {
            labels: chartData.map(item => item.date),
            datasets: [{
                label: "Günlük Doğrulama Sayısı",
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
});
</script>';

// Header'ı dahil et
include 'templates/header.php';
?>


    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- Small boxes (Stat box) -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?php echo $stats['users']; ?></h3>
                            <p>Kullanıcılar</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <a href="users.php" class="small-box-footer">Detaylar <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <!-- ./col -->
                <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?php echo $stats['artworks']; ?></h3>
                            <p>Sanat Eserleri</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-paint-brush"></i>
                        </div>
                        <a href="artworks.php" class="small-box-footer">Detaylar <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <!-- ./col -->
                <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?php echo $stats['customers']; ?></h3>
                            <p>Müşteriler</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <a href="customers.php" class="small-box-footer">Detaylar <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <!-- ./col -->
                <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?php echo $stats['orders']; ?></h3>
                            <p>Siparişler</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <a href="orders.php" class="small-box-footer">Detaylar <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <!-- ./col -->
            </div>
            <!-- /.row -->
        </div>
    </div>
    </section>
</div>

<?php
// Footer'ı dahil et
include 'templates/footer.php';
?> 