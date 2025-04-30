<?php
require_once 'includes/functions.php';

// Veritabanı bağlantısı
$conn = connectDB();

$artwork = null;
$message = '';
$message_type = '';

// Site URL'ini ayarlardan al
$site_url = "";
$stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'site_url'");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $site_url = $row['setting_value'];
}

// Logo URL'ini ayarlardan al
$logo_url = "";
$stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'site_logo'");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logo_url = $row['setting_value'];
}

// Arka plan resmini ayarlardan al
$background_image = "";
$stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'background_image'");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $background_image = $row['setting_value'];
}

// Site URL belirlenmemişse sunucu adresini kullan
if (empty($site_url)) {
    $site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
}

// Doğrulama kodu kontrol et
if (isset($_GET['code']) && !empty($_GET['code'])) {
    $code = sanitize($_GET['code']);
    
    // Eseri getir
    $stmt = $conn->prepare("
        SELECT a.*, 
               t.technique_name,
               et.edition_name
        FROM artworks a
        LEFT JOIN artwork_techniques t ON a.technique = t.technique_key COLLATE utf8mb4_unicode_ci
        LEFT JOIN artwork_edition_types et ON a.edition_type = et.edition_key COLLATE utf8mb4_unicode_ci
        WHERE a.verification_code = ? AND a.deleted_at IS NULL
    ");
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
            margin: 0;
            padding: 0;
            font-family: 'Myriad Pro', sans-serif;
            background-color: #fff;
            <?php if (!empty($background_image)): ?>
            background-image: url('<?php echo $background_image; ?>');
            <?php else: ?>
            background-image: url('assets/img/bg-pattern.png');
            <?php endif; ?>
            background-repeat: repeat;
            padding-top: 60px;
        }
        .verification-container {
            max-width: 1200px;
            margin: 30px auto;
        }
        .verification-header {
            text-align: center;
            margin-bottom: 30px;
            padding-top: 70px;
        }
        .verification-card {
            box-shadow: none;
            border-radius: 0;
            border: none;
            overflow: hidden;
            background-color: transparent;
        }
        .verification-image {
            max-height: 100%;
            object-fit: contain;
            width: 100%;
        }
        .verification-image-container {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: transparent;
            position: relative;
            min-height: 550px;
            max-height: 550px;
        }
        .verification-details {
            padding: 20px;
            height: 550px;
            overflow-y: auto;
            background-color: rgba(255, 255, 255, 0.2);
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
        .qr-code {
            max-width: 200px;
            margin: 20px auto;
        }
        .fab-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }
        .fab-button {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .fab-button:hover {
            transform: scale(1.1);
        }
        .fab-button i {
            font-size: 1.5rem;
        }
        .fab-label {
            position: absolute;
            right: 70px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s;
            white-space: nowrap;
        }
        .fab-button:hover .fab-label {
            opacity: 1;
        }
        .fab-qr {
            background-color: #007bff;
            color: white;
        }
        .fab-nfc {
            background-color: #28a745;
            color: white;
        }
        .fab-print {
            background-color: #17a2b8;
            color: white;
        }
        .print-section {
            display: none;
            margin-top: 40px;
            padding: 20px;
            border: 1px dashed #ccc;
            page-break-inside: avoid;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .print-section {
                display: block !important;
                break-inside: avoid;
                page-break-inside: avoid;
                visibility: visible !important;
                padding: 0;
                margin: 0;
                position: relative;
            }
            body {
                padding-top: 0;
                background: none !important;
            }
            .verification-container {
                max-width: 100%;
                margin: 0;
            }
            @page {
                size: A4 landscape;
                margin: 0;
            }
            body.printing {
                margin: 0;
                padding: 0;
                background: url('assets/img/bg-pattern.png') no-repeat center center;
                background-size: cover;
                font-family: 'Arial', sans-serif;
                color: #333;
                position: relative;
                width: 100%;
                height: 100vh;
            }
            .certificate-container {
                position: absolute;
                top: 2%;
                left: 2%;
                right: 2%;
                bottom: 2%;
                border: 2px solid #333;
                padding: 20px;
                box-sizing: border-box;
                display: flex;
                flex-direction: row;
                justify-content: space-between;
                align-items: flex-start;
                background-color: rgba(255,255,255,0.85);
            }
            .left-side {
                width: 48%;
            }
            .left-side img {
                width: 100%;
                height: auto;
                object-fit: contain;
                border: 1px solid #999;
            }
            .right-side {
                width: 48%;
                padding-left: 20px;
                box-sizing: border-box;
            }
            .right-side h1 {
                font-size: 24px;
                text-align: center;
                margin-bottom: 5px;
                letter-spacing: 2px;
            }
            .right-side h2 {
                font-size: 18px;
                text-align: center;
                margin: 5px 0 20px;
                font-weight: normal;
            }
            .info-section {
                font-size: 16px;
                margin-bottom: 20px;
            }
            .info-section b {
                display: inline-block;
                width: 150px;
            }
            .verification {
                text-align: center;
                margin-top: 30px;
            }
            .verification-code {
                font-weight: bold;
                font-size: 20px;
                margin-top: 10px;
            }
            .footer {
                position: absolute;
                bottom: 20px;
                left: 20px;
                right: 20px;
                text-align: center;
                font-size: 14px;
                color: #555;
            }
            .footer img {
                height: 50px;
                margin-bottom: 5px;
            }
        }
        @media (max-width: 768px) {
            .verification-image-container {
                min-height: 300px;
            }
            .verification-container {
                padding-left: 5px;
                padding-right: 5px;
            }
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
        .mini-toolbar .btn {
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
            padding: 0.7rem 1rem;
            width: 80px;
            height: 80px;
        }
        .mini-toolbar .btn:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }
        .mini-toolbar .btn i {
            color: #495057;
            font-size: 1.5rem;
        }
        .mini-toolbar .btn small {
            color: #6c757d;
            font-size: 0.75rem;
            font-weight: normal;
        }
        .table {
            background-color: transparent;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.05);
        }
        .table-striped tbody tr:nth-of-type(even) {
            background-color: transparent;
        }
        .card {
            background-color: transparent;
        }
    </style>
</head>
<body>
    <!-- Üst Menü -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <?php if (!empty($logo_url)): ?>
                <img src="<?php echo $logo_url; ?>" alt="ERBİLKARE" height="30" class="me-2">
                <?php else: ?>
                ERBİLKARE
                <?php endif; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="verify.php"><i class="fas fa-qrcode me-1"></i> Eser Sorgula</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="for_sale.php"><i class="fas fa-tags me-1"></i> Satıştaki Eserler</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($using_proxy): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <small><i class="fas fa-info-circle"></i> Bu sayfa harici bir kaynaktan resim görüntülüyor. Görüntülenme sorunları olursa lütfen yöneticiye bildirin.</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="verification-header">
            <h1 class="mb-2">ERBİLKARE</h1>
            <p class="text-muted">Certificate of Authenticity</p>
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
                <!-- Eser Resmi - Büyük şekilde görüntüleme -->
                <div class="col-md-8 position-relative">
                    <div class="verification-image-container">
                    <?php if (!empty($artwork['image_path'])): ?>
                        <?php
                        // Resim yolunu kontrol et ve doğru şekilde göster
                        $image_path = $artwork['image_path'];
                        
                        // Başında / varsa kaldır (önemli düzeltme)
                        if (strpos($image_path, '/') === 0) {
                            $image_path = substr($image_path, 1);
                        }
                        
                        // Eğer yol zaten http veya https ile başlıyorsa, tam URL kullan
                        if (strpos($image_path, 'http://') === 0 || strpos($image_path, 'https://') === 0) {
                            // URL olduğu gibi kullan
                        } 
                        // Eğer image_proxy.php kullanılıyorsa
                        elseif (strpos($image_path, 'image_proxy.php') !== false) {
                            // Proxy yolu olduğu gibi kullan
                        }
                        ?>
                        
                        <img src="<?php echo $image_path; ?>" alt="<?php echo $artwork['title']; ?>" class="img-fluid verification-image" style="max-height: 100%; object-fit: contain; max-width: 100%;">
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-image fa-5x text-muted"></i>
                            <p class="mt-3">Eser görseli mevcut değil</p>
                        </div>
                    <?php endif; ?>
                    </div>
                    
                    <!-- Yeni: Mini Toolbar -->
                   
                </div>
                
                <!-- Eser Detayları -->
                <div class="col-md-4">
                    <div class="verification-details">
                        <h2 class="mb-4"><?php echo $artwork['title']; ?></h2>
                        <table class="table table-striped">
                            <tbody>
                                <tr>
                                    <th width="30%">Sanatçı</th>
                                    <td><?php echo $artwork['artist_name']; ?></td>
                                </tr>
                                <?php if ($artwork['print_date']): ?>
                                <tr>
                                    <th>Tarih</th>
                                    <td><?php echo date('d.m.Y', strtotime($artwork['print_date'])); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($artwork['technique']): ?>
                                <tr>
                                    <th>Teknik</th>
                                    <td><?php echo !empty($artwork['technique_name']) ? $artwork['technique_name'] : $artwork['technique']; ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($artwork['dimensions']): ?>
                                <tr>
                                    <th>Boyutlar</th>
                                    <td><?php echo $artwork['dimensions']; ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($artwork['edition_name'])): ?>
                                <tr>
                                    <th>Baskı Detayları</th>
                                    <td><?php echo $artwork['edition_name'] . ' ' . $artwork['edition_number']; ?></td>
                                </tr>
                                <?php elseif ($artwork['edition_type'] && $artwork['edition_number']): ?>
                                <tr>
                                    <th>Baskı Detayları</th>
                                    <td><?php echo $artwork['edition_type'] . ' ' . $artwork['edition_number']; ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>Doğrulama Kodu</th>
                                    <td><strong><?php echo $artwork['verification_code']; ?></strong></td>
                                </tr>

                            </tbody>
                        </table>
                        
                        <?php if (!empty($artwork['description'])): ?>
                        <div class="mt-4">
                            <h5>Açıklama</h5>
                            <p><?php echo nl2br(htmlspecialchars($artwork['description'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Mini Toolbar -->
                        <div class="mini-toolbar no-print mt-4">
                            <div class="d-flex justify-content-between py-2">
                                <button class="btn btn-light shadow-sm mx-1" data-bs-toggle="modal" data-bs-target="#qrModal" data-bs-tooltip="tooltip" title="QR Kod ile Doğrula">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-qrcode"></i>
                                        <small class="mt-1">QR Kod</small>
                                    </div>
                                </button>
                                <button class="btn btn-light shadow-sm mx-1 nfc-button" data-bs-tooltip="tooltip" title="NFC ile Doğrula">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-wifi"></i>
                                        <small class="mt-1">NFC</small>
                                    </div>
                                </button>
                                <button class="btn btn-light shadow-sm mx-1" onclick="printCertificate()" data-bs-tooltip="tooltip" title="Sertifika Yazdır">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-print"></i>
                                        <small class="mt-1">Yazdır</small>
                                    </div>
                                </button>
                                <button class="btn btn-light shadow-sm mx-1" onclick="copyVerificationLink()" data-bs-tooltip="tooltip" title="Doğrulama Bağlantısını Kopyala">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-link"></i>
                                        <small class="mt-1">Kopyala</small>
                                    </div>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Doğrulama butonları kaldırıldı, artık floating action butonlar kullanılıyor -->
                        
                    </div>
                    
                </div>
                
            </div>
            
        </div>
        
        <!-- QR Kod Modal -->
        <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="qrModalLabel">Eser Doğrulama QR Kodu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="qr-code">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?php echo urlencode($site_url . '/verify.php?code=' . $artwork['verification_code']); ?>&format=png&bgcolor=FFFFFF00&color=000000" alt="QR Kodu" class="img-fluid">
                        </div>
                        <p>Bu QR kodu telefonunuzla tarayarak eserin orijinalliğini doğrulayabilirsiniz.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Yazdırma için gerekli bölüm - Sayfa içinde gizli olarak bulunur -->
        <div class="print-section">
            <body class="printing">
                <div class="certificate-container">
                    <div class="left-side">
                        <?php if (!empty($artwork['image_path'])): ?>
                            <?php
                            $image_path = $artwork['image_path'];
                            if (strpos($image_path, '/') === 0) {
                                $image_path = substr($image_path, 1);
                            }
                            ?>
                            <img src="<?php echo $image_path; ?>" alt="<?php echo $artwork['title']; ?>">
                        <?php else: ?>
                            <img src="assets/img/placeholder.php" alt="Eser Görseli">
                        <?php endif; ?>
                    </div>
                    <div class="right-side">
                        <h1>ERBİLKARE</h1>
                        <h2>Certificate of Authenticity</h2>
                        
                        <div class="info-section">
                            <p><b>Artist:</b> <?php echo $artwork['artist_name']; ?></p>
                            <p><b>Artwork Name:</b> <?php echo $artwork['title']; ?></p>
                            <?php if ($artwork['print_date']): ?>
                            <p><b>Print Date:</b> <?php echo date('d.m.Y', strtotime($artwork['print_date'])); ?></p>
                            <?php endif; ?>
                            <?php if ($artwork['dimensions']): ?>
                            <p><b>Print Size:</b> <?php echo $artwork['dimensions']; ?></p>
                            <?php endif; ?>
                            <?php if ($artwork['technique']): ?>
                            <p><b>Print Method:</b> <?php echo !empty($artwork['technique_name']) ? $artwork['technique_name'] : $artwork['technique']; ?></p>
                            <?php endif; ?>
                            <?php if (!empty($artwork['edition_name']) || ($artwork['edition_type'] && $artwork['edition_number'])): ?>
                            <p><b>Edition:</b> 
                                <?php 
                                if (!empty($artwork['edition_name'])) {
                                    echo $artwork['edition_name'] . (!empty($artwork['edition_number']) ? ' ' . $artwork['edition_number'] : '');
                                } else {
                                    echo $artwork['edition_type'] . ' ' . $artwork['edition_number']; 
                                }
                                ?>
                            </p>
                            <?php endif; ?>
                            <?php if (!empty($artwork['country'])): ?>
                            <p><b>Country of Origin:</b> <?php echo $artwork['country']; ?></p>
                            <?php endif; ?>
                            <?php if (!empty($artwork['first_owner'])): ?>
                            <p><b>First Owner:</b> <?php echo $artwork['first_owner']; ?></p>
                            <?php else: ?>
                            <p><b>First Owner:</b> -</p>
                            <?php endif; ?>
                        </div>

                        <div class="verification">
                            <div>Verification Code</div>
                            <div class="verification-code"><?php echo $artwork['verification_code']; ?></div>
                        </div>
                    </div>
                </div>

                <div class="footer">
                    <?php if (!empty($logo_url)): ?>
                        <img src="<?php echo $logo_url; ?>" alt="Erbil Kare Logo"><br>
                    <?php endif; ?>
                    This work is unique and registered in the <b><?php echo $artwork['artist_name']; ?></b> archive.<br>
                    <a href="<?php echo $site_url; ?>" target="_blank"><?php echo $site_url; ?></a>
                </div>
            </body>
        </div>
        
        
        
        <?php else: ?>
        <div class="card p-4 mb-4">
            <h4>Eser Doğrulama</h4>
            <form action="verify.php" method="get" class="mt-3">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" name="code" placeholder="Doğrulama kodunu girin" required>
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Sorgula</button>
                </div>
            </form>
            
            <h4 class="mt-4">Doğrulama Nasıl Çalışır?</h4>
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
            <div class="mb-3">
                <img src="<?php echo $logo_url; ?>" alt="ERBİLKARE" class="img-fluid" style="max-height: 60px;">
            </div>
            <p class="mt-3 text-muted">&copy; <?php echo date('Y'); ?> This work is unique and registered in the Erbilkare.com archive.</p>
            <p class="mt-3 text-muted">Sitemizden QR ve NFC ile doğrulama yapabilirsiniz. Detaylı bilgi için <a href="https://www.erbilkare.com/nfc-iletisim" target="_blank">tıklayınız.</a></p>

        </div> 
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
    <script>
    function copyVerificationLink() {
        // navigator.clipboard API kullanarak doğrulama linkini kopyala
        navigator.clipboard.writeText("<?php echo $site_url; ?>/verify.php?code=<?php echo $artwork['verification_code']; ?>")
            .then(() => {
                alert("Doğrulama bağlantısı kopyalandı!");
            })
            .catch(err => {
                console.error('Kopyalama hatası:', err);
                alert("Kopyalama işlemi başarısız oldu. Lütfen manuel olarak kopyalayın.");
            });
    }
    
    // Yazdırma fonksiyonu
    function printCertificate() {
        <?php 
        // Global değişkenlere erişim
        global $background_image, $logo_url, $site_url, $artwork, $image_path;
        ?>
        // Yeni pencere aç
        var printWindow = window.open('', '_blank', 'width=1200,height=800');
        
        // Pencere içeriği
        printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="tr">
        <head>
            <meta charset="UTF-8">
            <title>Sertifika Önizleme</title>
              <style>
                @font-face {
                font-family: 'Skia-Light';
                src: url('fonts/Skia-Light.ttf') format('truetype');
                }

                @font-face {
                font-family: 'Myriad Pro';
                src: url('fonts/MyriadPro-Regular.otf') format('opentype');
                }

                @page {
                size: A4 landscape;
                margin: 0;
                }

                body {
                margin: 0;
                padding: 0;
                font-family: 'Myriad Pro', sans-serif;
                background-color: #fff;
                <?php if (!empty($background_image)): ?>
                background-image: url('<?php echo $background_image; ?>');
                <?php else: ?>
                background-image: url('assets/img/bg-pattern.png');
                <?php endif; ?>
                background-repeat: repeat;
                }

                .certificate-container {
                margin: 0px 20px 0px 20px;
                padding: 0px 40px 0px 40px;
                position: relative;
                display: flex;
                flex-direction: row;
                justify-content: space-between;
                }

                .left-side {
                width: 55%;
                }

                .left-side img {
                width: 100%;
                border: 1px solid #999;
                object-fit: contain;
                }

                .right-side {
                width: 35%;
                padding-left: 20px;
                }

                .header {
                text-align: center;
                margin-top: 25px;
                }

                .header h1 {
                font-weight: lighter;
                font-family: 'Skia-Light', sans-serif;
                font-size: 60px;
                margin: 0;
                }

                .header h2 {
                font-family: 'Myriad Pro', sans-serif;
                font-size: 30px;
                margin-top: 5px;
                font-weight: normal;
                }

                .info-section {
                font-size: 16px;
                margin-top: 30px;
                }

                .info-section b {
                display: inline-block;
                width: 150px;
                }

                .verification {
                text-align: center;
                margin-top: 40px;
                }

                .verification-code {
                font-weight: bold;
                font-size: 30px;
                }

                .logo-top {
                position: absolute;
                top: 10px;
                left: 50%;
                transform: translateX(-50%);
                }

                .logo-bottom {
                position: absolute;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                text-align: center;
                font-size: 14px;
                color: #555;
                }

                .logo-bottom img,
                .logo-top img {
                height: 100px;
                }
                .qr-code img {
                    border: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
    padding: 0.7rem 1rem;
    width: 80px;
    height: 80px;
                }
           .qr-code small {
            margin-top: 0.5rem;
            display: block;
        }
            .qr-code {
                        margin-top: 25px;
                        display: flex;
                        flex-direction: row-reverse;
                        align-items: flex-start;
                        justify-content: center;
                        text-align: center;
                        align-content: flex-start;
                        flex-wrap: nowrap;
        }
    .qr-code svg {
                    border: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
    padding: 0.7rem 1rem;
    width: 80px;
    height: 80px;
                }
  </style>
        </head>
        <body>

 

  <div class="header">
    <h1>ERBİLKARE</h1>
    <h2>Certificate of Authenticity</h2>
  </div>

  <div class="certificate-container">
    <div class="left-side">
      <img src="<?php echo $image_path; ?>" alt="<?php echo $artwork['title']; ?>">
    </div>
    <div class="right-side">
      <div class="info-section">
        <p><b>Artist:</b> <?php echo $artwork['artist_name']; ?></p>
        <p><b>Artwork Name:</b> <?php echo $artwork['title']; ?></p>
        <?php if ($artwork['print_date']): ?>
        <p><b>Print Date:</b> <?php echo date('d.m.Y', strtotime($artwork['print_date'])); ?></p>
        <?php endif; ?>
        <?php if ($artwork['dimensions']): ?>
        <p><b>Print Size:</b> <?php echo $artwork['dimensions']; ?></p>
        <?php endif; ?>
        <?php if ($artwork['technique']): ?>
        <p><b>Print Method:</b> <?php echo !empty($artwork['technique_name']) ? $artwork['technique_name'] : $artwork['technique']; ?></p>
        <?php endif; ?>
        <?php if (!empty($artwork['edition_name']) || ($artwork['edition_type'] && $artwork['edition_number'])): ?>
        <p><b>Edition:</b> 
          <?php 
          if (!empty($artwork['edition_name'])) {
              echo $artwork['edition_name'] . (!empty($artwork['edition_number']) ? ' ' . $artwork['edition_number'] : '');
          } else {
              echo $artwork['edition_type'] . ' ' . $artwork['edition_number']; 
          }
          ?>
        </p>
        <?php endif; ?>
        <?php if (!empty($artwork['country'])): ?>
        <p><b>Country of Origin:</b> <?php echo $artwork['country']; ?></p>
        <?php endif; ?>
        <?php if (!empty($artwork['first_owner'])): ?>
        <p><b>First Owner:</b> <?php echo $artwork['first_owner']; ?></p>
        <?php else: ?>
        <p><b>First Owner:</b> -</p>
        <?php endif; ?>
      </div>

      <div class="verification">
        <div>Verification Code</div>
        <div class="verification-code"><?php echo $artwork['verification_code']; ?></div>
      </div>
    
      <div class="qr-code">
      <div class="left-side">
      <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?php echo urlencode($site_url . '/verify.php?code=' . $artwork['verification_code']); ?>&format=png&bgcolor=FFFFFF00&color=000000" alt="QR Kodu" class="img-fluid">
               <small>QR Kod</small>
     </div>
     
                 <div class="right-side">
<svg class="svg-inline--fa fa-wifi" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="wifi" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" data-fa-i2svg=""><path fill="currentColor" d="M54.2 202.9C123.2 136.7 216.8 96 320 96s196.8 40.7 265.8 106.9c12.8 12.2 33 11.8 45.2-.9s11.8-33-.9-45.2C549.7 79.5 440.4 32 320 32S90.3 79.5 9.8 156.7C-2.9 169-3.3 189.2 8.9 202s32.5 13.2 45.2 .9zM320 256c56.8 0 108.6 21.1 148.2 56c13.3 11.7 33.5 10.4 45.2-2.8s10.4-33.5-2.8-45.2C459.8 219.2 393 192 320 192s-139.8 27.2-190.5 72c-13.3 11.7-14.5 31.9-2.8 45.2s31.9 14.5 45.2 2.8c39.5-34.9 91.3-56 148.2-56zm64 160a64 64 0 1 0 -128 0 64 64 0 1 0 128 0z"></path></svg>               
<small>Eserlerimizde NFC İletişimi vardır.</small>
                 </div>
     
     </div>


    </div>
  </div>

  <div class="logo-bottom">
    <?php if (!empty($logo_url)): ?>
      <img src="<?php echo $logo_url; ?>" alt="Logo Alt"><br>
    <?php endif; ?>
    This work is unique and registered in the <b><?php echo $artwork['artist_name']; ?></b> archive.<br>
    <a href="<?php echo $site_url; ?>" target="_blank"><?php echo $site_url; ?></a>
  </div>

        </body>
        </html>
        `);
        
        // Çıktı al
        printWindow.document.close();
        printWindow.focus();
        
        // Yazdırma işlemi için kısa bir gecikme
        setTimeout(function() {
            printWindow.print();
            //printWindow.close(); // Yazdırma bitince pencereyi kapat (isteğe bağlı)
        }, 500);
    }
    
    // Sayfa yüklendiğinde
    document.addEventListener('DOMContentLoaded', function() {
        // Tooltipleri etkinleştir
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-tooltip="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Yazdır butonu için olay dinleyicisi ekle
        const printButton = document.getElementById('printButton');
        if (printButton) {
            printButton.addEventListener('click', function() {
                printCertificate();
            });
        }

        // NFC ile tarama fonksiyonu
        const nfcButton = document.querySelector('.nfc-button');
        
        if (nfcButton) {
            nfcButton.addEventListener('click', function() {
                if ('NDEFReader' in window) {
                    try {
                        const ndef = new NDEFReader();
                        ndef.scan()
                            .then(() => {
                                alert("NFC taramaya hazır. NFC etiketini telefonunuza yaklaştırın.");
                                
                                ndef.onreading = event => {
                                    const decoder = new TextDecoder();
                                    for (const record of event.message.records) {
                                        if (record.recordType === "text") {
                                            const textData = decoder.decode(record.data);
                                            if (textData.includes('verify.php?code=')) {
                                                window.location.href = textData;
                                            } else {
                                                alert("Bu bir doğrulama NFC etiketi değil!");
                                            }
                                        }
                                    }
                                }
                            })
                            .catch(error => {
                                alert(`NFC tarama hatası: ${error}`);
                            });
                    } catch (error) {
                        alert("NFC taraması başlatılamadı. Cihazınız desteklemiyor olabilir.");
                    }
                } else {
                    alert("Cihazınız NFC taramayı desteklemiyor.");
                }
            });
        }
    });
    </script>
</body>
</html> 