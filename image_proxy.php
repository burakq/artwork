<?php
// Resim proxy - CORS hatasını önlemek için
error_reporting(E_ALL);
ini_set('display_errors', 1);

// İşlem başlangıcını log'a yaz
error_log('Proxy başlatıldı. İstek detayları: IP=' . $_SERVER['REMOTE_ADDR'] . ', URL=' . (isset($_GET['url']) ? $_GET['url'] : 'URL yok'));

// URL parametresi zorunlu
if (!isset($_GET['url']) || empty($_GET['url'])) {
    header('HTTP/1.1 400 Bad Request');
    error_log('Proxy hata: URL parametresi eksik');
    exit('URL parametresi gerekli');
}

// URL'i güvenli şekilde al
$url = trim($_GET['url']);
error_log('Proxy işlemde: ' . $url);

// İzin verilen domainler - genişletilmiş
$allowedDomains = [
    'devrimerbil.com', 
    'sanatgalerisi.com', 
    'www.devrimerbil.com', 
    'www.sanatgalerisi.com', 
    'i.imgur.com',
    'imgur.com',
    'picsum.photos',
    'loremflickr.com',
    'placeimg.com',
    'unsplash.com',
    'images.unsplash.com',
    'source.unsplash.com',
    'cloudinary.com',
    'res.cloudinary.com',
    'cloudflare-ipfs.com',
    'googleusercontent.com',
    'ggpht.com',
    'artworkauth.com'
];

// Geçerli domain kontrolü
$parsedUrl = parse_url($url);
if (!isset($parsedUrl['host']) || empty($parsedUrl['host'])) {
    header('HTTP/1.1 400 Bad Request');
    error_log('Proxy hata: Geçersiz URL formatı - ' . $url);
    exit('Geçersiz URL formatı');
}

$isAllowed = false;
foreach ($allowedDomains as $domain) {
    if (stripos($parsedUrl['host'], $domain) !== false) {
        $isAllowed = true;
        break;
    }
}

if (!$isAllowed) {
    header('HTTP/1.1 403 Forbidden');
    error_log('Proxy güvenlik hatası: İzin verilmeyen domain - ' . $parsedUrl['host'] . ' - URL: ' . $url);
    exit('Sadece izin verilen domainlerden resimler gösterilebilir. Host: ' . $parsedUrl['host']);
}

// Önbellek kontrolü - daha önce indirilmiş mi?
$cacheDir = __DIR__ . '/cache/';
if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

$cacheFile = $cacheDir . md5($url);
$useCache = false;

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 86400)) { // 1 gün önbellek
    $imageData = file_get_contents($cacheFile);
    $useCache = true;
    error_log('Proxy: Önbellekten okundu - ' . $url);
} else {
    // Resmi indir - geliştirilmiş curl ayarları
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
    
    // Referrer ekle
    curl_setopt($ch, CURLOPT_REFERER, 'https://' . $_SERVER['HTTP_HOST']);
    
    // HTTP2 desteği (varsa)
    if (defined('CURL_HTTP_VERSION_2_0')) {
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
    }
    
    $imageData = curl_exec($ch);

    if ($imageData === false) {
        $errorMsg = curl_error($ch);
        $errorCode = curl_errno($ch);
        error_log('Proxy curl hatası: Kod:' . $errorCode . ' Mesaj:' . $errorMsg . ' URL: ' . $url);
        header('HTTP/1.1 500 Internal Server Error');
        exit('Resim indirme hatası: ' . $errorMsg . ' (Kod: ' . $errorCode . ')');
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    error_log('Proxy cevap: HTTP ' . $httpCode . ', ContentType: ' . $contentType . ', Boyut: ' . $contentLength . ' Final URL: ' . $finalUrl);

    // Hata durumunda
    if ($httpCode != 200) {
        error_log('Proxy HTTP hata: ' . $httpCode . ' URL: ' . $url);
        header('HTTP/1.1 404 Not Found');
        exit('Resim bulunamadı (HTTP ' . $httpCode . ')');
    }

    // Boş yanıt kontrolü
    if (empty($imageData) || strlen($imageData) < 100) {
        error_log('Proxy veri hatası: Boş veya çok küçük veri alındı (' . strlen($imageData) . ' bytes) URL: ' . $url);
        header('HTTP/1.1 404 Not Found');
        exit('Geçersiz resim verisi: Boş veya eksik veri');
    }

    // Resmi önbelleğe kaydet
    file_put_contents($cacheFile, $imageData);
    error_log('Proxy: Resim önbelleğe kaydedildi - ' . $url);
}

// ContentType belirleme
if (!$useCache) {
    if (empty($contentType)) {
        // ContentType boşsa otomatik belirlemeye çalışalım
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $contentType = $finfo->buffer($imageData);
        error_log('Proxy: Content-Type belirlendi: ' . $contentType . ' URL: ' . $url);
    }

    if (strpos($contentType, 'image/') === false) {
        error_log('Proxy içerik türü image/ değil: ' . $contentType . ' URL: ' . $url);
        // Dosya uzantısına göre content type belirle
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        if ($extension == 'jpg' || $extension == 'jpeg') {
            $contentType = 'image/jpeg';
        } elseif ($extension == 'png') {
            $contentType = 'image/png';
        } elseif ($extension == 'gif') {
            $contentType = 'image/gif';
        } elseif ($extension == 'webp') {
            $contentType = 'image/webp';
        } else {
            $contentType = 'image/jpeg'; // Varsayılan
        }
        error_log('Proxy: Content-Type düzeltildi: ' . $contentType . ' URL: ' . $url);
    }
} else {
    // Önbellekten okuma durumunda mime type'ı belirle
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $contentType = $finfo->buffer($imageData);
}

// HTTP başlıkları ekleme
header('HTTP/1.1 200 OK');
header('Content-Type: ' . $contentType);
header('Content-Length: ' . strlen($imageData));
header('Cache-Control: public, max-age=86400'); // 1 gün önbellek
header('Access-Control-Allow-Origin: *'); // CORS izni
header('X-Proxy-Source: ArtworkAuth');
header('X-Original-Url: ' . $url);
header('Vary: Accept-Encoding');

echo $imageData;
error_log('Proxy tamamlandı: ' . $url);
?> 