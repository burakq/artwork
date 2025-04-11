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

// GitHub API URL'si
$githubApiUrl = 'https://api.github.com/repos/burakq/artwork/commits/main';

// cURL ile GitHub API'sine istek gönder
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $githubApiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'PHP/ArtworkAuth');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/vnd.github.v3+json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die(json_encode([
        'error' => 'GitHub API\'sine erişilemedi',
        'httpCode' => $httpCode
    ]));
}

// Mevcut commit hash'ini al
$currentCommit = trim(shell_exec('git rev-parse HEAD'));

// GitHub'dan gelen en son commit hash'ini al
$githubData = json_decode($response, true);
$latestCommit = $githubData['sha'];

// Güncelleme var mı kontrol et
$hasUpdates = $currentCommit !== $latestCommit;

// Versiyon bilgilerini al
$currentVersion = trim(shell_exec('git describe --tags --always'));
$latestVersion = $githubData['commit']['message'];

// Sonuçları döndür
echo json_encode([
    'hasUpdates' => $hasUpdates,
    'currentVersion' => $currentVersion,
    'latestVersion' => $latestVersion,
    'currentCommit' => $currentCommit,
    'latestCommit' => $latestCommit
]); 
 
 
 