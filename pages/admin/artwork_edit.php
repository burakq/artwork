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
    redirect('artworks.php');
}

$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$id) {
    redirect('artworks.php');
}

// Veritabanı bağlantısı
$conn = connectDB();

// İşlem mesajı
$message = '';
$message_type = '';

// Eser bilgilerini getir
$artwork = null;
$stmt = $conn->prepare("SELECT * FROM artworks WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Eser bulunamadı
    redirect('artworks.php');
}

$artwork = $result->fetch_assoc();
$stmt->close();

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
    
    // Durum kontrolü - ENUM olduğu için sadece izin verilen değerler kabul edilecek
    $allowed_statuses = array('original', 'for_sale', 'sold', 'fake', 'archived');
    $status = trim($_POST['status']); // Önce trim yapalım
    
    // Debug için
    error_log("Gelen durum değeri: " . $status);
    
    // Sadece izin verilen değerlerden biri ise kullan
    if (!in_array($status, $allowed_statuses)) {
        $status = 'original'; // Eğer izin verilen değerlerden biri değilse varsayılan olarak 'original' kullan
    }
    
    error_log("Kullanılacak durum değeri: " . $status);
    
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $width = filter_var($_POST['width'], FILTER_VALIDATE_FLOAT);
    $height = filter_var($_POST['height'], FILTER_VALIDATE_FLOAT);
    $depth = filter_var($_POST['depth'], FILTER_VALIDATE_FLOAT);
    $dimension_unit = sanitize($_POST['dimension_unit']);
    
    // Resim yükleme işlemi
    $image_path = $artwork['image_path']; // Mevcut resim yolunu koru
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
                
                // Eski resmi sil (eğer varsa)
                if (!empty($artwork['image_path']) && file_exists('../../' . $artwork['image_path'])) {
                    unlink('../../' . $artwork['image_path']);
                }
            } else {
                $error = 'Dosya yüklenirken bir hata oluştu.';
            }
        } else {
            $error = 'Sadece JPG, JPEG, PNG ve GIF dosyaları yüklenebilir.';
        }
    }
    
    // Veritabanını güncelle
    $sql = "UPDATE artworks SET 
            title = ?, 
            artist_name = ?, 
            description = ?, 
            image_path = ?, 
            location = ?, 
            year = ?, 
            technique = ?, 
            price = ?, 
            width = ?, 
            height = ?, 
            depth = ?, 
            dimension_unit = ?, 
            updated_at = NOW() 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssiddddssi", 
        $title, $artist_name, $description, $image_path, $location, $year, 
        $technique, $price, $width, $height, $depth, $dimension_unit, $id);
    
    if ($stmt->execute()) {
        // Status değerini ayrı bir sorguda güncelleyelim
        if (in_array($status, $allowed_statuses)) {
            $status_sql = "UPDATE artworks SET status = ? WHERE id = ?";
            $status_stmt = $conn->prepare($status_sql);
            $status_stmt->bind_param("si", $status, $id);
            $status_stmt->execute();
            $status_stmt->close();
        }
        
        // Başarıyla güncellendi
        $message = "Eser başarıyla güncellendi.";
        $message_type = "success";
        
        // Güncellenmiş veriyi al
        $stmt = $conn->prepare("SELECT * FROM artworks WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $artwork = $result->fetch_assoc();
    } else {
        // Hata oluştu
        $message = "Eser güncellenirken bir hata oluştu: " . $conn->error;
        $message_type = "danger";
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
    <title>Artwork Auth | Eser Düzenle</title>
    
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
                        <h1 class="m-0">Eser Düzenle</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
                            <li class="breadcrumb-item"><a href="artworks.php">Eserler</a></li>
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
                        <!-- general form elements -->
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Eser Bilgileri</h3>
                            </div>
                            <!-- /.card-header -->

                            <!-- form start -->
                            <form action="artwork_edit.php?id=<?php echo $id; ?>" method="post" enctype="multipart/form-data">
                                <div class="card-body">
                                    <?php if (!empty($message)): ?>
                                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <?php echo $message; ?>
                                    </div>
                                    <?php endif; ?>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="title">Eser Adı</label>
                                                <input type="text" class="form-control" id="title" name="title" value="<?php echo $artwork['title']; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="artist_name">Sanatçı Adı</label>
                                                <input type="text" class="form-control" id="artist_name" name="artist_name" value="<?php echo $artwork['artist_name']; ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="description">Açıklama</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo $artwork['description']; ?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="location">Konum</label>
                                                <input type="text" class="form-control" id="location" name="location" value="<?php echo $artwork['location']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="year">Yapım Yılı</label>
                                                <input type="number" class="form-control" id="year" name="year" value="<?php echo $artwork['year']; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="technique">Teknik</label>
                                                <input type="text" class="form-control" id="technique" name="technique" value="<?php echo $artwork['technique']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="status">Durum</label>
                                                <select class="form-control" id="status" name="status" required>
                                                    <option value="original" <?php echo ($artwork['status'] == 'original') ? 'selected' : ''; ?>>Orijinal</option>
                                                    <option value="for_sale" <?php echo ($artwork['status'] == 'for_sale') ? 'selected' : ''; ?>>Satışta</option>
                                                    <option value="sold" <?php echo ($artwork['status'] == 'sold') ? 'selected' : ''; ?>>Satıldı</option>
                                                    <option value="fake" <?php echo ($artwork['status'] == 'fake') ? 'selected' : ''; ?>>Sahte</option>
                                                    <option value="archived" <?php echo ($artwork['status'] == 'archived') ? 'selected' : ''; ?>>Arşivlenmiş</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="price">Fiyat (₺)</label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="<?php echo $artwork['price']; ?>">
                                    </div>

                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="width">Genişlik</label>
                                                <input type="number" step="0.01" min="0" class="form-control" id="width" name="width" value="<?php echo $artwork['width']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="height">Yükseklik</label>
                                                <input type="number" step="0.01" min="0" class="form-control" id="height" name="height" value="<?php echo $artwork['height']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="depth">Derinlik</label>
                                                <input type="number" step="0.01" min="0" class="form-control" id="depth" name="depth" value="<?php echo $artwork['depth']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="dimension_unit">Ölçü Birimi</label>
                                                <select class="form-control" id="dimension_unit" name="dimension_unit">
                                                    <option value="cm" <?php echo ($artwork['dimension_unit'] == 'cm') ? 'selected' : ''; ?>>cm</option>
                                                    <option value="inch" <?php echo ($artwork['dimension_unit'] == 'inch') ? 'selected' : ''; ?>>inch</option>
                                                    <option value="mm" <?php echo ($artwork['dimension_unit'] == 'mm') ? 'selected' : ''; ?>>mm</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="verification_code">Doğrulama Kodu</label>
                                        <input type="text" class="form-control" id="verification_code" value="<?php echo $artwork['verification_code']; ?>" readonly>
                                        <small class="text-muted">Doğrulama kodu otomatik oluşturulur ve değiştirilemez.</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="image">Eser Görseli</label>
                                        <?php if (!empty($artwork['image_path'])): ?>
                                            <div class="mb-2">
                                                <img src="../../<?php echo $artwork['image_path']; ?>" alt="<?php echo $artwork['title']; ?>" class="img-thumbnail" style="max-height: 200px;">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" id="image" name="image">
                                        <small class="text-muted">Yeni bir görsel yüklerseniz, mevcut görsel değiştirilecektir.</small>
                                    </div>
                                </div>
                                <!-- /.card-body -->

                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">Güncelle</button>
                                    <a href="artworks.php" class="btn btn-secondary">İptal</a>
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
<!-- Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE 3 App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html> 