<?php
// QR kod örneği oluşturma
header('Content-Type: image/png');

// Görsel boyutları
$width = 200;
$height = 200;

// Yeni görsel oluştur
$image = imagecreatetruecolor($width, $height);

// Renkler
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);

// Arka planı beyaz yap
imagefill($image, 0, 0, $white);

// Basit bir QR kod görünümü oluştur
$blockSize = 10;
$margin = 20;
$qrSize = $width - (2 * $margin);
$blocks = $qrSize / $blockSize;

// QR kod çerçevesi
imagerectangle($image, $margin - 1, $margin - 1, $width - $margin, $height - $margin, $black);

// Tesadüfi QR desen oluştur (örnek amaçlı)
// Gerçek QR kod ayrı bir kütüphane ile oluşturulmalıdır
srand(12345); // Tutarlı test deseni için

// Köşe işaretleyicileri (QR kodun köşelerindeki kareler)
// Sol üst köşe işaretleyici
imagefilledrectangle($image, $margin, $margin, $margin + 7*$blockSize, $margin + $blockSize, $black);
imagefilledrectangle($image, $margin, $margin + $blockSize, $margin + $blockSize, $margin + 7*$blockSize, $black);
imagefilledrectangle($image, $margin + 6*$blockSize, $margin + $blockSize, $margin + 7*$blockSize, $margin + 7*$blockSize, $black);
imagefilledrectangle($image, $margin + $blockSize, $margin + 6*$blockSize, $margin + 6*$blockSize, $margin + 7*$blockSize, $black);
imagefilledrectangle($image, $margin + 2*$blockSize, $margin + 2*$blockSize, $margin + 5*$blockSize, $margin + 5*$blockSize, $black);

// Sağ üst köşe işaretleyici
imagefilledrectangle($image, $width - $margin - 7*$blockSize, $margin, $width - $margin, $margin + $blockSize, $black);
imagefilledrectangle($image, $width - $margin - $blockSize, $margin + $blockSize, $width - $margin, $margin + 7*$blockSize, $black);
imagefilledrectangle($image, $width - $margin - 7*$blockSize, $margin + $blockSize, $width - $margin - 6*$blockSize, $margin + 7*$blockSize, $black);
imagefilledrectangle($image, $width - $margin - 6*$blockSize, $margin + 6*$blockSize, $width - $margin - $blockSize, $margin + 7*$blockSize, $black);
imagefilledrectangle($image, $width - $margin - 5*$blockSize, $margin + 2*$blockSize, $width - $margin - 2*$blockSize, $margin + 5*$blockSize, $black);

// Sol alt köşe işaretleyici
imagefilledrectangle($image, $margin, $height - $margin - $blockSize, $margin + 7*$blockSize, $height - $margin, $black);
imagefilledrectangle($image, $margin, $height - $margin - 7*$blockSize, $margin + $blockSize, $height - $margin - $blockSize, $black);
imagefilledrectangle($image, $margin + 6*$blockSize, $height - $margin - 7*$blockSize, $margin + 7*$blockSize, $height - $margin - $blockSize, $black);
imagefilledrectangle($image, $margin + $blockSize, $height - $margin - 7*$blockSize, $margin + 6*$blockSize, $height - $margin - 6*$blockSize, $black);
imagefilledrectangle($image, $margin + 2*$blockSize, $height - $margin - 5*$blockSize, $margin + 5*$blockSize, $height - $margin - 2*$blockSize, $black);

// Orta kısımda biraz rastgele veri
for($i = 0; $i < $blocks; $i++) {
    for($j = 0; $j < $blocks; $j++) {
        // Köşe işaretçilerinin olduğu bölgeleri atla
        $isCorner = 
            // Sol üst köşe
            (($i < 7 && $j < 7) ||
            // Sağ üst köşe
            ($i > $blocks - 8 && $j < 7) ||
            // Sol alt köşe
            ($i < 7 && $j > $blocks - 8));
            
        if (!$isCorner && rand(0, 100) < 30) {
            $x = $margin + $i * $blockSize;
            $y = $margin + $j * $blockSize;
            imagefilledrectangle($image, $x, $y, $x + $blockSize - 1, $y + $blockSize - 1, $black);
        }
    }
}

// QR kod ortasına bir metin ekleyelim
imagestring($image, 2, 75, 90, "ÖRNEK", $black);

// PNG olarak çıktı ver
imagepng($image);
imagedestroy($image);
?> 