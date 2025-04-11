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

// ID kontrolü
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('customers.php');
}

$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$id) {
    redirect('customers.php');
}

// Veritabanı bağlantısı
$conn = connectDB();

// Müşteri bilgilerini getir
$customer = null;
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Müşteri bulunamadı
    redirect('customers.php');
}

$customer = $result->fetch_assoc();
$stmt->close();

// Müşterinin siparişlerini getir
$orders = [];
$stmt = $conn->prepare("SELECT * FROM orders WHERE customer_id = ? AND deleted_at IS NULL ORDER BY created_at DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Bağlantıyı kapat
$conn->close();

// Tür adları için yardımcı fonksiyon
function getCustomerTypeName($type) {
    switch ($type) {
        case 'individual':
            return 'Bireysel';
        case 'corporate':
            return 'Kurumsal';
        case 'gallery':
            return 'Galeri';
        case 'collector':
            return 'Koleksiyoner';
        default:
            return ucfirst($type);
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Artwork Auth | Müşteri Detayı</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
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
                        </ul>
                    </li>
                    
                    <li class="nav-item has-treeview menu-open">
                        <a href="#" class="nav-link active">
                            <i class="nav-icon fas fa-users"></i>
                            <p>
                                Müşteri Yönetimi
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="customers.php" class="nav-link active">
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
                    
                    <li class="nav-item has-treeview">
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
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Müşteri Detayı</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
                            <li class="breadcrumb-item"><a href="customers.php">Müşteriler</a></li>
                            <li class="breadcrumb-item active">Detay</li>
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
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo $customer['name']; ?></h3>
                                <div class="card-tools">
                                    <a href="customer_edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Düzenle
                                    </a>
                                    <a href="customers.php" class="btn btn-secondary btn-sm ml-1">
                                        <i class="fas fa-arrow-left"></i> Listeye Dön
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5>Müşteri Bilgileri</h5>
                                        <table class="table table-bordered">
                                            <tr>
                                                <th style="width: 30%">Müşteri ID</th>
                                                <td><?php echo $customer['id']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Ad Soyad / Kurum</th>
                                                <td><?php echo $customer['name']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Tür</th>
                                                <td>
                                                    <?php
                                                    $typeClass = 'secondary';
                                                    switch ($customer['type']) {
                                                        case 'individual':
                                                            $typeClass = 'primary';
                                                            break;
                                                        case 'corporate':
                                                            $typeClass = 'success';
                                                            break;
                                                        case 'gallery':
                                                            $typeClass = 'info';
                                                            break;
                                                        case 'collector':
                                                            $typeClass = 'warning';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $typeClass; ?>"><?php echo getCustomerTypeName($customer['type']); ?></span>
                                                </td>
                                            </tr>
                                            <?php if (!empty($customer['company_name'])): ?>
                                            <tr>
                                                <th>Firma Adı</th>
                                                <td><?php echo $customer['company_name']; ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (!empty($customer['tax_number'])): ?>
                                            <tr>
                                                <th>Vergi Numarası</th>
                                                <td><?php echo $customer['tax_number']; ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (!empty($customer['tax_office'])): ?>
                                            <tr>
                                                <th>Vergi Dairesi</th>
                                                <td><?php echo $customer['tax_office']; ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <th>E-posta</th>
                                                <td><?php echo $customer['email']; ?></td>
                                            </tr>
                                            <?php if (!empty($customer['phone'])): ?>
                                            <tr>
                                                <th>Telefon</th>
                                                <td><?php echo $customer['phone']; ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <th>Kayıt Tarihi</th>
                                                <td><?php echo date('d.m.Y H:i', strtotime($customer['created_at'])); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Son Güncelleme</th>
                                                <td><?php echo date('d.m.Y H:i', strtotime($customer['updated_at'])); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h5>İletişim ve Teslimat Bilgileri</h5>
                                        <?php if (!empty($customer['address'])): ?>
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h3 class="card-title">Adres</h3>
                                            </div>
                                            <div class="card-body">
                                                <p><?php echo nl2br($customer['address']); ?></p>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($customer['shipping_address'])): ?>
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h3 class="card-title">Teslimat Adresi</h3>
                                            </div>
                                            <div class="card-body">
                                                <p><?php echo nl2br($customer['shipping_address']); ?></p>
                                                <?php if (!empty($customer['shipping_city']) || !empty($customer['shipping_state']) || !empty($customer['shipping_postal_code']) || !empty($customer['shipping_country'])): ?>
                                                <p>
                                                    <?php 
                                                    $addressParts = [];
                                                    if (!empty($customer['shipping_city'])) $addressParts[] = $customer['shipping_city'];
                                                    if (!empty($customer['shipping_state'])) $addressParts[] = $customer['shipping_state'];
                                                    if (!empty($customer['shipping_postal_code'])) $addressParts[] = $customer['shipping_postal_code'];
                                                    if (!empty($customer['shipping_country'])) $addressParts[] = $customer['shipping_country'];
                                                    echo implode(', ', $addressParts);
                                                    ?>
                                                </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($customer['notes'])): ?>
                                        <div class="card">
                                            <div class="card-header">
                                                <h3 class="card-title">Notlar</h3>
                                            </div>
                                            <div class="card-body">
                                                <p><?php echo nl2br($customer['notes']); ?></p>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <h5>Siparişler</h5>
                                        <?php if (count($orders) > 0): ?>
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Sipariş Kodu</th>
                                                        <th>Toplam Tutar</th>
                                                        <th>Durum</th>
                                                        <th>Tarih</th>
                                                        <th>İşlemler</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($orders as $order): ?>
                                                    <tr>
                                                        <td><?php echo $order['id']; ?></td>
                                                        <td><?php echo $order['order_number']; ?></td>
                                                        <td><?php echo number_format($order['total_amount'], 2, ',', '.') . ' ₺'; ?></td>
                                                        <td>
                                                            <?php
                                                            $statusClass = 'secondary';
                                                            $statusText = 'Bilinmiyor';
                                                            
                                                            if (isset($order['status'])) {
                                                                switch ($order['status']) {
                                                                    case 'pending':
                                                                        $statusClass = 'warning';
                                                                        $statusText = 'Beklemede';
                                                                        break;
                                                                    case 'processing':
                                                                        $statusClass = 'info';
                                                                        $statusText = 'İşleniyor';
                                                                        break;
                                                                    case 'completed':
                                                                        $statusClass = 'success';
                                                                        $statusText = 'Tamamlandı';
                                                                        break;
                                                                    case 'cancelled':
                                                                        $statusClass = 'danger';
                                                                        $statusText = 'İptal Edildi';
                                                                        break;
                                                                    default:
                                                                        $statusText = ucfirst($order['status']);
                                                                }
                                                            }
                                                            ?>
                                                            <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                        </td>
                                                        <td><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></td>
                                                        <td>
                                                            <a href="order_view.php?id=<?php echo $order['id']; ?>" class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye"></i> Görüntüle
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php else: ?>
                                            <div class="alert alert-info">
                                                Bu müşteriye ait sipariş bulunamadı.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <a href="customer_edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-edit"></i> Düzenle
                                    </a>
                                    <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $customer['id']; ?>)" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Sil
                                    </a>
                                    <a href="customers.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Listeye Dön
                                    </a>
                                </div>
                            </div>
                        </div>
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
<!-- AdminLTE 3 App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
function confirmDelete(id) {
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    document.getElementById('confirmDeleteBtn').href = 'customers.php?delete=' + id;
    modal.show();
}
</script>
</body>
</html> 
 
 
 
 