<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Giriş yapılmış mı kontrol et
if (!isLoggedIn()) {
    die(json_encode(['status' => 'error', 'message' => 'Oturum açmanız gerekiyor']));
}

// Admin yetkisi kontrolü
if (!isAdmin()) {
    die(json_encode(['status' => 'error', 'message' => 'Bu işlem için yetkiniz yok']));
}

// Versiyon dosyası yolu
$versionFile = '../../version.txt';

// Mevcut versiyonu al
$currentVersion = '';
if (file_exists($versionFile)) {
    $currentVersion = trim(file_get_contents($versionFile));
}

// Eğer versiyon dosyası okunamadıysa veya boşsa varsayılan değer ata
if (empty($currentVersion)) {
    $currentVersion = 'v1.0.0';
}

// GitHub'dan en son versiyonu al
$latestVersion = '';
$githubUrl = 'https://raw.githubusercontent.com/burakq/artwork/main/version.txt';

// cURL ile GitHub'dan versiyon dosyasını al
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $githubUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'PHP/ArtworkAuth');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/vnd.github.v3.raw'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Hata ayıklama bilgileri
$debug = [
    'current_version' => $currentVersion,
    'github_url' => $githubUrl,
    'http_code' => $httpCode,
    'curl_error' => $curlError,
    'response' => $response
];

if ($httpCode === 200 && $response !== false) {
    $latestVersion = trim($response);
    
    // Sürüm numaralarını karşılaştır
    if (version_compare($latestVersion, $currentVersion, '>')) {
        echo json_encode([
            'status' => 'update',
            'message' => 'Yeni bir güncelleme mevcut',
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'debug' => $debug
        ]);
    } else {
        echo json_encode([
            'status' => 'current',
            'message' => 'Sistem güncel',
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'debug' => $debug
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'GitHub bağlantısı kurulamadı: ' . ($curlError ?: 'HTTP Kodu: ' . $httpCode),
        'current_version' => $currentVersion,
        'latest_version' => $currentVersion,
        'debug' => $debug
    ]);
} 
 
 
 