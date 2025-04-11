<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Veritabanı bağlantısı
$conn = connectDB();

// WhatsApp numarasını ayarlardan al
$whatsapp_number = "905301234567"; // Varsayılan numara
$query = "SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_number'";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $whatsapp_number = $row['setting_value'];
}

// Sayfalama değişkenleri
$items_per_page = 9;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // Sayfa 1'den küçük olamaz
$offset = ($current_page - 1) * $items_per_page;

// Toplam eser sayısını al
$count_query = "SELECT COUNT(*) as total FROM artworks WHERE status = 'for_sale'";
$count_result = $conn->query($count_query);
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $items_per_page);

// Eserleri getir
$query = "SELECT * FROM artworks WHERE status = 'for_sale' ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $items_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Sayfalama bağlantıları
$pagination_html = '';
if ($total_pages > 1) {
    $pagination_html .= '<div class="pagination justify-content-center">';
    
    // Önceki sayfa
    if ($current_page > 1) {
        $pagination_html .= '<a href="?page='.($current_page-1).'" class="page-link">&laquo; Önceki</a>';
    } else {
        $pagination_html .= '<span class="page-link disabled">&laquo; Önceki</span>';
    }
    
    // Sayfa numaraları
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $pagination_html .= '<span class="page-link active">'.$i.'</span>';
        } else {
            $pagination_html .= '<a href="?page='.$i.'" class="page-link">'.$i.'</a>';
        }
    }
    
    // Sonraki sayfa
    if ($current_page < $total_pages) {
        $pagination_html .= '<a href="?page='.($current_page+1).'" class="page-link">Sonraki &raquo;</a>';
    } else {
        $pagination_html .= '<span class="page-link disabled">Sonraki &raquo;</span>';
    }
    
    $pagination_html .= '</div>';
}

// Bağlantıyı kapat
$conn->close();

// Meta açıklaması için
$meta_description = "Artwork Auth - Satışta olan sanat eserleri. Orijinalliği doğrulanmış eserler arasından satın alım yapabilirsiniz.";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $meta_description; ?>">
    <title>Artwork Auth - Satıştaki Eserler</title>
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
        .for-sale-container {
            margin: 30px auto;
        }
        .artwork-card {
            height: 100%;
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .artwork-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .artwork-image {
            height: 250px;
            object-fit: cover;
            width: 100%;
        }
        .card-footer {
            background-color: rgba(0, 0, 0, 0.03);
        }
        .art-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #28a745;
        }
        .no-results {
            min-height: 300px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .navbar-brand img {
            height: 30px;
            margin-right: 10px;
        }
        .pagination {
            justify-content: center;
            margin-top: 40px;
        }
        .whatsapp-btn {
            background-color: #25D366;
            border-color: #25D366;
        }
        .whatsapp-btn:hover {
            background-color: #128C7E;
            border-color: #128C7E;
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

    <div class="container for-sale-container">
        <div class="text-center mb-4">
            <h1>Satıştaki Sanat Eserleri</h1>
            <p class="text-muted">Orijinalliği doğrulanmış, satışa hazır eserler.</p>
        </div>
        
        <!-- Eserler -->
        <?php if ($result->num_rows > 0): ?>
        <div class="row mt-4">
            <?php while ($artwork = $result->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="card artwork-card h-100">
                    <?php if (!empty($artwork['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($artwork['image_path']); ?>" class="card-img-top artwork-image" alt="<?php echo htmlspecialchars($artwork['title']); ?>">
                    <?php else: ?>
                    <div class="card-img-top artwork-image d-flex justify-content-center align-items-center bg-light">
                        <i class="fas fa-image fa-3x text-muted"></i>
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($artwork['title']); ?></h5>
                        <p class="card-text text-muted">Sanatçı: <?php echo htmlspecialchars($artwork['artist_name']); ?></p>
                        <?php if (!empty($artwork['technique'])): ?>
                        <p class="card-text"><small>Teknik: <?php echo htmlspecialchars($artwork['technique']); ?></small></p>
                        <?php endif; ?>
                        <?php if (!empty($artwork['year'])): ?>
                        <p class="card-text"><small>Yıl: <?php echo htmlspecialchars($artwork['year']); ?></small></p>
                        <?php endif; ?>
                        <?php
                        $dimensions = [];
                        if (!empty($artwork['width'])) $dimensions[] = "G: {$artwork['width']} {$artwork['dimension_unit']}";
                        if (!empty($artwork['height'])) $dimensions[] = "Y: {$artwork['height']} {$artwork['dimension_unit']}";
                        if (!empty($artwork['depth'])) $dimensions[] = "D: {$artwork['depth']} {$artwork['dimension_unit']}";
                        
                        if (!empty($dimensions)):
                        ?>
                        <p class="card-text"><small>Boyut: <?php echo implode(' × ', $dimensions); ?></small></p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <span class="art-price"><?php echo number_format($artwork['price'], 0, ',', '.'); ?> ₺</span>
                        <div>
                            <a href="https://wa.me/<?php echo $whatsapp_number; ?>?text=<?php echo urlencode('Artwork Auth sitesinde gördüğüm ' . $artwork['id'] . ' ID numaralı "' . $artwork['title'] . '" isimli eser hakkında bilgi almak istiyorum.'); ?>" class="btn btn-sm whatsapp-btn text-white" target="_blank">
                                <i class="fab fa-whatsapp"></i> Satın Al
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Sayfalama -->
        <?php echo $pagination_html; ?>

        <?php else: ?>
        <div class="no-results text-center mt-5">
            <i class="fas fa-search fa-4x text-muted mb-3"></i>
            <h3>Satışta eser bulunamadı</h3>
            <p class="text-muted">Şu anda satışta eser bulunmamaktadır.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-palette"></i> Artwork Auth</h5>
                    <p>Sanat eserlerinin orijinalliğini doğrulama ve satış platformu.</p>
                </div>
                <div class="col-md-3">
                    <h5>Bağlantılar</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Ana Sayfa</a></li>
                        <li><a href="verify.php" class="text-white">Eser Doğrulama</a></li>
                        <li><a href="for_sale.php" class="text-white">Satıştaki Eserler</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>İletişim</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> info@artworkauth.com</li>
                        <li><i class="fas fa-phone me-2"></i> +90 (212) 123 4567</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> İstanbul, Türkiye</li>
                    </ul>
                </div>
            </div>
            <div class="text-center mt-4">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Artwork Auth. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html> 
 
 
 
 