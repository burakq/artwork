<?php
// Gri placeholder görsel oluşturma
// Bu dosya çağrıldığında 100x100 boyutunda gri bir görsel döndürür

header('Content-Type: image/png');
$image = imagecreatetruecolor(100, 100);
$grey = imagecolorallocate($image, 240, 240, 240);
imagefill($image, 0, 0, $grey);

// PNG olarak çıktı ver
imagepng($image);
imagedestroy($image);
?> 