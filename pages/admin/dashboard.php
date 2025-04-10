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
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Artwork Auth | Yönetim Paneli</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
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
                    <a href="#" class="d-block"><?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin'; ?></a>
                </div>
            </div>

            <?php include 'sidebar_menu.php'; ?>
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Yönetim Paneli</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
                            <li class="breadcrumb-item active">Yönetim Paneli</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
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
            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <footer class="main-footer">
        <strong>Copyright &copy; 2023 <a href="#">Artwork Auth</a>.</strong>
        Tüm hakları saklıdır.
        <div class="float-right d-none d-sm-inline-block">
            <b>Versiyon</b> 1.0.0
        </div>
    </footer>
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- Custom JS -->
<script src="../../assets/js/script.js"></script>
</body>
</html> 