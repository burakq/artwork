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
        // Kök dizin dosyaları
        'version.txt' => 'https://raw.githubusercontent.com/burakq/artwork/main/version.txt',
        'index.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/index.php',
        'verify.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/verify.php',
        'contact.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/contact.php',
        'for_sale.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/for_sale.php',
        'login.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/login.php',
        'logout.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/logout.php',
        'image_proxy.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/image_proxy.php',

        // Admin sayfaları
        'pages/admin/artwork_techniques.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/artwork_techniques.php',
        'pages/admin/artwork_edition_types.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/artwork_edition_types.php',
        'pages/admin/artwork_editions.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/artwork_editions.php',
        'pages/admin/artworks.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/artworks.php',
        'pages/admin/artwork_add.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/artwork_add.php',
        'pages/admin/artwork_edit.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/artwork_edit.php',
        'pages/admin/artwork_view.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/artwork_view.php',
        'pages/admin/artwork_locations.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/artwork_locations.php',
        'pages/admin/artwork_statuses.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/artwork_statuses.php',
        'pages/admin/customers.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/customers.php',
        'pages/admin/customer_add.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/customer_add.php',
        'pages/admin/customer_edit.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/customer_edit.php',
        'pages/admin/customer_view.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/customer_view.php',
        'pages/admin/orders.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/orders.php',
        'pages/admin/order_view.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/order_view.php',
        'pages/admin/verification_logs.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/verification_logs.php',
        'pages/admin/users.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/users.php',
        'pages/admin/user_add.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/user_add.php',
        'pages/admin/user_edit.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/user_edit.php',
        'pages/admin/settings.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/settings.php',
        'pages/admin/sidebar_menu.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/sidebar_menu.php',
        'pages/admin/dashboard.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/dashboard.php',

        // Template dosyaları
        'pages/admin/templates/header.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/templates/header.php',
        'pages/admin/templates/footer.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/templates/footer.php',
        'pages/admin/templates/sidebar.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/pages/admin/templates/sidebar.php',

        // Config dosyaları
        'config/db.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/config/db.php',

        // Include dosyaları
        'includes/functions.php' => 'https://raw.githubusercontent.com/burakq/artwork/main/includes/functions.php'
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
 
 
 