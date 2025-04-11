<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Giriş yapılmış mı kontrol et
if (!isLoggedIn()) {
    die('Oturum açmanız gerekiyor.');
}

// Admin yetkisi kontrolü
if (!isAdmin()) {
    die('Bu işlem için yetkiniz yok.');
}

// Şifre kontrolü
$password = isset($_GET['password']) ? $_GET['password'] : '';
if (empty($password)) {
    die('Şifre gerekli.');
}

// Veritabanı bağlantısı
$conn = connectDB();

// Admin şifresini kontrol et
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ? AND is_admin = 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Yedek şifresini kontrol et (settings tablosundan)
$stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'backup_password'");
$stmt->execute();
$result = $stmt->get_result();
$backup_setting = $result->fetch_assoc();
$backup_password = $backup_setting ? $backup_setting['setting_value'] : null;

// Şifre kontrolü
if (!password_verify($password, $admin['password']) && $password !== $backup_password) {
    die('Geçersiz şifre.');
}

// Yedek dosyası için geçici dizin
$backup_dir = '../../backups';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Yedek dosyası adı
$backup_file = $backup_dir . '/artwork_auth_' . date('Y-m-d_H-i-s') . '.sql';

// MySQL yedek komutu
$command = sprintf(
    '/Applications/MAMP/Library/bin/mysql80/bin/mysqldump -u root -proot -h localhost -P 8889 artwork_auth > %s',
    escapeshellarg($backup_file)
);

// Yedeği al
exec($command, $output, $return_var);

if ($return_var !== 0) {
    die('Yedek alınırken bir hata oluştu.');
}

// Dosya boyutu kontrolü
if (!file_exists($backup_file) || filesize($backup_file) === 0) {
    die('Yedek dosyası oluşturulamadı.');
}

// Dosyayı indir
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($backup_file) . '"');
header('Content-Length: ' . filesize($backup_file));
readfile($backup_file);

// Geçici dosyayı sil
unlink($backup_file); 
 
 
 