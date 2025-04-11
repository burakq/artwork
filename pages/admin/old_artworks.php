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

// Eski eserler tablosu yoksa oluştur
$tableCheck = $conn->query("SHOW TABLES LIKE 'old_artworks'");
if ($tableCheck->num_rows == 0) {
    $sql = "CREATE TABLE old_artworks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        artist_name VARCHAR(255) NOT NULL,
        year VARCHAR(50),
        dimensions VARCHAR(100),
        medium VARCHAR(255),
        image_url VARCHAR(512) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) !== TRUE) {
        $_SESSION['message'] = "Eski Eserler tablosu oluşturulamadı: " . $conn->error;
        $_SESSION['message_type'] = "error";
    }
}

// Tabloda hiç kayıt yoksa örnek verileri ekle
$countResult = $conn->query("SELECT COUNT(*) as count FROM old_artworks");
$countRow = $countResult->fetch_assoc();

if ($countRow['count'] == 0) {
    // Eski eserleri içeren dizi
    $old_artworks = [
        [
            'title' => 'İstanbul ve Kuşlar Silver',
            'artist_name' => 'Devrim Erbil',
            'year' => '2022',
            'dimensions' => '100x120 cm',
            'medium' => 'Tuval Üzerine Yağlı Boya',
            'image_url' => 'https://www.devrimerbil.com/wp-content/uploads/2024/11/Istanbul-ve-Kuslar-Silver-39-1.jpg',
            'description' => 'İstanbul ve Kuşlar serisinden Silver renklerde kompozisyon.'
        ],
        [
            'title' => 'Kadıköyün Kalbinden',
            'artist_name' => 'Devrim Erbil',
            'year' => '2025',
            'dimensions' => '130x130 cm',
            'medium' => 'Tuval Üzerine Yağlı Boya',
            'image_url' => 'https://www.devrimerbil.com/wp-content/uploads/2025/01/6-Kadikoyun-Kalbinden-24-Ocak-2025-6-130x130-cm-T.U.Y.B.-Eser-Kodu-DPQ6UUILNCKSS240120256.jpg',
            'description' => 'Kadıköy temalı İstanbul manzarası.'
        ],
        [
            'title' => 'İstanbul (ÇM10)',
            'artist_name' => 'Devrim Erbil',
            'year' => '2013',
            'dimensions' => '145x150 cm',
            'medium' => 'Tuval Üzerine Karışık Teknik',
            'image_url' => 'http://devrimerbil.com/wp-content/uploads/2020/01/İstanbul-ÇM10-2013Tuv.Üz.Kar_.Tek_.145x150-cm.Kod-11-Temmuz-2013-75-1.jpg',
            'description' => 'İstanbul serisi çalışması.'
        ],
        [
            'title' => 'İstanbula Bakış',
            'artist_name' => 'Devrim Erbil',
            'year' => '2013',
            'dimensions' => '180x130 cm',
            'medium' => 'Tuval Üzerine Karışık Teknik',
            'image_url' => 'http://devrimerbil.com/wp-content/uploads/2020/01/İstanbula-Bakış-K-Y.11-2013Tuv.Üz.Kar_.Tek_.180x130-cm.kod-11-Temmuz-2013-73-2-1.jpg',
            'description' => 'İstanbul panoraması temalı çalışma.'
        ]
    ];

    $success = 0;
    $errors = 0;

    foreach ($old_artworks as $artwork) {
        $stmt = $conn->prepare("INSERT INTO old_artworks (title, artist_name, year, dimensions, medium, image_url, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $artwork['title'], $artwork['artist_name'], $artwork['year'], $artwork['dimensions'], $artwork['medium'], $artwork['image_url'], $artwork['description']);
        
        if ($stmt->execute()) {
            $success++;
        } else {
            $errors++;
        }
        $stmt->close();
    }

    if ($success > 0) {
        $_SESSION['message'] = "$success eski eser başarıyla eklendi.";
        $_SESSION['message_type'] = "success";
    }

    if ($errors > 0) {
        $_SESSION['message'] = "$errors eser eklenirken hata oluştu.";
        $_SESSION['message_type'] = "error";
    }
}

// Tüm eski eserleri getir
$result = $conn->query("SELECT * FROM old_artworks ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Artwork Auth | Eski Eserler</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs4@1.11.5/css/dataTables.bootstrap4.min.css">
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Custom CSS -->
    <link href="../../assets/css/style.css" rel="stylesheet">
    <style>
        .artwork-thumbnail {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
        }
    </style>
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

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-palette"></i>
                            <p>
                                Eser Yönetimi
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="artworks.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Eser Listesi</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="artwork_add.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Yeni Eser Ekle</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="old_codes.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Eski Doğrulama Kodları</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="old_artworks.php" class="nav-link active">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Eski Eserler</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>
                                Müşteri Yönetimi
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="customers.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Müşteri Listesi</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="customer_add.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Yeni Müşteri Ekle</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-shopping-cart"></i>
                            <p>
                                Sipariş Yönetimi
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="orders.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Sipariş Listesi</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a href="verification_logs.php" class="nav-link">
                            <i class="nav-icon fas fa-clipboard-check"></i>
                            <p>Doğrulama Kayıtları</p>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-user-shield"></i>
                            <p>
                                Kullanıcı Yönetimi
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="users.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Kullanıcı Listesi</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="user_add.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Yeni Kullanıcı Ekle</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>Ayarlar</p>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="../../logout.php" class="nav-link">
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
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Eski Eserler</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
                            <li class="breadcrumb-item active">Eski Eserler</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo ($_SESSION['message_type'] == 'success') ? 'success' : 'danger'; ?> alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas <?php echo ($_SESSION['message_type'] == 'success') ? 'fa-check' : 'fa-ban'; ?>"></i> <?php echo ($_SESSION['message_type'] == 'success') ? 'Başarılı!' : 'Hata!'; ?></h5>
                        <?php 
                        echo $_SESSION['message']; 
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Eski Eserler Listesi</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="oldArtworksTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Görsel</th>
                                            <th>Eser Adı</th>
                                            <th>Sanatçı</th>
                                            <th>Yıl</th>
                                            <th>Boyut</th>
                                            <th>Teknik</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo '<tr>';
                                                echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                                                echo '<td><img src="../image_proxy.php?url=' . urlencode($row['image_url']) . '" alt="' . htmlspecialchars($row['title']) . '" class="artwork-thumbnail"></td>';
                                                echo '<td>' . htmlspecialchars($row['title']) . '</td>';
                                                echo '<td>' . htmlspecialchars($row['artist_name']) . '</td>';
                                                echo '<td>' . htmlspecialchars($row['year']) . '</td>';
                                                echo '<td>' . htmlspecialchars($row['dimensions']) . '</td>';
                                                echo '<td>' . htmlspecialchars($row['medium']) . '</td>';
                                                echo '</tr>';
                                            }
                                        }
                                        ?>
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
            <b>Versiyon</b> 1.0.0
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
<script src="https://cdn.jsdelivr.net/npm/datatables.net@1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs4@1.11.5/js/dataTables.bootstrap4.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- Custom JS -->
<script src="../../assets/js/script.js"></script>

<script>
$(document).ready(function() {
    // DataTable başlat
    $('#oldArtworksTable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/tr.json"
        }
    });
});
</script>
</body>
</html>

<?php
// Veritabanı bağlantısını kapat
$conn->close();
?> 
 
 
 
 