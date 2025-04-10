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

// Dosya yükleme dizini
$uploadDir = 'uploads/artworks/';
if (!file_exists('../../' . $uploadDir)) {
    mkdir('../../' . $uploadDir, 0777, true);
}

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Form verilerini al
    $title = sanitize($_POST['title']);
    $artist_name = sanitize($_POST['artist_name']);
    $description = sanitize($_POST['description']);
    $location = sanitize($_POST['location']);
    $year = filter_var($_POST['year'], FILTER_VALIDATE_INT);
    $technique = sanitize($_POST['technique']);
    $status = sanitize($_POST['status']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $width = filter_var($_POST['width'], FILTER_VALIDATE_FLOAT);
    $height = filter_var($_POST['height'], FILTER_VALIDATE_FLOAT);
    $depth = filter_var($_POST['depth'], FILTER_VALIDATE_FLOAT);
    $dimension_unit = sanitize($_POST['dimension_unit']);
    
    // Doğrulama kodu oluştur - 12 hane
    $verification_code = generateVerificationCode();
    
    // Resim yükleme işlemi
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // Dosya adını düzenle - eser adı ve sanatçı adı kullanarak
        $fileExt = pathinfo(basename($_FILES['image']['name']), PATHINFO_EXTENSION);
        $sanitizedTitle = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
        $sanitizedArtist = preg_replace('/[^a-z0-9]+/', '-', strtolower($artist_name));
        $fileName = substr($sanitizedTitle, 0, 40) . '-' . substr($sanitizedArtist, 0, 20) . '-' . time() . '.' . $fileExt;
        
        $targetFilePath = '../../' . $uploadDir . $fileName;
        $fileType = strtolower($fileExt);
        
        // İzin verilen dosya türleri
        $allowTypes = array('jpg', 'jpeg', 'png', 'gif');
        if (in_array($fileType, $allowTypes)) {
            // Dosyayı yükle
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
                $image_path = $uploadDir . $fileName;
            } else {
                $error = 'Dosya yüklenirken bir hata oluştu.';
            }
        } else {
            $error = 'Sadece JPG, JPEG, PNG ve GIF dosyaları yüklenebilir.';
        }
    }
    
    // Veritabanına kaydet
    $sql = "INSERT INTO artworks (title, artist_name, description, image_path, location, year, 
            technique, status, price, verification_code, width, height, depth, dimension_unit, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssissdsddss", 
        $title, $artist_name, $description, $image_path, $location, $year, 
        $technique, $status, $price, $verification_code, $width, $height, $depth, $dimension_unit);
    
    if ($stmt->execute()) {
        // Başarıyla eklendi
        redirect('artworks.php?success=1');
    } else {
        // Hata oluştu
        $error = "Eser eklenirken bir hata oluştu: " . $conn->error;
    }
    
    $stmt->close();
}

// Bağlantıyı kapat
$conn->close();

/**
 * Benzersiz doğrulama kodu oluşturur
 * @return string Doğrulama kodu
 */
function generateVerificationCode() {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < 12; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Artwork Auth | Yeni Eser Ekle</title>
    
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
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Yeni Eser Ekle</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
                            <li class="breadcrumb-item"><a href="artworks.php">Eser Listesi</a></li>
                            <li class="breadcrumb-item active">Yeni Eser Ekle</li>
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
                        <!-- general form elements -->
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Eser Bilgileri</h3>
                            </div>
                            <!-- /.card-header -->

                            <!-- form start -->
                            <form action="artwork_add.php" method="post" enctype="multipart/form-data">
                                <div class="card-body">
                                    <?php if (isset($error)): ?>
                                    <div class="alert alert-danger">
                                        <?php echo $error; ?>
                                    </div>
                                    <?php endif; ?>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="title" class="required">Eser Başlığı</label>
                                                <input type="text" class="form-control" id="title" name="title" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="artist_name" class="required">Sanatçı Adı</label>
                                                <input type="text" class="form-control" id="artist_name" name="artist_name" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="description">Açıklama</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="image" class="required">Eser Görseli</label>
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="image" name="image" required>
                                                <label class="custom-file-label" for="image">Dosya seçin</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="location">Konum</label>
                                                <input type="text" class="form-control" id="location" name="location">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="year">Yıl</label>
                                                <input type="number" class="form-control" id="year" name="year" min="1" max="<?php echo date('Y'); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="technique">Teknik</label>
                                                <input type="text" class="form-control" id="technique" name="technique">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="status" class="required">Durum</label>
                                                <select class="form-control" id="status" name="status" required>
                                                    <option value="original">Orijinal</option>
                                                    <option value="for_sale">Satışta</option>
                                                    <option value="sold">Satıldı</option>
                                                    <option value="archived">Arşivlendi</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="price">Fiyat</label>
                                        <input type="number" class="form-control" id="price" name="price" min="0" step="0.01">
                                    </div>

                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="width">Genişlik</label>
                                                <input type="number" class="form-control" id="width" name="width" min="0" step="0.01">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="height">Yükseklik</label>
                                                <input type="number" class="form-control" id="height" name="height" min="0" step="0.01">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="depth">Derinlik</label>
                                                <input type="number" class="form-control" id="depth" name="depth" min="0" step="0.01">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="dimension_unit">Boyut Birimi</label>
                                                <select class="form-control" id="dimension_unit" name="dimension_unit">
                                                    <option value="cm">cm</option>
                                                    <option value="mm">mm</option>
                                                    <option value="m">m</option>
                                                    <option value="inch">inç</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- /.card-body -->

                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">Eser Ekle</button>
                                    <a href="artworks.php" class="btn btn-default">İptal</a>
                                </div>
                            </form>
                        </div>
                        <!-- /.card -->
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
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- bs-custom-file-input -->
<script src="https://cdn.jsdelivr.net/npm/bs-custom-file-input@1.3.4/dist/bs-custom-file-input.min.js"></script>
<!-- AdminLTE 3 App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
$(function () {
    bsCustomFileInput.init();
});
</script>
</body>
</html> 