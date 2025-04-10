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

// İşlem mesajı
$message = '';
$message_type = '';

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

// Form işleme
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form verilerini al
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $tax_number = trim($_POST['tax_number'] ?? '');
    $tax_office = trim($_POST['tax_office'] ?? '');
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $shipping_city = trim($_POST['shipping_city'] ?? '');
    $shipping_state = trim($_POST['shipping_state'] ?? '');
    $shipping_postal_code = trim($_POST['shipping_postal_code'] ?? '');
    $shipping_country = trim($_POST['shipping_country'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // Validasyon
    if (empty($name)) {
        $errors[] = "Müşteri adı boş olamaz";
    }
    
    if (empty($type)) {
        $errors[] = "Müşteri türü seçilmelidir";
    }
    
    if (empty($email)) {
        $errors[] = "E-posta adresi boş olamaz";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi giriniz";
    }
    
    // E-posta adresi benzersiz mi kontrol et (kendi kaydı hariç)
    $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ? AND id != ? AND deleted_at IS NULL");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    $email_result = $stmt->get_result();
    if ($email_result->num_rows > 0) {
        $errors[] = "Bu e-posta adresi başka bir müşteri tarafından kullanılıyor";
    }
    $stmt->close();
    
    // Hata yoksa kaydet
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE customers SET 
            name = ?, 
            type = ?, 
            email = ?, 
            phone = ?, 
            address = ?, 
            company_name = ?, 
            tax_number = ?, 
            tax_office = ?, 
            shipping_address = ?, 
            shipping_city = ?, 
            shipping_state = ?, 
            shipping_postal_code = ?, 
            shipping_country = ?, 
            notes = ?,
            updated_at = NOW()
            WHERE id = ?");
        
        $stmt->bind_param("ssssssssssssssi", 
            $name, 
            $type, 
            $email, 
            $phone, 
            $address, 
            $company_name, 
            $tax_number, 
            $tax_office, 
            $shipping_address, 
            $shipping_city, 
            $shipping_state, 
            $shipping_postal_code, 
            $shipping_country, 
            $notes,
            $id
        );
        
        if ($stmt->execute()) {
            $success = true;
            
            // Güncellenmiş müşteri bilgilerini al
            $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $customer = $result->fetch_assoc();
        } else {
            $errors[] = "Güncelleme hatası: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Bağlantıyı kapat
$conn->close();

// Müşteri türleri
$customerTypes = [
    'individual' => 'Bireysel',
    'corporate' => 'Kurumsal',
    'gallery' => 'Galeri',
    'collector' => 'Koleksiyoner'
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Artwork Auth | Müşteri Düzenle</title>
    
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
                        <h1 class="m-0">Müşteri Düzenle</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
                            <li class="breadcrumb-item"><a href="customers.php">Müşteriler</a></li>
                            <li class="breadcrumb-item active">Düzenle</li>
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
                                <h3 class="card-title">Müşteri Bilgilerini Düzenle</h3>
                                <div class="card-tools">
                                    <a href="customer_view.php?id=<?php echo $customer['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i> Müşteriyi Görüntüle
                                    </a>
                                    <a href="customers.php" class="btn btn-secondary btn-sm ml-1">
                                        <i class="fas fa-arrow-left"></i> Listeye Dön
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if ($success): ?>
                                <div class="alert alert-success">
                                    Müşteri bilgileri başarıyla güncellendi.
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>

                                <form action="" method="post">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Ad Soyad / Kurum Adı <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($customer['name']); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="type">Müşteri Türü <span class="text-danger">*</span></label>
                                                <select class="form-control" id="type" name="type" required>
                                                    <?php foreach ($customerTypes as $value => $label): ?>
                                                    <option value="<?php echo $value; ?>" <?php echo ($customer['type'] === $value) ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="email">E-posta <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="phone">Telefon</label>
                                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="address">Adres</label>
                                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="company_name">Firma Adı</label>
                                                <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($customer['company_name'] ?? ''); ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="tax_number">Vergi Numarası</label>
                                                <input type="text" class="form-control" id="tax_number" name="tax_number" value="<?php echo htmlspecialchars($customer['tax_number'] ?? ''); ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="tax_office">Vergi Dairesi</label>
                                                <input type="text" class="form-control" id="tax_office" name="tax_office" value="<?php echo htmlspecialchars($customer['tax_office'] ?? ''); ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="shipping_address">Teslimat Adresi</label>
                                                <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3"><?php echo htmlspecialchars($customer['shipping_address'] ?? ''); ?></textarea>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="shipping_city">Şehir</label>
                                                        <input type="text" class="form-control" id="shipping_city" name="shipping_city" value="<?php echo htmlspecialchars($customer['shipping_city'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="shipping_state">İlçe</label>
                                                        <input type="text" class="form-control" id="shipping_state" name="shipping_state" value="<?php echo htmlspecialchars($customer['shipping_state'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="shipping_postal_code">Posta Kodu</label>
                                                        <input type="text" class="form-control" id="shipping_postal_code" name="shipping_postal_code" value="<?php echo htmlspecialchars($customer['shipping_postal_code'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="shipping_country">Ülke</label>
                                                        <input type="text" class="form-control" id="shipping_country" name="shipping_country" value="<?php echo htmlspecialchars($customer['shipping_country'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="notes">Notlar</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($customer['notes'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary">Kaydet</button>
                                        <a href="customers.php" class="btn btn-secondary ml-2">İptal</a>
                                    </div>
                                </form>
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

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE 3 App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
// Müşteri türüne göre ilgili alanların görünürlüğünü ayarla
$(document).ready(function() {
    function toggleCompanyFields() {
        var customerType = $('#type').val();
        if (customerType === 'corporate' || customerType === 'gallery') {
            $('.company-fields').show();
        } else {
            $('.company-fields').hide();
        }
    }
    
    toggleCompanyFields();
    $('#type').change(toggleCompanyFields);
});
</script>
</body>
</html> 