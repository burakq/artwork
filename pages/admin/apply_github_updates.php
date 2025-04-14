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

// Güncelleme işlemi
$success = true;
$message = '';
$output = [];

try {
    // GitHub'dan dosyaları indir
    $files = [
        'version.txt' => 'https://raw.githubusercontent.com/burakq/artwork/main/version.txt',
        'pages/admin/artwork_techniques.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/artwork_techniques.php',
        'pages/admin/artwork_edition_types.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/artwork_edition_types.php',
        'pages/admin/artwork_editions.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/artwork_editions.php',
        'pages/admin/artworks.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/artworks.php',
        'pages/admin/customers.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/customers.php',
        'pages/admin/customer_add.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/customer_add.php',
        'pages/admin/orders.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/orders.php',
        'pages/admin/verification_logs.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/verification_logs.php',
        'pages/admin/users.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/users.php',
        'pages/admin/user_add.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/user_add.php',
        'pages/admin/settings.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/settings.php'
    ];

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: PHP/ArtworkAuth'
            ],
            'timeout' => 5
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    foreach ($files as $localPath => $remoteUrl) {
        $content = @file_get_contents($remoteUrl, false, $context);
        if ($content !== false) {
            // Dosyayı yaz
            if (file_put_contents('../../' . $localPath, $content) === false) {
                throw new Exception("$localPath dosyası yazılamadı");
            }
            $output[] = "$localPath başarıyla güncellendi";
        } else {
            throw new Exception("$localPath dosyası indirilemedi");
        }
    }

    $message = 'Güncellemeler başarıyla uygulandı';
} catch (Exception $e) {
    $success = false;
    $message = 'Güncelleme sırasında bir hata oluştu: ' . $e->getMessage();
}

// Sonucu döndür
echo json_encode([
    'success' => $success,
    'message' => $message,
    'output' => $output
]); 
 
 
 