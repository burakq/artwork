<?php
// Klasik sertifika şablonu görsel oluşturma
header('Content-Type: image/png');

// Görsel boyutları (A4)
$width = 794;   // 210mm (A4 genişlik)
$height = 1123; // 297mm (A4 yükseklik)

// Yeni görsel oluştur
$image = imagecreatetruecolor($width, $height);

// Renkler
$background = imagecolorallocate($image, 255, 255, 255); // Beyaz arka plan
$border = imagecolorallocate($image, 0, 0, 0);           // Siyah kenarlık
$text = imagecolorallocate($image, 0, 0, 0);             // Siyah metin

// Arka planı doldur
imagefill($image, 0, 0, $background);

// Kenarlık çiz (5px genişliğinde)
$borderWidth = 5;
for ($i = 0; $i < $borderWidth; $i++) {
    imagerectangle($image, $i, $i, $width - 1 - $i, $height - 1 - $i, $border);
}

// İkinci iç kenarlık çiz
$margin = 30;
imagerectangle($image, $margin, $margin, $width - $margin - 1, $height - $margin - 1, $border);

// Başlık yazısı
$title = "Orijinallik Sertifikası";
$font = realpath(__DIR__ . '/../fonts/times.ttf');

// Font yüklenmişse TTF kullanalım, yoksa basit metin
if ($font) {
    $fontSize = 40;
    $titleBox = imagettfbbox($fontSize, 0, $font, $title);
    $titleWidth = $titleBox[2] - $titleBox[0];
    $titleX = ($width - $titleWidth) / 2;
    imagettftext($image, $fontSize, 0, $titleX, 150, $text, $font, $title);
} else {
    // TTF kullanılamıyorsa basit metin
    $titleX = ($width - strlen($title) * 10) / 2;
    imagestring($image, 5, $titleX, 100, $title, $text);
}

// Örnek içerik alanı - placeholder
$contentY = 250;
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
    $fontSize = 20;
    foreach ($lines as $line) {
        $textBox = imagettfbbox($fontSize, 0, $font, $line);
        $textWidth = $textBox[2] - $textBox[0];
        $textX = ($width - $textWidth) / 2;
        imagettftext($image, $fontSize, 0, $textX, $contentY, $text, $font, $line);
        $contentY += $lineHeight;
    }
} else {
    foreach ($lines as $line) {
        $textX = ($width - strlen($line) * 8) / 2;
        imagestring($image, 3, $textX, $contentY, $line, $text);
        $contentY += 30;
    }
}

// QR Kod placeholder
$qrSize = 200;
$qrX = ($width - $qrSize) / 2;
$qrY = $contentY + 50;
imagerectangle($image, $qrX, $qrY, $qrX + $qrSize, $qrY + $qrSize, $border);
imagestring($image, 3, $qrX + 50, $qrY + 90, "QR KOD", $text);

// Alt bilgi
$footer = "Bu sertifika, eserin orijinalliğini doğrulamak için düzenlenmiştir.";
if ($font) {
    $fontSize = 14;
    $textBox = imagettfbbox($fontSize, 0, $font, $footer);
    $textWidth = $textBox[2] - $textBox[0];
    $textX = ($width - $textWidth) / 2;
    imagettftext($image, $fontSize, 0, $textX, $height - 100, $text, $font, $footer);
} else {
    $textX = ($width - strlen($footer) * 5) / 2;
    imagestring($image, 2, $textX, $height - 100, $footer, $text);
}

// PNG olarak çıktı ver
imagepng($image);
imagedestroy($image);
?> 