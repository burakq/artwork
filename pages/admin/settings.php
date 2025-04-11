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

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $whatsapp_number = isset($_POST['whatsapp_number']) ? sanitize($_POST['whatsapp_number']) : '';
    $site_title = isset($_POST['site_title']) ? sanitize($_POST['site_title']) : '';
    $site_description = isset($_POST['site_description']) ? sanitize($_POST['site_description']) : '';
    $allowed_iframe_domains = isset($_POST['allowed_iframe_domains']) ? sanitize($_POST['allowed_iframe_domains']) : '';
    
    // WhatsApp numarasını güncelle
    $stmt = $conn->prepare("SELECT id FROM settings WHERE setting_key = 'whatsapp_number'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Güncelle
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'whatsapp_number'");
        $stmt->bind_param("s", $whatsapp_number);
    } else {
        // Ekle
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES ('whatsapp_number', ?, NOW(), NOW())");
        $stmt->bind_param("s", $whatsapp_number);
    }
    $stmt->execute();
    
    // Site başlığını güncelle
    $stmt = $conn->prepare("SELECT id FROM settings WHERE setting_key = 'site_title'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'site_title'");
        $stmt->bind_param("s", $site_title);
    } else {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES ('site_title', ?, NOW(), NOW())");
        $stmt->bind_param("s", $site_title);
    }
    $stmt->execute();
    
    // Site açıklamasını güncelle
    $stmt = $conn->prepare("SELECT id FROM settings WHERE setting_key = 'site_description'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'site_description'");
        $stmt->bind_param("s", $site_description);
    } else {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES ('site_description', ?, NOW(), NOW())");
        $stmt->bind_param("s", $site_description);
    }
    $stmt->execute();
    
    // İzin verilen iframe domainlerini güncelle
    $stmt = $conn->prepare("SELECT id FROM settings WHERE setting_key = 'allowed_iframe_domains'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'allowed_iframe_domains'");
        $stmt->bind_param("s", $allowed_iframe_domains);
    } else {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES ('allowed_iframe_domains', ?, NOW(), NOW())");
        $stmt->bind_param("s", $allowed_iframe_domains);
    }
    $stmt->execute();
    
    $success_message = "Ayarlar başarıyla güncellendi.";
}

// Ayarları getir
$settings = [];

$query = "SELECT setting_key, setting_value FROM settings";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Varsayılan değerler
$whatsapp_number = isset($settings['whatsapp_number']) ? $settings['whatsapp_number'] : '905301234567';
$site_title = isset($settings['site_title']) ? $settings['site_title'] : 'Artwork Auth';
$site_description = isset($settings['site_description']) ? $settings['site_description'] : 'Sanat eserlerinin orijinalliğini doğrulama ve satış platformu.';
$allowed_iframe_domains = isset($settings['allowed_iframe_domains']) ? $settings['allowed_iframe_domains'] : '';

// Sunucu adresi
$server_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

// Bağlantıyı kapat
$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ayarlar - Artwork Auth Yönetim Paneli</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
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
                            <h1>Sistem Ayarları</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="dashboard.php">Anasayfa</a></li>
                                <li class="breadcrumb-item active">Ayarlar</li>
                            </ol>
                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Mesajlar -->
                    <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-check"></i> Başarılı!</h5>
                        <?php echo $success_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-ban"></i> Hata!</h5>
                        <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Site Ayarları</h3>
                                </div>
                                <!-- /.card-header -->
                                <!-- form start -->
                                <form method="post" action="">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="whatsapp_number">WhatsApp Numarası <small>(Uluslararası formatta, örn: 905301234567)</small></label>
                                            <input type="text" class="form-control" id="whatsapp_number" name="whatsapp_number" value="<?php echo htmlspecialchars($whatsapp_number); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="site_title">Site Başlığı</label>
                                            <input type="text" class="form-control" id="site_title" name="site_title" value="<?php echo htmlspecialchars($site_title); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="site_description">Site Açıklaması</label>
                                            <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($site_description); ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="allowed_iframe_domains">İzin Verilen iframe Domainleri <small>(Her satıra bir domain)</small></label>
                                            <textarea class="form-control" id="allowed_iframe_domains" name="allowed_iframe_domains" rows="4" placeholder="example.com&#10;sub.example.com"><?php echo htmlspecialchars($allowed_iframe_domains); ?></textarea>
                                            <small class="form-text text-muted">Siteyi iframe olarak gösterebilecek domain adlarını yazın. Boş bırakırsanız hiçbir site iframe olarak gösteremez.</small>
                                        </div>
                                    </div>
                                    <!-- /.card-body -->

                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">Ayarları Kaydet</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title">iframe Entegrasyon Kodları</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Doğrulama Sayfası iframe Kodu</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="verification_iframe" value="<iframe src=&quot;<?php echo $server_url; ?>/verify.php&quot; width=&quot;100%&quot; height=&quot;600&quot; frameborder=&quot;0&quot;></iframe>" readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('verification_iframe')"><i class="fas fa-copy"></i> Kopyala</button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Bu kodu web sitenize ekleyerek doğrulama sayfasını gösterebilirsiniz.</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Satılık Eserler iframe Kodu</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="forsale_iframe" value="<iframe src=&quot;<?php echo $server_url; ?>/for_sale.php&quot; width=&quot;100%&quot; height=&quot;800&quot; frameborder=&quot;0&quot;></iframe>" readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('forsale_iframe')"><i class="fas fa-copy"></i> Kopyala</button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Bu kodu web sitenize ekleyerek satılık eserler sayfasını gösterebilirsiniz.</small>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <h5><i class="icon fas fa-exclamation-triangle"></i> Dikkat!</h5>
                                        <p>Bu kodları kullanabilmek için, yukarıdaki ayarlar bölümünden iframe gösterimini etkinleştirmelisiniz.</p>
                                        <p>iframe'in çalışabilmesi için, sitenizin domain adını "İzin Verilen iframe Domainleri" kutusuna eklemelisiniz.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- GitHub Güncelleme Kartı -->
                            <div class="card card-success mt-4">
                                <div class="card-header">
                                    <h3 class="card-title">GitHub Güncellemeleri</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>GitHub Güncelleme Durumu</label>
                                        <div id="github-status" class="alert alert-info">
                                            <i class="fas fa-sync fa-spin"></i> Güncelleme durumu kontrol ediliyor...
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <button type="button" class="btn btn-success btn-block" id="check-updates">
                                            <i class="fas fa-sync"></i> Güncellemeleri Kontrol Et
                                        </button>
                                        <button type="button" class="btn btn-primary btn-block mt-2" id="apply-updates" style="display: none;">
                                            <i class="fas fa-download"></i> Güncellemeleri Uygula
                                        </button>
                                    </div>
                                    <div class="alert alert-warning">
                                        <h5><i class="icon fas fa-exclamation-triangle"></i> Önemli!</h5>
                                        <p>Güncelleme yapmadan önce tüm değişikliklerinizi kaydettiğinizden emin olun.</p>
                                        <p>Güncelleme sırasında sistem geçici olarak kullanılamaz hale gelebilir.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- MySQL Yedek Kartı -->
                            <div class="card card-warning mt-4">
                                <div class="card-header">
                                    <h3 class="card-title">MySQL Yedek İndirme</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Veritabanı Yedeği</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="backup_password" placeholder="Yedek şifresini girin">
                                            <div class="input-group-append">
                                                <button class="btn btn-warning" type="button" id="download-backup">
                                                    <i class="fas fa-download"></i> Yedeği İndir
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Yedek almak için şifre girin ve "Yedeği İndir" butonuna tıklayın.</small>
                                    </div>
                                    <div class="alert alert-info">
                                        <h5><i class="icon fas fa-info"></i> Bilgi</h5>
                                        <p>Yedek dosyası, veritabanının tam bir kopyasını içerir.</p>
                                        <p>Yedek almak için ana admin şifrenizi veya yedek şifresini kullanabilirsiniz.</p>
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
    <!-- Add custom script for copying to clipboard -->
    <script>
        function copyToClipboard(elementId) {
            var copyText = document.getElementById(elementId);
            copyText.select();
            document.execCommand("copy");
            alert("Kod panoya kopyalandı!");
        }
        
        $(document).ready(function() {
            // Pushmenu toggle
            $("[data-widget='pushmenu']").on('click', function() {
                $('body').toggleClass('sidebar-collapse');
            });
            
            // GitHub güncelleme kontrolü
            function checkGitHubUpdates() {
                $('#github-status').html('<i class="fas fa-sync fa-spin"></i> Güncelleme durumu kontrol ediliyor...');
                $('#github-status').removeClass('alert-success alert-danger').addClass('alert-info');
                
                $.ajax({
                    url: 'check_github_updates.php',
                    method: 'GET',
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.hasUpdates) {
                                $('#github-status').html('<i class="fas fa-exclamation-circle"></i> Yeni güncelleme mevcut: ' + data.latestVersion);
                                $('#github-status').removeClass('alert-info').addClass('alert-success');
                                $('#apply-updates').show();
                            } else {
                                $('#github-status').html('<i class="fas fa-check-circle"></i> Sistem güncel. Son sürüm: ' + data.currentVersion);
                                $('#github-status').removeClass('alert-info').addClass('alert-success');
                                $('#apply-updates').hide();
                            }
                        } catch (e) {
                            $('#github-status').html('<i class="fas fa-times-circle"></i> Güncelleme kontrolü sırasında bir hata oluştu.');
                            $('#github-status').removeClass('alert-info').addClass('alert-danger');
                        }
                    },
                    error: function() {
                        $('#github-status').html('<i class="fas fa-times-circle"></i> Güncelleme kontrolü sırasında bir hata oluştu.');
                        $('#github-status').removeClass('alert-info').addClass('alert-danger');
                    }
                });
            }
            
            // Güncellemeleri kontrol et butonu
            $('#check-updates').on('click', function() {
                checkGitHubUpdates();
            });
            
            // Güncellemeleri uygula butonu
            $('#apply-updates').on('click', function() {
                if (confirm('Güncellemeleri uygulamak istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
                    $('#github-status').html('<i class="fas fa-sync fa-spin"></i> Güncellemeler uygulanıyor...');
                    $('#github-status').removeClass('alert-success alert-danger').addClass('alert-info');
                    $('#check-updates, #apply-updates').prop('disabled', true);
                    
                    $.ajax({
                        url: 'apply_github_updates.php',
                        method: 'POST',
                        success: function(response) {
                            try {
                                const data = JSON.parse(response);
                                if (data.success) {
                                    $('#github-status').html('<i class="fas fa-check-circle"></i> Güncellemeler başarıyla uygulandı. Sayfa yenilenecek...');
                                    $('#github-status').removeClass('alert-info').addClass('alert-success');
                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 3000);
                                } else {
                                    $('#github-status').html('<i class="fas fa-times-circle"></i> Güncelleme sırasında bir hata oluştu: ' + data.message);
                                    $('#github-status').removeClass('alert-info').addClass('alert-danger');
                                    $('#check-updates, #apply-updates').prop('disabled', false);
                                }
                            } catch (e) {
                                $('#github-status').html('<i class="fas fa-times-circle"></i> Güncelleme sırasında bir hata oluştu.');
                                $('#github-status').removeClass('alert-info').addClass('alert-danger');
                                $('#check-updates, #apply-updates').prop('disabled', false);
                            }
                        },
                        error: function() {
                            $('#github-status').html('<i class="fas fa-times-circle"></i> Güncelleme sırasında bir hata oluştu.');
                            $('#github-status').removeClass('alert-info').addClass('alert-danger');
                            $('#check-updates, #apply-updates').prop('disabled', false);
                        }
                    });
                }
            });
            
            // Yedek indirme butonu
            $('#download-backup').on('click', function() {
                var password = $('#backup_password').val();
                if (!password) {
                    alert('Lütfen yedek şifresini girin.');
                    return;
                }
                
                // Yedek indirme isteği
                window.location.href = 'download_backup.php?password=' + encodeURIComponent(password);
            });
            
            // Sayfa yüklendiğinde güncelleme durumunu kontrol et
            checkGitHubUpdates();
        });
    </script>
</body>
</html> 