<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Giriş yapılmış mı kontrol et
if (!isLoggedIn()) {
    redirect('../../login.php');
    exit;
}

// Admin yetkisi kontrolü
if (!isAdmin()) {
    redirect('../../index.php');
    exit;
}

// Veritabanı bağlantısı
$conn = connectDB();

$success_message = '';
$error_message = '';

// Örnek eser resmi için veritabanından rastgele bir resim çek
$sample_artwork = null;
$sample_query = "SELECT id, title, image_path, artist_name, verification_code FROM artworks WHERE image_path IS NOT NULL AND deleted_at IS NULL LIMIT 1";
$sample_result = $conn->query($sample_query);
if ($sample_result && $sample_result->num_rows > 0) {
    $sample_artwork = $sample_result->fetch_assoc();
}

// Mevcut ayarları getir
$settings = [];

$query = "SELECT setting_key, setting_value FROM settings";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Varsayılan değerler
$certificate_title = isset($settings['certificate_title']) ? $settings['certificate_title'] : 'Orijinallik Sertifikası';
$certificate_footer = isset($settings['certificate_footer']) ? $settings['certificate_footer'] : 'Bu sertifika, eserin orijinalliğini doğrulamak için düzenlenmiştir.';
$certificate_background_color = isset($settings['certificate_background_color']) ? $settings['certificate_background_color'] : '#ffffff';
$certificate_text_color = isset($settings['certificate_text_color']) ? $settings['certificate_text_color'] : '#000000';
$certificate_font = isset($settings['certificate_font']) ? $settings['certificate_font'] : 'Arial';
$show_qr_code = isset($settings['show_qr_code']) ? $settings['show_qr_code'] : 1;
$show_logo = isset($settings['show_logo']) ? $settings['show_logo'] : 1;
$certificate_logo = isset($settings['certificate_logo']) ? $settings['certificate_logo'] : '';
$template_style = isset($settings['template_style']) ? $settings['template_style'] : 'classic';
$paper_size = isset($settings['paper_size']) ? $settings['paper_size'] : 'A4';
$background_image = isset($settings['certificate_background_image']) ? $settings['certificate_background_image'] : '';
$use_background_image = isset($settings['use_background_image']) ? $settings['use_background_image'] : 0;
$elements_positions = isset($settings['elements_positions']) ? $settings['elements_positions'] : '{}';

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $certificate_title = isset($_POST['certificate_title']) ? sanitize($_POST['certificate_title']) : '';
    $certificate_footer = isset($_POST['certificate_footer']) ? sanitize($_POST['certificate_footer']) : '';
    $certificate_background_color = isset($_POST['certificate_background_color']) ? sanitize($_POST['certificate_background_color']) : '#ffffff';
    $certificate_text_color = isset($_POST['certificate_text_color']) ? sanitize($_POST['certificate_text_color']) : '#000000';
    $certificate_font = isset($_POST['certificate_font']) ? sanitize($_POST['certificate_font']) : 'Arial';
    $show_qr_code = isset($_POST['show_qr_code']) ? 1 : 0;
    $show_logo = isset($_POST['show_logo']) ? 1 : 0;
    $template_style = isset($_POST['template_style']) ? sanitize($_POST['template_style']) : 'classic';
    $paper_size = isset($_POST['paper_size']) ? sanitize($_POST['paper_size']) : 'A4';
    $use_background_image = isset($_POST['use_background_image']) ? 1 : 0;
    $elements_positions = isset($_POST['elements_positions']) ? $_POST['elements_positions'] : '{}';
    
    // Arka plan görüntüsü yükleme işlemi
    if(isset($_FILES['background_image']) && $_FILES['background_image']['size'] > 0) {
        $upload_dir = '../../uploads/certificate/backgrounds/';
        
        // Klasör yoksa oluştur
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['background_image']['name']);
        $target_file = $upload_dir . $file_name;
        
        // Geçerli bir resim dosyası mı kontrol et
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif") {
            if(move_uploaded_file($_FILES['background_image']['tmp_name'], $target_file)) {
                $bg_path = 'uploads/certificate/backgrounds/' . $file_name;
                
                // Arka plan görüntüsü yolunu güncelle
                $stmt = $conn->prepare("SELECT id FROM settings WHERE setting_key = 'certificate_background_image'");
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $stmt = $conn->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'certificate_background_image'");
                    $stmt->bind_param("s", $bg_path);
                } else {
                    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES ('certificate_background_image', ?, NOW(), NOW())");
                    $stmt->bind_param("s", $bg_path);
                }
                $stmt->execute();
            } else {
                $error_message = "Arka plan görüntüsü yüklenirken bir hata oluştu.";
            }
        } else {
            $error_message = "Sadece JPG, JPEG, PNG ve GIF dosyaları yüklenebilir.";
        }
    }
    
    // Ayarları veritabanına kaydet
    $settings_array = [
        'certificate_title' => $certificate_title,
        'certificate_footer' => $certificate_footer,
        'certificate_background_color' => $certificate_background_color,
        'certificate_text_color' => $certificate_text_color,
        'certificate_font' => $certificate_font,
        'show_qr_code' => $show_qr_code,
        'show_logo' => $show_logo,
        'template_style' => $template_style,
        'paper_size' => $paper_size,
        'use_background_image' => $use_background_image,
        'elements_positions' => $elements_positions
    ];
    
    foreach ($settings_array as $key => $value) {
        $stmt = $conn->prepare("SELECT id FROM settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
            $stmt->bind_param("ss", $value, $key);
        } else {
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
            $stmt->bind_param("ss", $key, $value);
        }
        $stmt->execute();
    }
    
    if (empty($error_message)) {
        $success_message = "Sertifika tasarımı ayarları başarıyla güncellendi.";
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
    <title>Sertifika Tasarımı - Artwork Auth Yönetim Paneli</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Color Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-colorpicker@3.4.0/dist/css/bootstrap-colorpicker.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <!-- jQuery UI CSS -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
        .certificate-preview {
            border: 1px solid #ddd;
            padding: 20px;
            margin-top: 20px;
            min-height: 600px;
            background-color: <?php echo $certificate_background_color; ?>;
            color: <?php echo $certificate_text_color; ?>;
            font-family: <?php echo $certificate_font; ?>, sans-serif;
            position: relative;
            overflow: hidden;
        }
        <?php if ($use_background_image && !empty($background_image)): ?>
        .certificate-preview {
            background-image: url('../../<?php echo $background_image; ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        <?php endif; ?>
        .certificate-preview .title {
            font-size: 24px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
            position: relative;
            z-index: 2;
            cursor: move;
            width: auto;
        }
        .certificate-preview .content {
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
            cursor: move;
            width: auto;
        }
        .certificate-preview .artwork-image {
            text-align: center;
            margin: 20px 0;
            position: relative;
            z-index: 2;
            cursor: move;
            width: auto;
        }
        .certificate-preview .artwork-image img {
            max-width: 300px;
            max-height: 200px;
            border: 1px solid #ddd;
        }
        .certificate-preview .footer {
            font-size: 14px;
            text-align: center;
            margin-top: 30px;
            font-style: italic;
            position: relative;
            z-index: 2;
            cursor: move;
            width: auto;
        }
        .certificate-preview .logo-container {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
            cursor: move;
            width: auto;
        }
        .certificate-preview .logo-container img {
            max-width: 150px;
            max-height: 100px;
        }
        .certificate-preview .qr-container {
            text-align: center;
            margin: 20px 0;
            position: relative;
            z-index: 2;
            cursor: move;
            width: auto;
        }
        .certificate-preview .qr-container img {
            width: 100px;
            height: 100px;
            background-color: #f5f5f5;
        }
        .template-option {
            cursor: pointer;
            border: 2px solid #ddd;
            padding: 10px;
            margin: 5px;
            text-align: center;
        }
        .template-option.selected {
            border-color: #007bff;
            background-color: #f0f7ff;
        }
        .template-option img {
            max-width: 100%;
            height: auto;
        }
        .certificate-element {
            border: 1px dashed transparent;
            padding: 5px;
            box-sizing: border-box;
        }
        .certificate-element:hover {
            border: 1px dashed #007bff;
        }
        .editor-active .certificate-element {
            border: 1px dashed #ccc;
        }
        .ui-resizable-handle {
            background-color: #007bff;
            border-radius: 50%;
            width: 8px;
            height: 8px;
            z-index: 100 !important;
        }
        .design-mode-toggle {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }
        .save-positions {
            position: absolute;
            top: 10px;
            right: 140px;
            z-index: 1000;
            display: none;
        }
        .reset-positions {
            position: absolute;
            top: 10px;
            right: 250px;
            z-index: 1000;
            display: none;
        }
        .fullscreen-toggle {
            position: absolute;
            top: 10px;
            right: 370px;
            z-index: 1000;
        }
        #fullscreen-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 9999;
            display: none;
            overflow: auto;
            padding: 20px;
            box-sizing: border-box;
        }
        #fullscreen-certificate {
            width: 90%;
            height: 90vh;
            margin: 0 auto;
            background: white;
            padding: 20px;
            position: relative;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }
        .close-fullscreen {
            position: fixed;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 30px;
            cursor: pointer;
            z-index: 10000;
        }
        #fullscreen-tools {
            position: fixed;
            top: 20px;
            left: 20px; 
            z-index: 10000;
        }
        .btn-fullscreen {
            background: white;
            color: #333;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            margin-right: 5px;
            cursor: pointer;
        }
        .btn-fullscreen:hover {
            background: #f0f0f0;
        }
        /* Yeni: Yazdırma önizleme için */
        #print-iframe {
            width: 100%;
            height: 100vh;
            border: none;
        }
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
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
            </ul>
            
            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a href="../../logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Çıkış
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
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Sertifika Tasarımı</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="dashboard.php">Anasayfa</a></li>
                                <li class="breadcrumb-item active">Sertifika Tasarımı</li>
                            </ol>
                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-check"></i> Başarılı!</h5>
                        <?php echo $success_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-ban"></i> Hata!</h5>
                        <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Sertifika Tasarım Ayarları</h3>
                                </div>
                                <form method="post" enctype="multipart/form-data">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="template_style">Şablon Stili</label>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="template-option <?php echo ($template_style == 'classic') ? 'selected' : ''; ?>" data-template="classic">
                                                        <img src="../../assets/img/certificate_classic.png" alt="Klasik">
                                                        <div>Klasik</div>
                                                        <input type="radio" name="template_style" value="classic" <?php echo ($template_style == 'classic') ? 'checked' : ''; ?> style="display:none;">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="template-option <?php echo ($template_style == 'modern') ? 'selected' : ''; ?>" data-template="modern">
                                                        <img src="../../assets/img/certificate_modern.png" alt="Modern">
                                                        <div>Modern</div>
                                                        <input type="radio" name="template_style" value="modern" <?php echo ($template_style == 'modern') ? 'checked' : ''; ?> style="display:none;">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="template-option <?php echo ($template_style == 'minimalist') ? 'selected' : ''; ?>" data-template="minimalist">
                                                        <img src="../../assets/img/certificate_minimalist.png" alt="Minimalist">
                                                        <div>Minimalist</div>
                                                        <input type="radio" name="template_style" value="minimalist" <?php echo ($template_style == 'minimalist') ? 'checked' : ''; ?> style="display:none;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="certificate_title">Sertifika Başlığı</label>
                                            <input type="text" class="form-control" id="certificate_title" name="certificate_title" value="<?php echo htmlspecialchars($certificate_title); ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="certificate_footer">Alt Bilgi</label>
                                            <textarea class="form-control" id="certificate_footer" name="certificate_footer" rows="3"><?php echo htmlspecialchars($certificate_footer); ?></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="certificate_background_color">Arkaplan Rengi</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="certificate_background_color" name="certificate_background_color" value="<?php echo htmlspecialchars($certificate_background_color); ?>">
                                                <div class="input-group-append">
                                                    <span class="input-group-text"><i class="fas fa-square" style="color: <?php echo $certificate_background_color; ?>;"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="certificate_text_color">Yazı Rengi</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="certificate_text_color" name="certificate_text_color" value="<?php echo htmlspecialchars($certificate_text_color); ?>">
                                                <div class="input-group-append">
                                                    <span class="input-group-text"><i class="fas fa-square" style="color: <?php echo $certificate_text_color; ?>;"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="certificate_font">Yazı Tipi</label>
                                            <select class="form-control" id="certificate_font" name="certificate_font">
                                                <option value="Arial" <?php echo ($certificate_font == 'Arial') ? 'selected' : ''; ?>>Arial</option>
                                                <option value="Times New Roman" <?php echo ($certificate_font == 'Times New Roman') ? 'selected' : ''; ?>>Times New Roman</option>
                                                <option value="Verdana" <?php echo ($certificate_font == 'Verdana') ? 'selected' : ''; ?>>Verdana</option>
                                                <option value="Helvetica" <?php echo ($certificate_font == 'Helvetica') ? 'selected' : ''; ?>>Helvetica</option>
                                                <option value="Georgia" <?php echo ($certificate_font == 'Georgia') ? 'selected' : ''; ?>>Georgia</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="show_logo" name="show_logo" value="1" <?php echo ($show_logo == 1) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="show_logo">Logo Göster</label>
                                            </div>
                                            <small class="form-text text-muted">Logo ayarlarını "Ayarlar" sayfasından yapabilirsiniz.</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="show_qr_code" name="show_qr_code" value="1" <?php echo ($show_qr_code == 1) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="show_qr_code">QR Kod Göster</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="paper_size">Kağıt Boyutu</label>
                                            <select class="form-control" id="paper_size" name="paper_size">
                                                <option value="A4" <?php echo ($paper_size == 'A4') ? 'selected' : ''; ?>>A4 (210 × 297mm)</option>
                                                <option value="A5" <?php echo ($paper_size == 'A5') ? 'selected' : ''; ?>>A5 (148 × 210mm)</option>
                                                <option value="A6" <?php echo ($paper_size == 'A6') ? 'selected' : ''; ?>>A6 (105 × 148mm)</option>
                                                <option value="Letter" <?php echo ($paper_size == 'Letter') ? 'selected' : ''; ?>>Letter (216 × 279mm)</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="background_image">Arka Plan Görüntüsü</label>
                                            <div class="input-group">
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="background_image" name="background_image" accept="image/*">
                                                    <label class="custom-file-label" for="background_image">Dosya seçin</label>
                                                </div>
                                            </div>
                                            <?php if (!empty($background_image)): ?>
                                            <div class="mt-2">
                                                <img src="../../<?php echo $background_image; ?>" alt="Arka Plan" style="max-width: 100px; max-height: 100px;">
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="use_background_image" name="use_background_image" value="1" <?php echo ($use_background_image == 1) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="use_background_image">Arka Plan Görüntüsünü Kullan</label>
                                            </div>
                                        </div>
                                        
                                        <input type="hidden" id="elements_positions" name="elements_positions" value='<?php echo htmlspecialchars($elements_positions); ?>'>
                                    </div>
                                    
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">Kaydet</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card card-secondary">
                                <div class="card-header">
                                    <h3 class="card-title">Sertifika Önizleme</h3>
                                </div>
                                <div class="card-body">
                                    <div class="certificate-preview" id="certificate_preview">
                                        <button type="button" class="btn btn-sm btn-primary design-mode-toggle">Tasarım Modunu Aç</button>
                                        <button type="button" class="btn btn-sm btn-success save-positions">Pozisyonları Kaydet</button>
                                        <button type="button" class="btn btn-sm btn-danger reset-positions">Sıfırla</button>
                                        <button type="button" class="btn btn-sm btn-secondary fullscreen-toggle">Tam Ekran</button>
                                        
                                        <?php if ($show_logo == 1 && !empty($certificate_logo)): ?>
                                        <div class="logo-container certificate-element" data-element="logo">
                                            <img src="../../<?php echo $certificate_logo; ?>" alt="Logo">
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="title certificate-element" data-element="title"><?php echo htmlspecialchars($certificate_title); ?></div>
                                        
                                        <div class="content certificate-element" data-element="content">
                                            <?php if ($sample_artwork): ?>
                                            <p>Bu belge <strong><?php echo htmlspecialchars($sample_artwork['title']); ?></strong> adlı eserin orijinalliğini doğrulamaktadır.</p>
                                            <p><strong>Sanatçı:</strong> <?php echo htmlspecialchars($sample_artwork['artist_name'] ?: 'Örnek Sanatçı'); ?></p>
                                            <p><strong>Doğrulama Kodu:</strong> <?php echo htmlspecialchars($sample_artwork['verification_code']); ?></p>
                                            <?php else: ?>
                                            <p>Bu belge <strong>Örnek Sanat Eseri</strong> adlı eserin orijinalliğini doğrulamaktadır.</p>
                                            <p><strong>Sanatçı:</strong> Örnek Sanatçı</p>
                                            <p><strong>Boyut:</strong> 50x70 cm</p>
                                            <p><strong>Teknik:</strong> Yağlı Boya</p>
                                            <p><strong>Doğrulama Kodu:</strong> ABC123XYZ</p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($sample_artwork && !empty($sample_artwork['image_path'])): ?>
                                        <div class="artwork-image certificate-element" data-element="artwork-image">
                                            <img src="../../<?php echo $sample_artwork['image_path']; ?>" alt="Eser Görseli">
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($show_qr_code == 1): ?>
                                        <div class="qr-container certificate-element" data-element="qr">
                                            <img src="../../assets/img/qr-sample.png" alt="QR Kod">
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="footer certificate-element" data-element="footer"><?php echo htmlspecialchars($certificate_footer); ?></div>
                                    </div>
                                    
                                    <button type="button" class="btn btn-info mt-3" id="print_preview_btn">
                                        <i class="fas fa-print"></i> Yazdırma Önizleme
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- /.content -->
        </div>
        
        <!-- Tam ekran modu için gerekli HTML -->
        <div id="fullscreen-container">
            <div id="fullscreen-tools" class="no-print">
                <button type="button" class="btn-fullscreen fullscreen-design-mode">Tasarım Modu</button>
                <button type="button" class="btn-fullscreen fullscreen-print">Yazdır</button>
            </div>
            <div class="close-fullscreen no-print">&times;</div>
            <div id="fullscreen-certificate"></div>
        </div>
        
        <!-- Main Footer -->
        <footer class="main-footer">
            <div class="float-right d-none d-sm-block">
                <b>Version</b> 1.0.0
            </div>
            <strong>Copyright &copy; <?php echo date('Y'); ?> <a href="../../index.php">Artwork Auth</a>.</strong> Tüm hakları saklıdır.
        </footer>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- jQuery UI -->
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <!-- Color Picker -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-colorpicker@3.4.0/dist/js/bootstrap-colorpicker.min.js"></script>
    <!-- bs-custom-file-input -->
    <script src="https://cdn.jsdelivr.net/npm/bs-custom-file-input@1.3.4/dist/bs-custom-file-input.min.js"></script>
    
    <script>
    $(function () {
        bsCustomFileInput.init();
        
        // Renk seçiciler
        $('#certificate_background_color').colorpicker();
        $('#certificate_text_color').colorpicker();
        
        // Sertifika elemanlarının varsayılan pozisyonları
        var defaultPositions = {
            logo: { top: 20, left: '50%', transform: 'translateX(-50%)' },
            title: { top: 120, left: '50%', transform: 'translateX(-50%)' },
            content: { top: 200, left: '50%', transform: 'translateX(-50%)' },
            'artwork-image': { top: 350, left: '50%', transform: 'translateX(-50%)' },
            qr: { top: 600, left: '50%', transform: 'translateX(-50%)' },
            footer: { bottom: 20, left: '50%', transform: 'translateX(-50%)' }
        };
        
        // Kaydedilmiş pozisyonları yükle
        var savedPositions = {};
        try {
            savedPositions = JSON.parse($('#elements_positions').val()) || {};
        } catch (e) {
            savedPositions = {};
        }
        
        // Tasarım modu aktif mi?
        var designMode = false;
        var isFullscreen = false;
        
        // Elemanları sürüklenebilir ve boyutlandırılabilir yap
        function enableDesignMode(container) {
            // Tasarım modunu aç
            container.addClass('editor-active');
            
            // Sürüklenebilir ve yeniden boyutlandırılabilir yap
            container.find('.certificate-element').each(function() {
                var $el = $(this);
                var elName = $el.data('element');
                
                // Eğer kaydedilmiş pozisyon varsa uygula
                if (savedPositions[elName]) {
                    $el.css({
                        position: 'absolute',
                        top: savedPositions[elName].top,
                        left: savedPositions[elName].left,
                        width: savedPositions[elName].width || 'auto',
                        height: savedPositions[elName].height || 'auto',
                        transform: 'none',
                        margin: 0
                    });
                } else if (defaultPositions[elName]) {
                    // Varsayılan transform değerlerini geçici olarak uygula
                    var defaultTop = defaultPositions[elName].top;
                    var defaultLeft = defaultPositions[elName].left;
                    
                    // Eğer varsayılan pozisyonda transform varsa, gerçek piksel pozisyonuna çevir
                    if (defaultPositions[elName].transform && defaultPositions[elName].transform.includes('translateX(-50%)')) {
                        var parentWidth = container.width();
                        // Merkezi hesapla
                        if (defaultLeft === '50%') {
                            defaultLeft = parentWidth / 2 - $el.width() / 2;
                        }
                    }
                    
                    $el.css({
                        position: 'absolute',
                        top: defaultTop,
                        left: defaultLeft,
                        transform: 'none',
                        margin: 0
                    });
                }
                
                // z-index değerini arttır
                $el.css('z-index', 10);
                
                // Önceki sürüklenebilir ve yeniden boyutlandırılabilir özelliklerini kaldır
                if ($el.hasClass('ui-draggable')) {
                    $el.draggable('destroy');
                }
                if ($el.hasClass('ui-resizable')) {
                    $el.resizable('destroy');
                }
                
                // Sürüklenebilir yap - Containment sınırını container olarak ayarla
                $el.draggable({
                    containment: container,
                    scroll: false,
                    handle: "",
                    snap: false,
                    grid: false,
                    start: function(event, ui) {
                        // Sürükleme başladığında transform değerini sıfırla
                        $(this).css({
                            'transform': 'none',
                            'margin': 0
                        });
                    },
                    stop: function(event, ui) {
                        // Konumu güncelle
                        updateElementPosition(elName, ui.position);
                    }
                });
                
                // Yeniden boyutlandırılabilir yap
                $el.resizable({
                    handles: 'all',
                    minWidth: 50,
                    minHeight: 50,
                    containment: container,
                    stop: function(event, ui) {
                        // Boyutu güncelle
                        updateElementSize(elName, ui.size);
                    }
                });
            });
        }
        
        // Tasarım modunu devre dışı bırak
        function disableDesignMode(container) {
            container.removeClass('editor-active');
            
            // Sürüklenebilir ve yeniden boyutlandırılabilir özelliklerini kaldır
            container.find('.certificate-element').each(function() {
                if ($(this).hasClass('ui-draggable')) {
                    $(this).draggable('destroy');
                }
                if ($(this).hasClass('ui-resizable')) {
                    $(this).resizable('destroy');
                }
            });
        }
        
        // Tasarım modunu aç/kapat
        $('.design-mode-toggle').on('click', function() {
            designMode = !designMode;
            
            if (designMode) {
                $('.design-mode-toggle').text('Tasarım Modunu Kapat');
                $('.save-positions, .reset-positions').show();
                enableDesignMode($('#certificate_preview'));
            } else {
                $('.design-mode-toggle').text('Tasarım Modunu Aç');
                $('.save-positions, .reset-positions').hide();
                disableDesignMode($('#certificate_preview'));
            }
        });
        
        // Tam ekran modu için tasarım modunu aç/kapat
        $('.fullscreen-design-mode').on('click', function() {
            var $fullscreenCert = $('#fullscreen-certificate');
            
            if ($fullscreenCert.hasClass('editor-active')) {
                $(this).text('Tasarım Modu');
                disableDesignMode($fullscreenCert);
            } else {
                $(this).text('Tasarım Modunu Kapat');
                enableDesignMode($fullscreenCert);
            }
        });
        
        // Tam ekran modunda yazdır
        $('.fullscreen-print').on('click', function() {
            window.print();
        });
        
        // Eleman pozisyonunu güncelle
        function updateElementPosition(element, position) {
            if (!savedPositions[element]) {
                savedPositions[element] = {};
            }
            savedPositions[element].top = position.top;
            savedPositions[element].left = position.left;
            savedPositions[element].transform = 'none';
            savedPositions[element].margin = 0;
            
            // Hidden input'u güncelle
            $('#elements_positions').val(JSON.stringify(savedPositions));
        }
        
        // Eleman boyutunu güncelle
        function updateElementSize(element, size) {
            if (!savedPositions[element]) {
                savedPositions[element] = {};
            }
            savedPositions[element].width = size.width;
            savedPositions[element].height = size.height;
            
            // Hidden input'u güncelle
            $('#elements_positions').val(JSON.stringify(savedPositions));
        }
        
        // Pozisyonları kaydet
        $('.save-positions').on('click', function() {
            alert('Pozisyonlar kaydedildi. Değişiklikleri kaydetmek için sayfanın altındaki "Kaydet" butonuna tıklayın.');
        });
        
        // Pozisyonları sıfırla
        $('.reset-positions').on('click', function() {
            if (confirm('Tüm pozisyonları sıfırlamak istediğinizden emin misiniz?')) {
                savedPositions = {};
                $('#elements_positions').val('{}');
                
                if ($('#certificate_preview').hasClass('editor-active')) {
                    disableDesignMode($('#certificate_preview'));
                    enableDesignMode($('#certificate_preview'));
                }
                
                if ($('#fullscreen-certificate').hasClass('editor-active')) {
                    disableDesignMode($('#fullscreen-certificate'));
                    enableDesignMode($('#fullscreen-certificate'));
                }
            }
        });
        
        // Tam ekran modunu aç/kapat
        $('.fullscreen-toggle').on('click', function() {
            openFullscreen();
        });
        
        // Tam ekran aç
        function openFullscreen() {
            var previewContent = $('#certificate_preview').html();
            $('#fullscreen-certificate').html(previewContent);
            $('#fullscreen-container').fadeIn(300);
            $('body').css('overflow', 'hidden');
            isFullscreen = true;
            
            // Tam ekranda öğelerin stil ve pozisyonlarını ayarla
            var bgColor = $('#certificate_background_color').val();
            var textColor = $('#certificate_text_color').val();
            var font = $('#certificate_font').val();
            
            $('#fullscreen-certificate').css({
                'background-color': bgColor,
                'color': textColor,
                'font-family': font + ', sans-serif'
            });
            
            // Arka plan görüntüsü
            if ($('#use_background_image').is(':checked')) {
                <?php if (!empty($background_image)): ?>
                $('#fullscreen-certificate').css({
                    'background-image': 'url(../../<?php echo $background_image; ?>)',
                    'background-size': 'cover',
                    'background-position': 'center',
                    'background-repeat': 'no-repeat'
                });
                <?php endif; ?>
            }
            
            // Kaydedilmiş pozisyonları uygula
            $('#fullscreen-certificate .certificate-element').each(function() {
                var elName = $(this).data('element');
                if (savedPositions[elName]) {
                    $(this).css({
                        position: 'absolute',
                        top: savedPositions[elName].top,
                        left: savedPositions[elName].left,
                        width: savedPositions[elName].width || 'auto',
                        height: savedPositions[elName].height || 'auto',
                        transform: 'none',
                        margin: 0
                    });
                }
            });
        }
        
        // Tam ekran kapat
        $('.close-fullscreen').on('click', function() {
            closeFullscreen();
        });
        
        // ESC tuşu ile tam ekrandan çık
        $(document).on('keydown', function(e) {
            if (e.key === "Escape" && isFullscreen) {
                closeFullscreen();
            }
        });
        
        // Tam ekran kapat fonksiyonu
        function closeFullscreen() {
            $('#fullscreen-container').fadeOut(300);
            $('body').css('overflow', 'auto');
            isFullscreen = false;
            
            // Tam ekran tasarım modunu kapat
            if ($('#fullscreen-certificate').hasClass('editor-active')) {
                disableDesignMode($('#fullscreen-certificate'));
                $('.fullscreen-design-mode').text('Tasarım Modu');
            }
        }
        
        // Canlı önizleme değişiklikleri
        $('#certificate_title, #certificate_footer, #certificate_background_color, #certificate_text_color, #certificate_font, #show_qr_code, #show_logo, #paper_size, #use_background_image').on('change input', updatePreview);
        
        function updatePreview() {
            var preview = $('#certificate_preview');
            var title = $('#certificate_title').val();
            var footer = $('#certificate_footer').val();
            var bgColor = $('#certificate_background_color').val();
            var textColor = $('#certificate_text_color').val();
            var font = $('#certificate_font').val();
            var showQR = $('#show_qr_code').is(':checked');
            var showLogo = $('#show_logo').is(':checked');
            var paperSize = $('#paper_size').val();
            var useBackgroundImage = $('#use_background_image').is(':checked');
            
            // Kağıt boyutuna göre en-boy oranını ayarla
            var ratio = 1;
            if (paperSize === 'A4') {
                ratio = 210/297; // A4 en-boy oranı
                preview.css('width', '100%').css('height', preview.width() / ratio + 'px');
            } else if (paperSize === 'A5') {
                ratio = 148/210; // A5 en-boy oranı
                preview.css('width', '80%').css('height', preview.width() / ratio + 'px');
            } else if (paperSize === 'A6') {
                ratio = 105/148; // A6 en-boy oranı
                preview.css('width', '60%').css('height', preview.width() / ratio + 'px');
            } else if (paperSize === 'Letter') {
                ratio = 216/279; // Letter en-boy oranı
                preview.css('width', '100%').css('height', preview.width() / ratio + 'px');
            }
            
            // Arka plan görüntüsü veya renk ayarla
            if (useBackgroundImage) {
                // Arka plan görüntüsü var ve kullanım açık ise
                <?php if (!empty($background_image)): ?>
                preview.css({
                    'background-image': 'url(<?php echo "../../" . $background_image; ?>)',
                    'background-size': 'cover',
                    'background-position': 'center',
                    'background-repeat': 'no-repeat',
                    'background-color': 'transparent',
                    'color': textColor,
                    'font-family': font + ', sans-serif'
                });
                <?php else: ?>
                preview.css({
                    'background-image': 'none',
                    'background-color': bgColor,
                    'color': textColor,
                    'font-family': font + ', sans-serif'
                });
                <?php endif; ?>
            } else {
                // Sadece renk kullan
                preview.css({
                    'background-image': 'none',
                    'background-color': bgColor,
                    'color': textColor,
                    'font-family': font + ', sans-serif'
                });
            }
            
            preview.find('.title').text(title);
            preview.find('.footer').text(footer);
            
            if (showQR) {
                preview.find('.qr-container').show();
            } else {
                preview.find('.qr-container').hide();
            }
            
            if (showLogo) {
                preview.find('.logo-container').show();
            } else {
                preview.find('.logo-container').hide();
            }
            
            // Eğer tam ekran modundaysak, tam ekrandaki sertifikayı da güncelle
            if (isFullscreen) {
                var fullscreenPreview = $('#fullscreen-certificate');
                fullscreenPreview.css({
                    'background-color': bgColor,
                    'color': textColor,
                    'font-family': font + ', sans-serif'
                });
                
                if (useBackgroundImage) {
                    <?php if (!empty($background_image)): ?>
                    fullscreenPreview.css({
                        'background-image': 'url(<?php echo "../../" . $background_image; ?>)',
                        'background-size': 'cover',
                        'background-position': 'center',
                        'background-repeat': 'no-repeat',
                        'background-color': 'transparent'
                    });
                    <?php endif; ?>
                } else {
                    fullscreenPreview.css({
                        'background-image': 'none',
                        'background-color': bgColor
                    });
                }
                
                fullscreenPreview.find('.title').text(title);
                fullscreenPreview.find('.footer').text(footer);
                
                if (showQR) {
                    fullscreenPreview.find('.qr-container').show();
                } else {
                    fullscreenPreview.find('.qr-container').hide();
                }
                
                if (showLogo) {
                    fullscreenPreview.find('.logo-container').show();
                } else {
                    fullscreenPreview.find('.logo-container').hide();
                }
            }
        }
        
        // Şablon seçimi
        $('.template-option').on('click', function() {
            $('.template-option').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
            
            var template = $(this).data('template');
            if (template === 'classic') {
                $('#certificate_background_color').val('#ffffff').trigger('change');
                $('#certificate_text_color').val('#000000').trigger('change');
                $('#certificate_font').val('Times New Roman').trigger('change');
                $('#use_background_image').prop('checked', false).trigger('change');
            } else if (template === 'modern') {
                $('#certificate_background_color').val('#f0f7ff').trigger('change');
                $('#certificate_text_color').val('#333333').trigger('change');
                $('#certificate_font').val('Arial').trigger('change');
                $('#use_background_image').prop('checked', false).trigger('change');
            } else if (template === 'minimalist') {
                $('#certificate_background_color').val('#f8f9fa').trigger('change');
                $('#certificate_text_color').val('#212529').trigger('change');
                $('#certificate_font').val('Helvetica').trigger('change');
                $('#use_background_image').prop('checked', false).trigger('change');
            }
            
            updatePreview();
        });
        
        // Yazdırma önizlemesi
        $('#print_preview_btn').on('click', function() {
            openFullscreen();
            
            // Bilgilendirme
            setTimeout(function() {
                alert('Yazdırmak için üst kısımdaki "Yazdır" butonuna tıklayabilirsiniz.');
            }, 1000);
        });
        
        // Sayfanın başlangıcında önizlemeyi güncelle
        updatePreview();
    });
    </script>
</body>
</html>