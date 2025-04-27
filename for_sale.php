<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Veritabanı bağlantısı
$conn = connectDB();

// Site URL'ini ve logoyu ayarlardan al
$site_url = "";
$logo_url = "";

$stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_url', 'site_logo')");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if ($row['setting_key'] == 'site_url') {
        $site_url = $row['setting_value'];
    } elseif ($row['setting_key'] == 'site_logo') {
        $logo_url = $row['setting_value'];
    }
}

// Site URL belirlenmemişse sunucu adresini kullan
if (empty($site_url)) {
    $site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
}

// Satıştaki eserleri getir (status = 'satista')
$artworks = [];
$stmt = $conn->prepare("
    SELECT a.*, 
           t.technique_name,
           et.edition_name
    FROM artworks a
    LEFT JOIN artwork_techniques t ON a.technique = t.technique_key COLLATE utf8mb4_unicode_ci
    LEFT JOIN artwork_edition_types et ON a.edition_type = et.edition_key COLLATE utf8mb4_unicode_ci 
    WHERE a.status = 'satista' AND a.deleted_at IS NULL 
    ORDER BY a.created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $artworks[] = $row;
    }
}

// Bağlantıyı kapat
$conn->close();

// Sayfa başlığı
$page_title = "Satıştaki Eserler";
$meta_description = "Satın alabileceğiniz orijinal sanat eserleri. Tüm eserlerimiz orijinallik sertifikası ile birlikte gelir.";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $meta_description; ?>">
    <title><?php echo $page_title; ?> - ERBİLKARE</title>
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
        .page-header {
            text-align: center;
            padding-top: 70px;
            margin-bottom: 30px;
        }
        .card-img-top {
            height: 250px;
            object-fit: cover;
        }
        .card {
            transition: transform 0.3s;
            margin-bottom: 30px;
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-footer {
            background-color: #fff;
            border-top: 1px solid rgba(0,0,0,.125);
        }
        .price-tag {
            font-size: 1.25rem;
            font-weight: bold;
            color: #28a745;
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
                        <a class="nav-link active" href="for_sale.php"><i class="fas fa-tags me-1"></i> Satıştaki Eserler</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="mb-2">Satıştaki Eserler</h1>
            <p class="text-muted">Orijinallik sertifikalı eserlerimiz</p>
        </div>

        <?php if (empty($artworks)): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle fa-2x mb-3"></i>
            <h4>Şu anda satışta eser bulunmamaktadır.</h4>
            <p>Lütfen daha sonra tekrar ziyaret edin veya yeni eserler hakkında bilgi almak için bizimle iletişime geçin.</p>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($artworks as $artwork): ?>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100">
                    <?php if (!empty($artwork['image_path'])): ?>
                    <?php
                    // Resim yolunu kontrol et ve doğru şekilde göster
                    $image_path = $artwork['image_path'];
                    
                    // Başında / varsa kaldır
                    if (strpos($image_path, '/') === 0) {
                        $image_path = substr($image_path, 1);
                    }
                    ?>
                    <img src="<?php echo $image_path; ?>" class="card-img-top" alt="<?php echo $artwork['title']; ?>">
                    <?php else: ?>
                    <div class="text-center py-5 bg-light">
                        <i class="fas fa-image fa-3x text-muted"></i>
                        <p class="mt-2">Görsel yok</p>
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $artwork['title']; ?></h5>
                        <p class="card-text text-muted"><?php echo $artwork['artist_name']; ?></p>
                        <?php if (!empty($artwork['technique_name'])): ?>
                        <p class="card-text"><small>Teknik: <?php echo $artwork['technique_name']; ?></small></p>
                        <?php elseif (!empty($artwork['technique'])): ?>
                        <p class="card-text"><small>Teknik: <?php echo $artwork['technique']; ?></small></p>
                        <?php endif; ?>
                        <?php if (!empty($artwork['dimensions'])): ?>
                        <p class="card-text"><small>Boyut: <?php echo $artwork['dimensions']; ?></small></p>
                        <?php endif; ?>
                        <?php if (!empty($artwork['edition_name'])): ?>
                        <p class="card-text"><small>Baskı: <?php echo $artwork['edition_name']; ?> <?php echo $artwork['edition_number']; ?></small></p>
                        <?php elseif (!empty($artwork['edition_type']) && !empty($artwork['edition_number'])): ?>
                        <p class="card-text"><small>Baskı: <?php echo $artwork['edition_type'] . ' ' . $artwork['edition_number']; ?></small></p>
                        <?php endif; ?>
                        <?php if (!empty($artwork['description'])): ?>
                        <p class="card-text"><?php echo substr(strip_tags($artwork['description']), 0, 100); ?>...</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <span class="price-tag"><?php echo number_format($artwork['price'], 0, ',', '.'); ?> ₺</span>
                        <div>
                            <a href="verify.php?code=<?php echo $artwork['verification_code']; ?>" class="btn btn-sm btn-outline-primary me-1" title="Detaylar">
                                <i class="fas fa-search"></i> Detaylar
                            </a>
                            <a href="https://wa.me/905300000000?text=<?php echo urlencode($site_url . '/verify.php?code=' . $artwork['verification_code'] . ' eserini satın almak istiyorum.'); ?>" class="btn btn-sm btn-success" target="_blank" title="Satın Al">
                                <i class="fab fa-whatsapp"></i> Satın Al
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="mt-5 text-center">
            <div class="mb-3">
                <img src="<?php echo $logo_url; ?>" alt="ERBİLKARE" class="img-fluid" style="max-height: 60px;">
            </div>
            <p class="mt-3 text-muted">&copy; <?php echo date('Y'); ?> ERBİLKARE. Tüm hakları saklıdır.</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html> 
 
 
 
 