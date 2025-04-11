<?php
header('Content-Type: image/jpeg');
header('Cache-Control: max-age=86400, public');

// Güvenlik kontrolü
$allowed_domains = [
    'www.devrimerbil.com',
    'devrimerbil.com',
    'artstation.com', 
    'cdna.artstation.com'
];

$url = isset($_GET['url']) ? $_GET['url'] : '';

if (empty($url)) {
    die('URL parametresi gerekli.');
}

// URL'nin decode edilmesi
$url = urldecode($url);

// URL'nin güvenli olup olmadığının kontrolü
$parsed_url = parse_url($url);
$host = isset($parsed_url['host']) ? $parsed_url['host'] : '';

$is_allowed = false;
foreach ($allowed_domains as $domain) {
    if (strtolower($host) === strtolower($domain) || strtolower(substr($host, -strlen($domain)-1)) === strtolower('.'.$domain)) {
        $is_allowed = true;
        break;
    }
}

if (!$is_allowed) {
    die('Bu domain için erişim izni yok.');
}

// Görüntüyü getir ve doğrudan çıktıla
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

$image_data = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    echo $image_data;
} else {
    // Görüntü bulunamadıysa hata resmi göster
    $error_image = imagecreate(400, 300);
    $background = imagecolorallocate($error_image, 240, 240, 240);
    $text_color = imagecolorallocate($error_image, 220, 30, 30);
    
    imagestring($error_image, 5, 100, 130, 'Görüntü bulunamadı', $text_color);
    imagestring($error_image, 3, 100, 150, 'HTTP Hata Kodu: ' . $http_code, $text_color);
    
    imagejpeg($error_image);
    imagedestroy($error_image);
}
?> 
 
 
 
 