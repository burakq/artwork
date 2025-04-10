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

// ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Geçersiz kullanıcı ID'si.";
    $_SESSION['message_type'] = "error";
    redirect('users.php');
}

$id = $_GET['id'];

// Kullanıcı bilgilerini getir
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $_SESSION['message'] = "Kullanıcı bulunamadı.";
    $_SESSION['message_type'] = "error";
    redirect('users.php');
}

$user = $result->fetch_assoc();
$stmt->close();

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
    $password = $_POST['password'] ?? '';
    $errors = [];

    // Validasyon
    if (empty($name)) {
        $errors[] = "Ad Soyad alanı zorunludur.";
    }

    if (empty($email)) {
        $errors[] = "E-posta alanı zorunludur.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi giriniz.";
    } else {
        // Email değiştirilmiş mi ve başka kullanıcı tarafından kullanılıyor mu kontrol
        if ($email !== $user['email']) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $errors[] = "Bu e-posta adresi zaten kullanılmakta.";
            }
            
            $stmt->close();
        }
    }

    // Hata yoksa güncelle
    if (empty($errors)) {
        if (!empty($password)) {
            // Şifre değiştiriliyor
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, is_admin = ?, password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssisi", $name, $email, $isAdmin, $hashedPassword, $id);
        } else {
            // Şifre değiştirilmiyor
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, is_admin = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssii", $name, $email, $isAdmin, $id);
        }

        if ($stmt->execute()) {
            $_SESSION['message'] = "Kullanıcı başarıyla güncellendi.";
            $_SESSION['message_type'] = "success";
            
            // Kullanıcı kendi bilgilerini değiştirdiyse, session bilgilerini güncelle
            if ($id == $_SESSION['user_id']) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['is_admin'] = $isAdmin;
            }
            
            redirect('users.php');
        } else {
            $errors[] = "Kullanıcı güncellenirken bir hata oluştu: " . $conn->error;
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
    <title>Artwork Auth | Kullanıcı Düzenle</title>
    
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
                        <h1>Kullanıcı Düzenle</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
                            <li class="breadcrumb-item"><a href="users.php">Kullanıcılar</a></li>
                            <li class="breadcrumb-item active">Kullanıcı Düzenle</li>
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
                        
                        <!-- Kullanıcı düzenleme -->
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
                                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email">E-posta</label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="password">Şifre (Değiştirmek istemiyorsanız boş bırakın)</label>
                                                <input type="password" class="form-control" id="password" name="password" placeholder="Yeni şifre">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="confirmPassword">Şifre Tekrar</label>
                                                <input type="password" class="form-control" id="confirmPassword" placeholder="Şifreyi tekrar girin">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="is_admin" name="is_admin" <?php echo $user['is_admin'] ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="is_admin">Yönetici Yetkisi Ver</label>
                                        </div>
                                    </div>
                                    
                                    <?php if ($id == $_SESSION['user_id']): ?>
                                    <div class="alert alert-warning">
                                        <i class="icon fas fa-exclamation-triangle"></i> Uyarı!
                                        <p>Kendi hesabınızı düzenliyorsunuz. Yönetici yetkisini kaldırırsanız, bazı işlevlere erişiminiz kısıtlanacaktır.</p>
                                    </div>
                                    <?php endif; ?>
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
        if ($('#password').val()) {
            if (!validatePassword()) {
                e.preventDefault();
                alert('Şifreler eşleşmiyor!');
            }
        }
    });
});

// Şifre uyuşma kontrolü
function validatePassword() {
    var password = $('#password').val();
    var confirmPassword = $('#confirmPassword').val();
    
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