<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Giriş yapılmış mı kontrol et
if (!isLoggedIn()) {
    die(json_encode(['error' => 'Oturum açmanız gerekiyor']));
}

// Admin yetkisi kontrolü
if (!isAdmin()) {
    die(json_encode(['error' => 'Bu işlem için yetkiniz yok']));
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
curl_close($ch);

if ($httpCode === 200 && $response !== false) {
    $latestVersion = trim($response);
}

// Eğer GitHub'dan versiyon alınamadıysa varsayılan değer ata
if (empty($latestVersion)) {
    $latestVersion = $currentVersion;
}

// Güncelleme var mı kontrol et
$hasUpdates = version_compare($latestVersion, $currentVersion, '>');

// Sonuçları döndür
echo json_encode([
    'hasUpdates' => $hasUpdates,
    'currentVersion' => $currentVersion,
    'latestVersion' => $latestVersion,
    'debug' => [
        'php_version' => PHP_VERSION,
        'curl_available' => function_exists('curl_init'),
        'version_file_exists' => file_exists($versionFile),
        'http_code' => $httpCode,
        'response' => $response,
        'current_version_file' => $currentVersion,
        'latest_version_github' => $latestVersion
    ]
]); 
 
 
 