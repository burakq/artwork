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

// Kullanıcı silme işlemi
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // Kullanıcı kendisini silemez
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['message'] = "Kendi hesabınızı silemezsiniz.";
        $_SESSION['message_type'] = "error";
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delete_stmt->bind_param("i", $user_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['message'] = "Kullanıcı başarıyla silindi.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Kullanıcı silinirken bir hata oluştu: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
        
        $delete_stmt->close();
    }
    
    // Sayfayı yeniden yükle
    redirect('users.php');
}

// Kullanıcıları getir
$query = "SELECT id, name, email, is_admin, created_at, updated_at FROM users ORDER BY id DESC";
$result = $conn->query($query);

// Toplam kullanıcı sayısı
$total_users = $result->num_rows;
$total_admins = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['is_admin'] == 1) {
            $total_admins++;
        }
    }
}

// Sorguyu tekrar çalıştırmalıyız çünkü fetch_assoc ile tüm sonuçları taradık
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Artwork Auth | Kullanıcı Yönetimi</title>
    
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
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Kullanıcı Yönetimi</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
                            <li class="breadcrumb-item active">Kullanıcı Yönetimi</li>
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
                
                <!-- İstatistikler -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?php echo $total_users; ?></h3>
                                <p>Toplam Kullanıcı</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?php echo $total_admins; ?></h3>
                                <p>Yönetici Sayısı</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                        </div>
                    </div>
                    <!-- ./col -->
                </div>
                <!-- /.row -->
                
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Kullanıcı Listesi</h3>
                                <div class="card-tools">
                                    <a href="user_add.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Yeni Kullanıcı Ekle
                                    </a>
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="usersTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Ad Soyad</th>
                                            <th>E-posta</th>
                                            <th>Yetki</th>
                                            <th>Kayıt Tarihi</th>
                                            <th>Son Güncelleme</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo '<tr>';
                                                echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                                                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                                                echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                                                echo '<td>' . ($row['is_admin'] ? '<span class="badge badge-success">Yönetici</span>' : '<span class="badge badge-info">Kullanıcı</span>') . '</td>';
                                                echo '<td>' . htmlspecialchars(date('d.m.Y H:i', strtotime($row['created_at']))) . '</td>';
                                                echo '<td>' . htmlspecialchars(date('d.m.Y H:i', strtotime($row['updated_at']))) . '</td>';
                                                echo '<td>';
                                                echo '<a href="user_edit.php?id=' . $row['id'] . '" class="btn btn-sm btn-info mr-1"><i class="fas fa-pencil-alt"></i> Düzenle</a>';
                                                
                                                // Kullanıcı kendisini silemesin
                                                if ($row['id'] != $_SESSION['user_id']) {
                                                    echo '<a href="javascript:void(0)" onclick="confirmDelete(' . $row['id'] . ')" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Sil</a>';
                                                }
                                                
                                                echo '</td>';
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
            <b>Version</b> 1.0.0
        </div>
        <strong>Copyright &copy; <?php echo date('Y'); ?> <a href="../../index.php">Artwork Auth</a>.</strong> Tüm hakları saklıdır.
    </footer>
</div>
<!-- ./wrapper -->

<!-- Silme onay modalı -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Onay</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Kapat">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Bu kullanıcıyı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                <a href="#" id="deleteButton" class="btn btn-danger">Evet, Sil</a>
            </div>
        </div>
    </div>
</div>

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
    $('#usersTable').DataTable({
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

// Silme onayı
function confirmDelete(id) {
    document.getElementById('deleteButton').href = 'users.php?delete=' + id;
    $('#deleteModal').modal('show');
}
</script>
</body>
</html>

<?php
// Bağlantıyı kapat
$conn->close();
?> 