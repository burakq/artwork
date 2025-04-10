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

// İşlem mesajı
$message = '';
$message_type = '';

// Müşteri silme işlemi
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    
    if ($id) {
        // Silme işlemini gerçekleştir (soft delete)
        $stmt = $conn->prepare("UPDATE customers SET deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Müşteri başarıyla silindi.";
            $message_type = "success";
        } else {
            $message = "Müşteri silinirken bir hata oluştu: " . $conn->error;
            $message_type = "danger";
        }
        
        $stmt->close();
    }
}

// Müşterileri getir
$customers = [];
$sql = "SELECT * FROM customers WHERE deleted_at IS NULL ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
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
    <title>Artwork Auth | Müşteriler</title>
    
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
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Müşteriler</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
                            <li class="breadcrumb-item active">Müşteriler</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Müşteri Listesi</h3>
                        <div class="card-tools">
                            <a href="customer_add.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Yeni Müşteri Ekle
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="customersTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>İsim</th>
                                    <th>Tür</th>
                                    <th>Firma Adı</th>
                                    <th>E-posta</th>
                                    <th>Telefon</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?php echo $customer['id']; ?></td>
                                    <td><?php echo $customer['name']; ?></td>
                                    <td>
                                        <?php
                                        $typeClass = 'secondary';
                                        switch ($customer['type']) {
                                            case 'individual':
                                                $typeClass = 'primary';
                                                $typeText = 'Bireysel';
                                                break;
                                            case 'corporate':
                                                $typeClass = 'success';
                                                $typeText = 'Kurumsal';
                                                break;
                                            case 'gallery':
                                                $typeClass = 'info';
                                                $typeText = 'Galeri';
                                                break;
                                            case 'collector':
                                                $typeClass = 'warning';
                                                $typeText = 'Koleksiyoner';
                                                break;
                                            default:
                                                $typeText = ucfirst($customer['type']);
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $typeClass; ?>"><?php echo $typeText; ?></span>
                                    </td>
                                    <td><?php echo !empty($customer['company_name']) ? $customer['company_name'] : '-'; ?></td>
                                    <td><?php echo $customer['email']; ?></td>
                                    <td><?php echo !empty($customer['phone']) ? $customer['phone'] : '-'; ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($customer['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="customer_view.php?id=<?php echo $customer['id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="customer_edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $customer['id']; ?>)" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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

<!-- Silme Onay Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Silme Onayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Bu müşteriyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Sil</a>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
<!-- AdminLTE 3 App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
$(document).ready(function () {
    $('#customersTable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.12.1/i18n/tr.json"
        }
    });
});

function confirmDelete(id) {
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    document.getElementById('confirmDeleteBtn').href = 'customers.php?delete=' + id;
    modal.show();
}
</script>
</body>
</html> 