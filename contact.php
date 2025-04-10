<?php
require_once 'includes/functions.php';

// Veritabanı bağlantısı
$conn = connectDB();

$artwork = null;
$success_message = '';
$error_message = '';

// Eser ID'sini kontrol et
if (isset($_GET['artwork_id']) && is_numeric($_GET['artwork_id'])) {
    $artwork_id = (int)$_GET['artwork_id'];
    
    // Eseri getir
    $stmt = $conn->prepare("SELECT * FROM artworks WHERE id = ? AND status = 'for_sale' AND deleted_at IS NULL");
    $stmt->bind_param("i", $artwork_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $artwork = $result->fetch_assoc();
    } else {
        $error_message = "Bu eser satışta değil veya mevcut değil.";
    }
    
    $stmt->close();
}

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? sanitize($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
    $message = isset($_POST['message']) ? sanitize($_POST['message']) : '';
    $artwork_id = isset($_POST['artwork_id']) ? (int)$_POST['artwork_id'] : 0;
    
    // Validasyon
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Adınız gereklidir.";
    }
    
    if (empty($email)) {
        $errors[] = "E-posta adresiniz gereklidir.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi giriniz.";
    }
    
    if (empty($phone)) {
        $errors[] = "Telefon numaranız gereklidir.";
    }
    
    if (empty($message)) {
        $errors[] = "Mesaj alanı boş olamaz.";
    }
    
    if (empty($errors)) {
        // Eseri kontrol et
        $stmt = $conn->prepare("SELECT * FROM artworks WHERE id = ? AND status = 'for_sale' AND deleted_at IS NULL");
        $stmt->bind_param("i", $artwork_id);
        $stmt->execute();
        $artwork_result = $stmt->get_result();
        
        if ($artwork_result->num_rows > 0) {
            $artwork = $artwork_result->fetch_assoc();
            
            // İletişim mesajını kaydet
            $insert_stmt = $conn->prepare("INSERT INTO contact_messages (artwork_id, name, email, phone, message, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $insert_stmt->bind_param("issss", $artwork_id, $name, $email, $phone, $message);
            
            if ($insert_stmt->execute()) {
                $success_message = "Mesajınız başarıyla gönderildi. En kısa sürede sizinle iletişime geçeceğiz.";
                
                // Bildirim e-postası gönder
                $to = "info@artworkauth.com"; // Galeri e-posta adresi
                $subject = "Yeni Eser Satın Alma Talebi: " . $artwork['title'];
                $email_message = "Yeni bir satın alma talebi alındı:\n\n";
                $email_message .= "Eser: " . $artwork['title'] . " (ID: " . $artwork_id . ")\n";
                $email_message .= "Fiyat: " . number_format($artwork['price'], 2, ',', '.') . " TL\n\n";
                $email_message .= "Müşteri Bilgileri:\n";
                $email_message .= "Ad Soyad: " . $name . "\n";
                $email_message .= "E-posta: " . $email . "\n";
                $email_message .= "Telefon: " . $phone . "\n\n";
                $email_message .= "Mesaj:\n" . $message . "\n";
                
                $headers = 'From: ' . $email . "\r\n" .
                    'Reply-To: ' . $email . "\r\n" .
                    'X-Mailer: PHP/' . phpversion();
                
                mail($to, $subject, $email_message, $headers);
                
                // Formu temizle
                $name = $email = $phone = $message = '';
                
            } else {
                $error_message = "Mesajınız gönderilemedi. Lütfen daha sonra tekrar deneyin.";
            }
            
            $insert_stmt->close();
            
        } else {
            $error_message = "Bu eser satışta değil veya mevcut değil.";
        }
        
        $stmt->close();
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Bağlantıyı kapat
$conn->close();

// Meta açıklaması için
$meta_description = "Artwork Auth - Sanat eseri satın alma formu. İlgilendiğiniz sanat eseri için bizimle iletişime geçin.";
if ($artwork) {
    $meta_description = "Satın alma talebi: " . $artwork['title'] . " - " . $artwork['artist_name'] . ". Fiyat: " . number_format($artwork['price'], 2, ',', '.') . " TL";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $meta_description; ?>">
    <title>Artwork Auth - <?php echo $artwork ? "Satın Al: {$artwork['title']}" : "İletişim"; ?></title>
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
        .contact-container {
            max-width: 800px;
            margin: 30px auto;
        }
        .contact-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .contact-card {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 30px;
        }
        .contact-image {
            max-height: 300px;
            object-fit: cover;
            width: 100%;
        }
        .navbar-brand img {
            height: 30px;
            margin-right: 10px;
        }
        .form-group {
            margin-bottom: 1rem;
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

    <div class="container contact-container">
        <div class="contact-header">
            <h1 class="mb-2"><?php echo $artwork ? "Satın Al: {$artwork['title']}" : "İletişim"; ?></h1>
            <p class="text-muted">
                <?php echo $artwork 
                    ? "Bu eser için satın alma talebinizi aşağıdaki formu doldurarak iletebilirsiniz."
                    : "Bizimle iletişime geçmek için aşağıdaki formu doldurun."; 
                ?>
            </p>
        </div>

        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <?php if ($artwork): ?>
        <div class="card contact-card mb-4">
            <div class="row g-0">
                <div class="col-md-4">
                    <?php if (!empty($artwork['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($artwork['image_path']); ?>" class="contact-image" alt="<?php echo htmlspecialchars($artwork['title']); ?>">
                    <?php else: ?>
                    <div class="contact-image d-flex justify-content-center align-items-center bg-light">
                        <i class="fas fa-image fa-3x text-muted"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
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
                        
                        <div class="mt-3">
                            <h4 class="text-success"><?php echo number_format($artwork['price'], 2, ',', '.'); ?> TL</h4>
                        </div>
                        
                        <div class="mt-2">
                            <a href="verify.php?code=<?php echo htmlspecialchars($artwork['verification_code']); ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-certificate"></i> Doğrula
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">İletişim Formu</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                    <?php if ($artwork): ?>
                    <input type="hidden" name="artwork_id" value="<?php echo $artwork['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group mb-3">
                        <label for="name" class="form-label">Adınız Soyadınız *</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="email" class="form-label">E-posta Adresiniz *</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="phone" class="form-label">Telefon Numaranız *</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="message" class="form-label">Mesajınız *</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agree" name="agree" required>
                            <label class="form-check-label" for="agree">
                                Kişisel verilerimin işlenmesine onay veriyorum.
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Gönder</button>
                </form>
            </div>
        </div>

        <div class="mt-4 text-center">
            <p>
                <a href="for_sale.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Satıştaki Eserlere Dön
                </a>
            </p>
        </div>
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