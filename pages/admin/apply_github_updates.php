<?php
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

// Git komutlarını çalıştır
$commands = [
    'git fetch origin',
    'git reset --hard origin/main',
    'git pull origin main'
];

$output = [];
$success = true;

foreach ($commands as $command) {
    $result = shell_exec($command . ' 2>&1');
    $output[] = $command . ': ' . $result;
    
    // Hata kontrolü
    if (strpos($result, 'error') !== false || strpos($result, 'fatal') !== false) {
        $success = false;
        break;
    }
}

// Sonucu döndür
echo json_encode([
    'success' => $success,
    'message' => $success ? 'Güncellemeler başarıyla uygulandı' : 'Güncelleme sırasında bir hata oluştu',
    'output' => $output
]); 
 
 
 