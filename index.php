<?php
session_start();
require_once 'includes/functions.php';

// Oturum kontrolü
if (!isLoggedIn()) {
    redirect('login.php');
}

// Admin kullanıcı kontrolü - admin ise dashboard'a yönlendir
if (isAdmin()) {
    redirect('pages/admin/dashboard.php');
}

// Veritabanı bağlantısı
$conn = connectDB();

// İstatistikleri getir
$stats = [
    'artworks' => 0,
    'verification_logs' => 0
];

// Sanat eserleri sayısı
$result = $conn->query("SELECT COUNT(*) as count FROM artworks WHERE deleted_at IS NULL");
if ($result && $row = $result->fetch_assoc()) {
    $stats['artworks'] = $row['count'];
}

// Doğrulama log sayısı
$result = $conn->query("SELECT COUNT(*) as count FROM verification_logs");
if ($result && $row = $result->fetch_assoc()) {
    $stats['verification_logs'] = $row['count'];
}

// Son eklenen eserler
$latest_artworks = [];
$sql = "SELECT * FROM artworks WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $latest_artworks[] = $row;
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
    <title>Artwork Auth | Ana Sayfa</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
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
                <a href="index.php" class="nav-link">Ana Sayfa</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="logout.php" role="button">
                    <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                </a>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="index.php" class="brand-link">
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

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link active">
                            <i class="nav-icon fas fa-home"></i>
                            <p>Ana Sayfa</p>
                        </a>
                    </li>
                    
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-palette"></i>
                            <p>
                                Eser Yönetimi
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="pages/admin/artworks.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Eser Listesi</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="pages/admin/artwork_add.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Yeni Eser Ekle</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>
                                Müşteri Yönetimi
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="pages/admin/customers.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Müşteri Listesi</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="pages/admin/customer_add.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Yeni Müşteri Ekle</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-shopping-cart"></i>
                            <p>
                                Sipariş Yönetimi
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="pages/admin/orders.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Sipariş Listesi</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a href="pages/admin/verification_logs.php" class="nav-link">
                            <i class="nav-icon fas fa-clipboard-check"></i>
                            <p>Doğrulama Kayıtları</p>
                        </a>
                    </li>
                    
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a href="pages/admin/dashboard.php" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Yönetim Paneli</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>Çıkış Yap</p>
                        </a>
                    </li>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
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
                        <h1 class="m-0">Artwork Auth - Hoş Geldiniz</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item active">Ana Sayfa</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Artwork Auth Yönetim Sistemi</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <p>Sanat eserleri, müşteriler ve siparişleri yönetmek için sol menüyü kullanabilirsiniz.</p>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Hızlı Doğrulama</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <form>
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Doğrulama kodu girin">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary">Doğrula</button>
                                        </div>
                                    </div>
                                </form>
                                <p class="mt-3">Sanat eserinin doğrulama kodunu girerek orijinalliğini kontrol edebilirsiniz.</p>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
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
<!-- AdminLTE 3 App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html> 