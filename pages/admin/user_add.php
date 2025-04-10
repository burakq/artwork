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

$errors = [];

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;

    // Validasyon
    if (empty($name)) {
        $errors[] = "Ad Soyad alanı zorunludur.";
    }

    if (empty($email)) {
        $errors[] = "E-posta alanı zorunludur.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi giriniz.";
    } else {
        // E-posta adresi zaten kullanılıyor mu kontrol et
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Bu e-posta adresi zaten kullanılmakta.";
        }
        
        $stmt->close();
    }

    if (empty($password)) {
        $errors[] = "Şifre alanı zorunludur.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Şifre en az 6 karakter olmalıdır.";
    } elseif ($password !== $confirmPassword) {
        $errors[] = "Şifreler eşleşmiyor.";
    }

    // Hata yoksa kullanıcı ekle
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, is_admin, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("sssi", $name, $email, $hashedPassword, $isAdmin);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Kullanıcı başarıyla eklendi.";
            $_SESSION['message_type'] = "success";
            redirect('users.php');
        } else {
            $errors[] = "Kullanıcı eklenirken bir hata oluştu: " . $conn->error;
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Artwork Auth | Yeni Kullanıcı Ekle</title>
    
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
                        <h1>Yeni Kullanıcı Ekle</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
                            <li class="breadcrumb-item"><a href="users.php">Kullanıcılar</a></li>
                            <li class="breadcrumb-item active">Yeni Kullanıcı Ekle</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <!-- Hata mesajları -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                <h5><i class="icon fas fa-ban"></i> Hata!</h5>
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Kullanıcı ekleme formu -->
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Kullanıcı Bilgileri</h3>
                            </div>
                            <!-- /.card-header -->
                            
                            <!-- form başlangıç -->
                            <form method="post" action="">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Ad Soyad</label>
                                                <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email">E-posta</label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="password">Şifre</label>
                                                <input type="password" class="form-control" id="password" name="password" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="confirmPassword">Şifre Tekrar</label>
                                                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="is_admin" name="is_admin" <?php echo isset($_POST['is_admin']) ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="is_admin">Yönetici Yetkisi Ver</label>
                                        </div>
                                    </div>
                                </div>
                                <!-- /.card-body -->

                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">Kaydet</button>
                                    <a href="users.php" class="btn btn-secondary">İptal</a>
                                </div>
                            </form>
                        </div>
                        <!-- /.card -->
                    </div>
                    <!-- /.col -->
                </div>
                <!-- /.row -->
            </div><!-- /.container-fluid -->
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

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- Custom JS -->
<script src="../../assets/js/script.js"></script>

<script>
$(document).ready(function() {
    // Şifre kontrolü
    $('#password, #confirmPassword').on('keyup', function() {
        validatePassword();
    });
    
    // Form submiti öncesi şifre kontrolü
    $('form').on('submit', function(e) {
        if (!validatePassword()) {
            e.preventDefault();
            alert('Şifreler eşleşmiyor!');
        }
    });
});

// Şifre uyuşma kontrolü
function validatePassword() {
    var password = $('#password').val();
    var confirmPassword = $('#confirmPassword').val();
    
    if (password == '') {
        return true; // Henüz giriş yapılmadı, hata verme
    }
    
    if (password != confirmPassword) {
        $('#confirmPassword').addClass('is-invalid');
        return false;
    } else {
        $('#confirmPassword').removeClass('is-invalid');
        return true;
    }
}
</script>
</body>
</html>

<?php
// Bağlantıyı kapat
$conn->close();
?> 