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
$error = '';

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Form verilerini al
    $name = sanitize($_POST['name']);
    $type = sanitize($_POST['type']);
    $company_name = isset($_POST['company_name']) ? sanitize($_POST['company_name']) : null;
    $tax_number = isset($_POST['tax_number']) ? sanitize($_POST['tax_number']) : null;
    $tax_office = isset($_POST['tax_office']) ? sanitize($_POST['tax_office']) : null;
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $notes = sanitize($_POST['notes']);
    $shipping_address = sanitize($_POST['shipping_address']);
    $shipping_city = sanitize($_POST['shipping_city']);
    $shipping_state = sanitize($_POST['shipping_state']);
    $shipping_postal_code = sanitize($_POST['shipping_postal_code']);
    $shipping_country = sanitize($_POST['shipping_country']);
    
    // E-posta adresi kontrolü
    $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ? AND deleted_at IS NULL LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $error = "Bu e-posta adresi ile kayıtlı bir müşteri zaten mevcut.";
    } else {
        // Müşteri ekle
        $stmt = $conn->prepare("INSERT INTO customers (name, type, company_name, tax_number, tax_office, email, phone, address, notes, 
                               shipping_address, shipping_city, shipping_state, shipping_postal_code, shipping_country, created_at, updated_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        
        $stmt->bind_param("ssssssssssssss", 
            $name, $type, $company_name, $tax_number, $tax_office, $email, $phone, $address, $notes, 
            $shipping_address, $shipping_city, $shipping_state, $shipping_postal_code, $shipping_country);
        
        if ($stmt->execute()) {
            // Başarıyla eklendi
            redirect('customers.php?success=1');
        } else {
            // Hata oluştu
            $error = "Müşteri eklenirken bir hata oluştu: " . $conn->error;
        }
    }
    
    $stmt->close();
}

// Bağlantıyı kapat
$conn->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Artwork Auth | Yeni Müşteri Ekle</title>
    
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
                        <h1 class="m-0">Yeni Müşteri Ekle</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
                            <li class="breadcrumb-item"><a href="customers.php">Müşteriler</a></li>
                            <li class="breadcrumb-item active">Yeni Müşteri Ekle</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Müşteri Bilgileri</h3>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Ad Soyad / Kurum Adı <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="type" class="form-label">Müşteri Türü <span class="text-danger">*</span></label>
                                        <select class="form-select" id="type" name="type" required>
                                            <option value="individual">Bireysel</option>
                                            <option value="corporate">Kurumsal</option>
                                            <option value="gallery">Galeri</option>
                                            <option value="collector">Koleksiyoner</option>
                                        </select>
                                    </div>
                                    
                                    <div id="companyFields" style="display: none;">
                                        <div class="mb-3">
                                            <label for="company_name" class="form-label">Firma Adı</label>
                                            <input type="text" class="form-control" id="company_name" name="company_name">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="tax_number" class="form-label">Vergi Numarası</label>
                                            <input type="text" class="form-control" id="tax_number" name="tax_number">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="tax_office" class="form-label">Vergi Dairesi</label>
                                            <input type="text" class="form-control" id="tax_office" name="tax_office">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">E-posta <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Telefon</label>
                                        <input type="text" class="form-control" id="phone" name="phone">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Adres</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="shipping_address" class="form-label">Teslimat Adresi</label>
                                        <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3"></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="shipping_city" class="form-label">Teslimat Şehri</label>
                                                <input type="text" class="form-control" id="shipping_city" name="shipping_city">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="shipping_state" class="form-label">Teslimat Bölgesi/İlçe</label>
                                                <input type="text" class="form-control" id="shipping_state" name="shipping_state">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="shipping_postal_code" class="form-label">Posta Kodu</label>
                                                <input type="text" class="form-control" id="shipping_postal_code" name="shipping_postal_code">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="shipping_country" class="form-label">Ülke</label>
                                                <input type="text" class="form-control" id="shipping_country" name="shipping_country" value="Türkiye">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notlar</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Müşteriyi Kaydet</button>
                                <a href="customers.php" class="btn btn-secondary">İptal</a>
                            </div>
                        </form>
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
<!-- AdminLTE 4 App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-alpha3/dist/js/adminlte.min.js"></script>

<script>
$(document).ready(function() {
    // Müşteri türüne göre firma alanlarını göster/gizle
    $('#type').change(function() {
        if ($(this).val() === 'corporate' || $(this).val() === 'gallery') {
            $('#companyFields').show();
        } else {
            $('#companyFields').hide();
        }
    });
    
    // Sayfa yüklendiğinde mevcut duruma göre kontrol et
    if ($('#type').val() === 'corporate' || $('#type').val() === 'gallery') {
        $('#companyFields').show();
    }
    
    // Adres kopyalama
    $('#copyAddress').click(function() {
        $('#shipping_address').val($('#address').val());
    });
});
</script>
</body>
</html> 