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

// Boyut bilgisini birleştir
$artwork['dimensions'] = $artwork['dimensions'] . ' ' . $artwork['size'];

// NULL değerler için varsayılan değerler ata
$artwork['edition_number'] = $artwork['edition_number'] ?? '';
$artwork['first_owner'] = $artwork['first_owner'] ?? '';
$artwork['price'] = $artwork['price'] ?? 0;
$artwork['width'] = $artwork['width'] ?? 0;
$artwork['height'] = $artwork['height'] ?? 0;
$artwork['depth'] = $artwork['depth'] ?? 0;
$artwork['dimension_unit'] = $artwork['dimension_unit'] ?? 'cm';
$artwork['edition_type'] = $artwork['edition_type'] ?? 'unique';
$artwork['technique'] = $artwork['technique'] ?? '';
$artwork['status'] = $artwork['status'] ?? 'original';
$artwork['location'] = $artwork['location'] ?? '';
$artwork['description'] = $artwork['description'] ?? '';

// Status değerini debug için
error_log("Mevcut durum (DB'den alınan): " . $artwork['status']);

$stmt->close();

// Dosya yükleme dizini
$uploadDir = 'uploads/artworks/';
if (!file_exists('../../' . $uploadDir)) {
    mkdir('../../' . $uploadDir, 0777, true);
}

// Teknikleri getir
$techniques = [];
$query = "SELECT * FROM artwork_techniques WHERE is_active = 1 ORDER BY technique_name";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $techniques[$row['technique_key']] = $row['technique_name'];
    }
}

// Baskı türlerini getir
$edition_types = [];
$query = "SELECT * FROM artwork_edition_types WHERE is_active = 1 ORDER BY edition_name";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $edition_types[$row['edition_key']] = $row['edition_name'];
    }
}

// Durumları getir
$statuses = [];
$query = "SELECT * FROM artwork_statuses WHERE is_active = 1 ORDER BY status_name";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $statuses[$row['status_key']] = $row['status_name'];
    }
}

// Hiç durum yoksa varsayılan değerleri ekle
if (empty($statuses)) {
    $statuses = [
        'original' => 'Orijinal',
        'for_sale' => 'Satışta',
        'sold' => 'Satıldı',
        'archived' => 'Arşivlendi',
        'fake' => 'Sahte'
    ];
}

// Konumları getir
$locations = [];
$query = "SELECT * FROM artwork_locations WHERE is_active = 1 ORDER BY location_name";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locations[$row['location_key']] = $row['location_name'];
    }
}

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Form verilerini al
    $title = sanitize($_POST['title']);
    $artist_name = sanitize($_POST['artist_name']);
    $description = sanitize($_POST['description']);
    $location = sanitize($_POST['location']);
    $print_date = sanitize($_POST['print_date']);
    
    // Tarih formatını değiştir (DD.MM.YYYY -> YYYY-MM-DD)
    if (!empty($print_date)) {
        $date_parts = explode('.', $print_date);
        if (count($date_parts) === 3) {
            $print_date = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
        } else {
            // Geçersiz tarih formatı
            $print_date = null;
        }
    } else {
        $print_date = null;
    }
    
    $technique = sanitize($_POST['technique']);
    $status = sanitize($_POST['status']); // Formdan gelen status_key değeri doğrudan kullanılacak
    $edition_type = sanitize($_POST['edition_type']);
    $edition_number = sanitize($_POST['edition_number']);
    $first_owner = sanitize($_POST['first_owner']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $dimensions = sanitize($_POST['dimensions']);
    
    error_log("Düzenleme formundan gelen status değeri: $status");
    
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
    
    // Veritabanını güncelle (status dahil tüm alanlar)
    $sql = "UPDATE artworks SET 
            title = ?, 
            artist_name = ?, 
            description = ?, 
            image_path = ?, 
            location = ?, 
            print_date = ?, 
            technique = ?, 
            status = ?,
            edition_type = ?,
            edition_number = ?,
            first_owner = ?,
            price = ?, 
            dimensions = ?, 
            updated_at = NOW() 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    
    // Hata ayıklama için
    error_log("SQL: " . $sql);
    
    // bind_param - sssssssssssds + i (14 parametre)
    $stmt->bind_param("sssssssssssdsi", 
        $title, $artist_name, $description, $image_path, $location, $print_date, 
        $technique, $status, $edition_type, $edition_number, $first_owner, $price, 
        $dimensions, $id);
    
    if ($stmt->execute()) {
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
    <!-- Tempus Dominus Bootstrap 4 Date/Time Picker -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/css/tempusdominus-bootstrap-4.min.css">
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
                                                <select class="form-control" id="location" name="location">
                                                    <option value="">Konum Seçin</option>
                                                    <?php foreach ($locations as $key => $name): ?>
                                                        <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $artwork['location'] == $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="print_date">Baskı Tarihi</label>
                                                <div class="input-group date" id="datetimepicker1" data-target-input="nearest">
                                                    <input type="text" class="form-control datetimepicker-input" data-target="#datetimepicker1" id="print_date" name="print_date" value="<?php echo !empty($artwork['print_date']) ? date('d.m.Y', strtotime($artwork['print_date'])) : ''; ?>" placeholder="GG.AA.YYYY">
                                                    <div class="input-group-append" data-target="#datetimepicker1" data-toggle="datetimepicker">
                                                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                    </div>
                                                </div>
                                                <small class="form-text text-muted">Tarih formatı: GG.AA.YYYY</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Teknik</label>
                                                <select class="form-control" name="technique" required>
                                                    <option value="">Teknik Seçin</option>
                                                    <?php foreach ($techniques as $key => $name): ?>
                                                        <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($artwork['technique'] == $key) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Durum</label>
                                                <select class="form-control" name="status" required>
                                                    <option value="">Durum Seçin</option>
                                                    <?php foreach ($statuses as $key => $name): ?>
                                                        <option value="<?php echo htmlspecialchars($key); ?>" 
                                                                <?php echo ($artwork['status'] == $key) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($name); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="edition_type">Baskı Türü</label>
                                                <select class="form-control" id="edition_type" name="edition_type">
                                                    <option value="">Seçiniz</option>
                                                    <?php foreach ($edition_types as $key => $name): ?>
                                                        <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($artwork['edition_type'] == $key) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="edition_number">Baskı Numarası</label>
                                                <input type="text" class="form-control" id="edition_number" name="edition_number" placeholder="Örn: 39-49" value="<?php echo htmlspecialchars($artwork['edition_number']); ?>">
                                                <small class="text-muted">Edition seçilirse baskı numarasını girin (örn: 39-49)</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="first_owner">İlk Sahip</label>
                                                <input type="text" class="form-control" id="first_owner" name="first_owner" value="<?php echo htmlspecialchars($artwork['first_owner']); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="price">Fiyat (₺)</label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="<?php echo $artwork['price']; ?>">
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="dimensions">Boyut</label>
                                                <input type="text" class="form-control" id="dimensions" name="dimensions" value="<?php echo htmlspecialchars($artwork['dimensions'] ?? ''); ?>">
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
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="image" name="image">
                                                <label class="custom-file-label" for="image">Dosya Seçin</label>
                                            </div>
                                        </div>
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
<!-- bs-custom-file-input -->
<script src="https://cdn.jsdelivr.net/npm/bs-custom-file-input@1.3.4/dist/bs-custom-file-input.min.js"></script>
<!-- AdminLTE 3 App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- Moment.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/tr.js"></script>
<!-- Tempus Dominus Bootstrap 4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/js/tempusdominus-bootstrap-4.min.js"></script>

<script>
$(function () {
    // Özel dosya input'u için
    bsCustomFileInput.init();
    
    // Tarih seçici
    $('#datetimepicker1').datetimepicker({
        format: 'DD.MM.YYYY',
        locale: 'tr',
        icons: {
            time: 'far fa-clock',
            date: 'far fa-calendar',
            up: 'fas fa-arrow-up',
            down: 'fas fa-arrow-down',
            previous: 'fas fa-chevron-left',
            next: 'fas fa-chevron-right',
            today: 'fas fa-calendar-check',
            clear: 'far fa-trash-alt',
            close: 'far fa-times-circle'
        }
    });
    
    // Dosya seçildiğinde label'ı güncelle
    $('#image').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });
});
</script>
</body>
</html> 