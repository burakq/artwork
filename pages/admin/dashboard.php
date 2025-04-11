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

// Bağlantıyı kapat
$conn->close();

// Sayfa başlığı ve breadcrumb
$page_title = 'Yönetim Paneli';
$breadcrumb = '<li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
               <li class="breadcrumb-item active">Yönetim Paneli</li>';

// Header'ı dahil et
include 'templates/header.php';
?>

<!-- Small boxes (Stat box) -->
<div class="row">
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo isset($stats['artworks']) ? $stats['artworks'] : 0; ?></h3>
                <p>Sanat Eserleri</p>
            </div>
            <div class="icon">
                <i class="fas fa-palette"></i>
            </div>
            <a href="artworks.php" class="small-box-footer">Daha fazla bilgi <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?php echo isset($stats['customers']) ? $stats['customers'] : 0; ?></h3>
                <p>Müşteriler</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="customers.php" class="small-box-footer">Daha fazla bilgi <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?php echo isset($stats['orders']) ? $stats['orders'] : 0; ?></h3>
                <p>Siparişler</p>
            </div>
            <div class="icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <a href="orders.php" class="small-box-footer">Daha fazla bilgi <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?php echo isset($stats['verification_logs']) ? $stats['verification_logs'] : 0; ?></h3>
                <p>Doğrulama Kayıtları</p>
            </div>
            <div class="icon">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <a href="verification_logs.php" class="small-box-footer">Daha fazla bilgi <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- ./col -->
</div>
<!-- /.row -->

<?php
// Footer'ı dahil et
include 'templates/footer.php';
?> 