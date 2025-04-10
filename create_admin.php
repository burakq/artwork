<?php
// Veritabanı bağlantı bilgileri
$db_host = 'localhost';
$db_port = '8889';
$db_user = 'root';
$db_pass = 'root';
$db_name = 'artwork_auth';

// Veritabanı bağlantısı oluştur
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

// Bağlantı kontrolü
if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

// Admin kullanıcı bilgileri
$name = 'Admin';
$email = 'admin@example.com';
$password = 'admin123'; // Gerçek ortamda güçlü bir şifre kullanılmalıdır!
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// is_admin sütunu kontrolü
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
if ($result->num_rows == 0) {
    // is_admin sütunu yoksa ekle
    $conn->query("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0");
    echo "is_admin sütunu eklendi.<br>";
}

// Aynı e-posta ile kullanıcı var mı kontrol et
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Kullanıcı varsa güncelle
    $stmt = $conn->prepare("UPDATE users SET name = ?, password = ?, is_admin = 1 WHERE email = ?");
    $stmt->bind_param("sss", $name, $hashedPassword, $email);
    
    if ($stmt->execute()) {
        echo "Admin kullanıcısı güncellendi.<br>";
        echo "E-posta: $email<br>";
        echo "Şifre: $password<br>";
    } else {
        echo "Hata: " . $stmt->error;
    }
} else {
    // Kullanıcı yoksa ekle
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, is_admin, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())");
    $stmt->bind_param("sss", $name, $email, $hashedPassword);
    
    if ($stmt->execute()) {
        echo "Admin kullanıcısı oluşturuldu.<br>";
        echo "E-posta: $email<br>";
        echo "Şifre: $password<br>";
    } else {
        echo "Hata: " . $stmt->error;
    }
}

$stmt->close();
$conn->close();

// Bu dosyayı güvenlik için kullandıktan sonra silmeyi unutmayın!
echo "<br><strong>Önemli:</strong> Bu dosyayı güvenlik nedeniyle sunucunuzdan silmelisiniz!";
?> 