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
    // GitHub API URL'si
    $apiUrl = 'https://api.github.com/repos/burakq/artwork/contents';
    
    // cURL ile GitHub API'sine istek gönder
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP/ArtworkAuth');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/vnd.github.v3+json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('GitHub API\'sine erişilemedi. HTTP Kodu: ' . $httpCode);
    }

    // Dosya listesini oluştur
    $files = [];
    
    // Recursive olarak dosyaları listele
    function getFiles($path, $baseUrl) {
        global $files;
        
        $apiUrl = 'https://api.github.com/repos/burakq/artwork/contents/' . $path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP/ArtworkAuth');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/vnd.github.v3+json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $items = json_decode($response, true);
            foreach ($items as $item) {
                if ($item['type'] === 'file') {
                    // Sadece PHP, JS, CSS, HTML ve TXT dosyalarını al
                    $ext = pathinfo($item['name'], PATHINFO_EXTENSION);
                    if (in_array($ext, ['php', 'js', 'css', 'html', 'txt'])) {
                        $files[$item['path']] = 'https://raw.githubusercontent.com/burakq/artwork/main/' . $item['path'];
                    }
                } elseif ($item['type'] === 'dir') {
                    // Alt dizinleri kontrol et
                    getFiles($item['path'], $baseUrl);
                }
            }
        }
    }

    // Ana dizinden başla
    getFiles('', $apiUrl);

    // Sadece config/db.php dosyasını atla
    unset($files['config/db.php']);

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
            $targetPath = '../../' . $localPath;
            if (file_put_contents($targetPath, $content) === false) {
                throw new Exception("$localPath dosyası yazılamadı (Hedef: $targetPath)");
            }
            $output[] = "$localPath başarıyla güncellendi";
        } else {
            throw new Exception("$localPath dosyası indirilemedi (URL: $remoteUrl)");
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
 
 
 