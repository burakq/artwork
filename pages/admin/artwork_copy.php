<?php
// Hata raporlamayı kapat
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Giriş yapılmış mı kontrol et
if (!isLoggedIn()) {
    die(json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']));
}

// Admin yetkisi kontrolü
if (!isAdmin()) {
    die(json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok']));
}

// ID parametresini kontrol et
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die(json_encode(['success' => false, 'message' => 'Geçersiz eser ID\'si']));
}

$id = (int)$_GET['id'];

$conn = connectDB();

try {
    // Orijinal eseri getir
    $stmt = $conn->prepare("SELECT * FROM artworks WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $original = $result->fetch_assoc();

    if (!$original) {
        throw new Exception('Eser bulunamadı');
    }

    // Yeni doğrulama kodu oluştur
    function generateVerificationCode() {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < 12; $i++) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $code;
    }

    $verification_code = generateVerificationCode();

    // Baskı numarasını bir artır
    $edition_number = $original['edition_number'] + 1;

    // Yeni eseri ekle
    $stmt = $conn->prepare("INSERT INTO artworks (
        title, artist, year, medium, dimensions, edition_number, 
        edition_type, verification_code, location, print_date, 
        status, technique, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->bind_param(
        "sssssissssss",
        $original['title'],
        $original['artist'],
        $original['year'],
        $original['medium'],
        $original['dimensions'],
        $edition_number,
        $original['edition_type'],
        $verification_code,
        $original['location'],
        $original['print_date'],
        $original['status'],
        $original['technique']
    );

    if (!$stmt->execute()) {
        throw new Exception('Eser kopyalanırken bir hata oluştu');
    }

    $new_id = $conn->insert_id;

    // Başarılı yanıt döndür
    echo json_encode([
        'success' => true,
        'message' => 'Eser başarıyla kopyalandı',
        'new_id' => $new_id
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
} 