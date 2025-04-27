<?php
// Modern sertifika şablonu görsel oluşturma
header('Content-Type: image/png');

// Görsel boyutları (A4)
$width = 794;   // 210mm (A4 genişlik)
$height = 1123; // 297mm (A4 yükseklik)

// Yeni görsel oluştur
$image = imagecreatetruecolor($width, $height);

// Renkler
$background = imagecolorallocate($image, 240, 247, 255); // Açık mavi arka plan
$border = imagecolorallocate($image, 51, 122, 183);      // Mavi kenarlık
$text = imagecolorallocate($image, 51, 51, 51);          // Koyu gri metin
$accent = imagecolorallocate($image, 0, 123, 255);       // Vurgu rengi

// Arka planı doldur
imagefill($image, 0, 0, $background);

// Üst kısımda renkli şerit
imagefilledrectangle($image, 0, 0, $width, 80, $accent);

// Alt kısımda renkli şerit
imagefilledrectangle($image, 0, $height - 80, $width, $height, $accent);

// Başlık yazısı
$title = "Orijinallik Sertifikası";
$font = realpath(__DIR__ . '/../fonts/arial.ttf');

// Font yüklenmişse TTF kullanalım, yoksa basit metin
if ($font) {
    $fontSize = 40;
    $titleColor = imagecolorallocate($image, 255, 255, 255); // Beyaz başlık
    $titleBox = imagettfbbox($fontSize, 0, $font, $title);
    $titleWidth = $titleBox[2] - $titleBox[0];
    $titleX = ($width - $titleWidth) / 2;
    imagettftext($image, $fontSize, 0, $titleX, 55, $titleColor, $font, $title);
} else {
    // TTF kullanılamıyorsa basit metin
    $titleColor = imagecolorallocate($image, 255, 255, 255);
    $titleX = ($width - strlen($title) * 10) / 2;
    imagestring($image, 5, $titleX, 30, $title, $titleColor);
}

// İnce çizgi çiz
imageline($image, 100, 150, $width - 100, 150, $accent);

// Örnek içerik alanı - placeholder
$contentY = 200;
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

// QR Kod placeholder - modern stilde
$qrSize = 180;
$qrX = ($width - $qrSize) / 2;
$qrY = $contentY + 50;

// QR kod arka planı
imagefilledrectangle($image, $qrX - 10, $qrY - 10, $qrX + $qrSize + 10, $qrY + $qrSize + 10, imagecolorallocate($image, 255, 255, 255));
imagerectangle($image, $qrX - 10, $qrY - 10, $qrX + $qrSize + 10, $qrY + $qrSize + 10, $accent);
imagerectangle($image, $qrX, $qrY, $qrX + $qrSize, $qrY + $qrSize, $accent);
imagestring($image, 3, $qrX + 40, $qrY + 80, "QR KOD ALANI", $text);

// Alt bilgi
$footer = "Bu sertifika, eserin orijinalliğini doğrulamak için düzenlenmiştir.";
if ($font) {
    $fontSize = 14;
    $footerColor = imagecolorallocate($image, 255, 255, 255);
    $textBox = imagettfbbox($fontSize, 0, $font, $footer);
    $textWidth = $textBox[2] - $textBox[0];
    $textX = ($width - $textWidth) / 2;
    imagettftext($image, $fontSize, 0, $textX, $height - 40, $footerColor, $font, $footer);
} else {
    $footerColor = imagecolorallocate($image, 255, 255, 255);
    $textX = ($width - strlen($footer) * 5) / 2;
    imagestring($image, 2, $textX, $height - 50, $footer, $footerColor);
}

// PNG olarak çıktı ver
imagepng($image);
imagedestroy($image);
?> 