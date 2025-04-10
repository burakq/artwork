<?php
session_start();
require_once 'includes/functions.php';

// Kullanıcı zaten giriş yapmış mı kontrol et
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$email = '';

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verileri al ve temizle
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    // Boş alan kontrolü
    if (empty($email) || empty($password)) {
        $error = 'Lütfen e-posta ve şifrenizi giriniz.';
    } else {
        // Kullanıcı girişini kontrol et
        $user = loginUser($email, $password);
        
        if ($user) {
            // Oturum başlat
            createUserSession($user);
            
            // Admin kontrolü ve yönlendirme
            if (isAdmin()) {
                redirect('pages/admin/dashboard.php');
            } else {
                redirect('index.php');
            }
        } else {
            $error = 'E-posta veya şifre hatalı.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Artwork Auth | Giriş</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <a href="index.php"><b>Artwork</b>Auth</a>
        </div>
        <!-- /.login-logo -->
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Giriş yapmak için bilgilerinizi giriniz</p>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="login.php">
                    <div class="input-group mb-3">
                        <input type="email" class="form-control" placeholder="E-posta" name="email" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" placeholder="Şifre" name="password" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Beni hatırla</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block">Giriş Yap</button>
                        </div>
                    </div>
                </form>

                <p class="mb-1 mt-3">
                    <a href="forgot-password.php">Şifremi unuttum</a>
                </p>
            </div>
            <!-- /.login-card-body -->
        </div>
    </div>
    <!-- /.login-box -->

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE 4 App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-alpha3/dist/js/adminlte.min.js"></script>
</body>
</html> 