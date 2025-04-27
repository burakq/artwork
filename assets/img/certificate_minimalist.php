<?php
// Minimalist sertifika şablonu görsel oluşturma
header('Content-Type: image/png');

// Görsel boyutları (A4)
$width = 794;   // 210mm (A4 genişlik)
$height = 1123; // 297mm (A4 yükseklik)

// Yeni görsel oluştur
$image = imagecreatetruecolor($width, $height);

// Renkler
$background = imagecolorallocate($image, 248, 249, 250); // Çok açık gri arka plan
$textColor = imagecolorallocate($image, 33, 37, 41);     // Koyu gri metin
$accent = imagecolorallocate($image, 108, 117, 125);     // Orta gri aksent rengi
$light = imagecolorallocate($image, 222, 226, 230);      // Açık gri

// Arka planı doldur
imagefill($image, 0, 0, $background);

// İnce kenarlık
imagerectangle($image, 10, 10, $width - 10, $height - 10, $light);

// Başlık yazısı
$title = "Orijinallik Sertifikası";
$font = realpath(__DIR__ . '/../fonts/helvetica.ttf');

// Font yüklenmişse TTF kullanalım, yoksa basit metin
if ($font) {
    $fontSize = 36;
    $titleBox = imagettfbbox($fontSize, 0, $font, $title);
    $titleWidth = $titleBox[2] - $titleBox[0];
    $titleX = ($width - $titleWidth) / 2;
    imagettftext($image, $fontSize, 0, $titleX, 120, $textColor, $font, $title);
    
    // İnce yatay çizgi
    imageline($image, $titleX, 150, $titleX + $titleWidth, 150, $accent);
} else {
    // TTF kullanılamıyorsa basit metin
    $titleX = ($width - strlen($title) * 10) / 2;
    imagestring($image, 5, $titleX, 100, $title, $textColor);
    
    // İnce yatay çizgi
    imageline($image, $titleX, 120, $titleX + strlen($title) * 10, 120, $accent);
}

// Örnek içerik alanı - placeholder
$contentY = 220;
$lineHeight = 40;

$lines = [
    "Bu belge ÖRNEK SANAT ESERİ'nin orijinalliğini doğrulamaktadır.",
    "",
    "Sanatçı: Örnek Sanatçı",
    "Boyut: 50x70 cm",
    "Teknik: Yağlı Boya",
    "Doğrulama Kodu: ABC123XYZ"
];

if ($font) {
    $fontSize = 18;
    foreach ($lines as $line) {
        if (empty($line)) {
            $contentY += $lineHeight / 2;
            continue;
        }
        $textBox = imagettfbbox($fontSize, 0, $font, $line);
        $textWidth = $textBox[2] - $textBox[0];
        $textX = ($width - $textWidth) / 2;
        imagettftext($image, $fontSize, 0, $textX, $contentY, $textColor, $font, $line);
        $contentY += $lineHeight;
    }
} else {
    foreach ($lines as $line) {
        if (empty($line)) {
            $contentY += 15;
            continue;
        }
        $textX = ($width - strlen($line) * 8) / 2;
        imagestring($image, 3, $textX, $contentY, $line, $textColor);
        $contentY += 30;
    }
}

// QR Kod placeholder - minimalist stilde
$qrSize = 150;
$qrX = ($width - $qrSize) / 2;
$qrY = $contentY + 70;

// QR kod kenar çizgisi
imagerectangle($image, $qrX, $qrY, $qrX + $qrSize, $qrY + $qrSize, $accent);
imagestring($image, 3, $qrX + 45, $qrY + 65, "QR KOD", $textColor);

// Alt bilgi
$footer = "Bu sertifika, eserin orijinalliğini doğrulamak için düzenlenmiştir.";
if ($font) {
    $fontSize = 12;
    $textBox = imagettfbbox($fontSize, 0, $font, $footer);
    $textWidth = $textBox[2] - $textBox[0];
    $textX = ($width - $textWidth) / 2;
    
    // Alt bilgi üzerinde ince çizgi
    imageline($image, $width / 4, $height - 120, $width * 3/4, $height - 120, $light);
    
    imagettftext($image, $fontSize, 0, $textX, $height - 90, $textColor, $font, $footer);
} else {
    $textX = ($width - strlen($footer) * 5) / 2;
    
    // Alt bilgi üzerinde ince çizgi
    imageline($image, $width / 4, $height - 100, $width * 3/4, $height - 100, $light);
    
    imagestring($image, 2, $textX, $height - 80, $footer, $textColor);
}

// PNG olarak çıktı ver
imagepng($image);
imagedestroy($image);
?> 