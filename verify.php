<?php
require_once 'includes/functions.php';

// Veritabanı bağlantısı
$conn = connectDB();

$artwork = null;
$message = '';
$message_type = '';

// Doğrulama kodu kontrol et
if (isset($_GET['code']) && !empty($_GET['code'])) {
    $code = sanitize($_GET['code']);
    
    // Eseri getir
    $stmt = $conn->prepare("SELECT * FROM artworks WHERE verification_code = ? AND deleted_at IS NULL");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $artwork = $result->fetch_assoc();
        $message = "Eser başarıyla doğrulandı. Bu eser orijinaldir.";
        $message_type = "success";
        
        // Doğrulama log kaydı
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        // Browser, platform ve device bilgilerini ayarla
        $browser = '';
        $platform = '';
        $device_type = 'unknown';
        
        // Mobil cihaz kontrolü
        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $user_agent)) {
            $device_type = 'mobile';
        } elseif (preg_match('/tablet|ipad|playbook|silk/i', $user_agent)) {
            $device_type = 'tablet';
        } else {
            $device_type = 'desktop';
        }
        
        // Browser tespiti
        if (strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Trident') !== false) {
            $browser = 'Internet Explorer';
        } elseif (strpos($user_agent, 'Edge') !== false) {
            $browser = 'Microsoft Edge';
        } elseif (strpos($user_agent, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (strpos($user_agent, 'Safari') !== false) {
            $browser = 'Safari';
        } elseif (strpos($user_agent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (strpos($user_agent, 'Opera') !== false || strpos($user_agent, 'OPR') !== false) {
            $browser = 'Opera';
        }
        
        // Platform tespiti
        if (strpos($user_agent, 'Windows') !== false) {
            $platform = 'Windows';
        } elseif (strpos($user_agent, 'Macintosh') !== false) {
            $platform = 'Mac OS';
        } elseif (strpos($user_agent, 'Linux') !== false) {
            $platform = 'Linux';
        } elseif (strpos($user_agent, 'iPhone') !== false) {
            $platform = 'iOS';
        } elseif (strpos($user_agent, 'Android') !== false) {
            $platform = 'Android';
        }
        
        // Başarılı doğrulama log kaydı
        $log_stmt = $conn->prepare("INSERT INTO verification_logs (artwork_id, verification_code, ip_address, user_agent, device_type, browser, platform, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $log_stmt->bind_param("issssss", $artwork['id'], $code, $ip, $user_agent, $device_type, $browser, $platform);
        $log_result = $log_stmt->execute();
        $log_stmt->close();
        
        if (!$log_result) {
            error_log("Log kaydı oluşturulurken hata: " . $conn->error);
        }
        
    } else {
        // Eski kodlarda arama kaldırıldı
        $message = "<strong>DİKKAT!</strong> Bu doğrulama kodu sistemimizde kayıtlı değildir. Bu eser muhtemelen <strong>SAHTE</strong> olabilir.";
        $message_type = "danger";
        
        // Geçersiz doğrulama kodları için sadece error_log kaydı yapılıyor
        error_log("Geçersiz doğrulama kodu denemesi: " . $code . " | IP: " . $_SERVER['REMOTE_ADDR'] . " | User-Agent: " . $_SERVER['HTTP_USER_AGENT']);
    }
    
    $stmt->close();
}

// Bağlantıyı kapat
$conn->close();

// Meta açıklaması için
$meta_description = "Artwork Auth - Sanat eserlerinin orijinalliğini doğrulama sistemi. Eser doğrulama kodunu girerek sanat eserinin gerçekliğini kontrol edebilirsiniz.";
if ($artwork) {
    $meta_description = "Sanat eseri '{$artwork['title']}' - {$artwork['artist_name']} tarafından. Doğrulama kodu: {$artwork['verification_code']}";
}

// Başka bir dış domaine ait resim kullanıldığını belirten uyarı
$using_proxy = false;
if ($artwork && strpos($artwork['image_path'], 'image_proxy.php') !== false) {
    $using_proxy = true;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $meta_description; ?>">
    <title>Artwork Auth - <?php echo $artwork ? "Eser Doğrulaması: {$artwork['title']}" : "Sanat Eseri Doğrulama"; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 60px;
        }
        .verification-container {
            max-width: 800px;
            margin: 30px auto;
        }
        .verification-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .verification-card {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .verification-image {
            max-height: 400px;
            object-fit: contain;
            width: 100%;
        }
        .verification-details {
            padding: 20px;
        }
        .verification-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 10px 15px;
            border-radius: 30px;
            font-weight: bold;
            z-index: 10;
        }
        .search-form {
            max-width: 500px;
            margin: 0 auto 30px;
        }
        .qr-info {
            margin-top: 20px;
            text-align: center;
        }
        .verification-success {
            color: #28a745;
            font-weight: bold;
        }
        .verification-error {
            color: #dc3545;
            font-weight: bold;
        }
        .fake-artwork-warning {
            background-color: #dc3545;
            color: white;
            text-align: center;
            padding: 30px;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(220, 53, 69, 0.5);
        }
        .fake-artwork-warning h2 {
            font-size: 3rem;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        .fake-artwork-warning p {
            font-size: 1.2rem;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .navbar-brand img {
            height: 30px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <!-- Üst Menü -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-palette"></i> Artwork Auth
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'verify.php' ? 'active' : ''; ?>" href="verify.php">Eser Doğrulama</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'for_sale.php' ? 'active' : ''; ?>" href="for_sale.php">Satıştaki Eserler</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Yönetici Girişi</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container verification-container">
        <?php if ($using_proxy): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <small><i class="fas fa-info-circle"></i> Bu sayfa harici bir kaynaktan resim görüntülüyor. Görüntülenme sorunları olursa lütfen yöneticiye bildirin.</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="verification-header">
            <h1 class="mb-2">Sanat Eseri Doğrulama</h1>
            <p class="text-muted">Eser doğrulama kodunu girerek sanat eserinin orijinalliğini doğrulayabilirsiniz.</p>
        </div>
        
        <div class="search-form">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="get">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" name="code" placeholder="Doğrulama kodu giriniz" value="<?php echo isset($_GET['code']) ? htmlspecialchars($_GET['code']) : ''; ?>" required>
                    <button class="btn btn-primary" type="submit">Doğrula</button>
                </div>
            </form>
        </div>
        
        <?php if (isset($_GET['code']) && !empty($_GET['code']) && !$artwork): ?>
        <div class="fake-artwork-warning">
            <h2><i class="fas fa-exclamation-triangle"></i> SAHTE ESER</h2>
            <p>Bu doğrulama kodu (<?php echo htmlspecialchars($_GET['code']); ?>) sistemimizde bulunmamaktadır.</p>
            <p>Bu eser sahte olabilir veya doğrulama kodu yanlış girilmiş olabilir.</p>
            <p>Satın alma işlemi yapmadan önce mutlaka eserin gerçekliğini doğrulayınız.</p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($artwork): ?>
        <div class="card verification-card">
            <?php if ($artwork['status'] == 'original' || $artwork['status'] == 'for_sale' || $artwork['status'] == 'sold'): ?>
                <div class="verification-badge bg-success text-white">ORİJİNAL</div>
            <?php elseif ($artwork['status'] == 'fake'): ?>
                <div class="verification-badge bg-danger text-white">SAHTE</div>
            <?php endif; ?>
            
            <div class="row g-0">
                <div class="col-md-6">
                    <?php if (!empty($artwork['image_path'])): ?>
                        <img src="<?php echo $artwork['image_path']; ?>" alt="<?php echo $artwork['title']; ?>" class="verification-image">
                    <?php else: ?>
                        <div class="text-center p-5 bg-light">
                            <i class="fas fa-image fa-5x text-muted"></i>
                            <p class="mt-3">Görsel mevcut değil</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <div class="verification-details">
                        <h3><?php echo $artwork['title']; ?></h3>
                        <p class="text-muted">Sanatçı: <?php echo $artwork['artist_name']; ?></p>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-6">
                                <strong>Doğrulama Kodu:</strong>
                            </div>
                            <div class="col-6">
                                <?php echo $artwork['verification_code']; ?>
                            </div>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-6">
                                <strong>Durum:</strong>
                            </div>
                            <div class="col-6">
                                <?php
                                switch ($artwork['status']) {
                                    case 'original':
                                        echo '<span class="badge bg-primary">Orijinal</span>';
                                        break;
                                    case 'for_sale':
                                        echo '<span class="badge bg-success">Satışta</span>';
                                        break;
                                    case 'sold':
                                        echo '<span class="badge bg-warning text-dark">Satıldı</span>';
                                        break;
                                    case 'fake':
                                        echo '<span class="badge bg-danger">Sahte</span>';
                                        break;
                                    case 'archived':
                                        echo '<span class="badge bg-info">Arşivlendi</span>';
                                        break;
                                    default:
                                        echo $artwork['status'];
                                }
                                ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($artwork['year'])): ?>
                        <div class="row mt-2">
                            <div class="col-6">
                                <strong>Yapım Yılı:</strong>
                            </div>
                            <div class="col-6">
                                <?php echo $artwork['year']; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($artwork['technique'])): ?>
                        <div class="row mt-2">
                            <div class="col-6">
                                <strong>Teknik:</strong>
                            </div>
                            <div class="col-6">
                                <?php echo $artwork['technique']; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        $dimensions = [];
                        if (!empty($artwork['width'])) $dimensions[] = "Genişlik: {$artwork['width']} {$artwork['dimension_unit']}";
                        if (!empty($artwork['height'])) $dimensions[] = "Yükseklik: {$artwork['height']} {$artwork['dimension_unit']}";
                        if (!empty($artwork['depth'])) $dimensions[] = "Derinlik: {$artwork['depth']} {$artwork['dimension_unit']}";
                        
                        if (!empty($dimensions)):
                        ?>
                        <div class="row mt-2">
                            <div class="col-6">
                                <strong>Boyutlar:</strong>
                            </div>
                            <div class="col-6">
                                <?php echo implode(', ', $dimensions); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <?php if (!empty($artwork['description'])): ?>
                        <h5>Açıklama</h5>
                        <p><?php echo nl2br($artwork['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="mt-4 text-center">
                            <div class="alert alert-info">
                                Bu sanat eseri <?php echo date('d.m.Y', strtotime($artwork['created_at'])); ?> tarihinde kayıt altına alınmıştır.
                            </div>
                            
                            <!-- Paylaşım bağlantıları -->
                            <div class="mt-3">
                                <p><strong>Bu doğrulama sonucunu paylaşın:</strong></p>
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode('Bu sanat eseri Artwork Auth tarafından doğrulanmıştır: '); ?>&url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-sm btn-info">
                                        <i class="fab fa-twitter"></i> Twitter
                                    </a>
                                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fab fa-facebook"></i> Facebook
                                    </a>
                                    <a href="mailto:?subject=<?php echo urlencode('Sanat Eseri Doğrulama: ' . $artwork['title']); ?>&body=<?php echo urlencode('Bu sanat eseri Artwork Auth tarafından doğrulanmıştır. Detayları görüntülemek için: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-envelope"></i> E-posta
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="qr-info mt-4">
            <p>Bu eserin doğrulama sayfasına direkt erişim için aşağıdaki bağlantıyı kullanabilirsiniz:</p>
            <div class="input-group mb-3">
                <input type="text" class="form-control" value="https://<?php echo $_SERVER['HTTP_HOST']; ?>/verify.php?code=<?php echo $artwork['verification_code']; ?>" id="verification-link" readonly>
                <button class="btn btn-outline-secondary" type="button" onclick="copyVerificationLink()">Kopyala</button>
            </div>
        </div>
        <?php else: ?>
        <div class="card p-4 mb-4">
            <h4>Doğrulama Nasıl Çalışır?</h4>
            <ol>
                <li>Her sanat eseri benzersiz bir doğrulama kodu ile kayıt altına alınır.</li>
                <li>Eser üzerindeki QR kodu tarayarak veya doğrulama kodunu girerek eserin orijinalliğini kontrol edebilirsiniz.</li>
                <li>Doğrulama sistemi, eserin tüm detaylarını ve gerçekliğini anında gösterir.</li>
                <li>Tüm doğrulama işlemleri güvenlik için kayıt altına alınır.</li>
            </ol>
            <div class="alert alert-warning">
                <strong>Dikkat:</strong> Sanat eserlerini satın alırken doğrulama kodunu mutlaka kontrol ediniz. Sahte eserler için yasal işlem başlatılabilir.
            </div>
        </div>
        <?php endif; ?>
        
        <div class="mt-4 text-center">
            <p><a href="index.php" class="btn btn-outline-secondary">Ana Sayfa</a></p>
            <p class="mt-3 text-muted">&copy; <?php echo date('Y'); ?> Artwork Auth. Tüm hakları saklıdır.</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
    <script>
    function copyVerificationLink() {
        var copyText = document.getElementById("verification-link");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value);
        
        alert("Doğrulama bağlantısı kopyalandı!");
    }
    </script>
</body>
</html> 