<?php ini_set("display_errors", 1); error_reporting(E_ALL); echo "Hata ayıklama sayfası çalışıyor<br>";

// includes klasörünün yolunu kontrol et
echo "Includes klasörü kontrolü:<br>";
if (file_exists('../../includes/functions.php')) {
    echo "../../includes/functions.php dosyası mevcut<br>";
} else {
    echo "../../includes/functions.php dosyası bulunamadı!<br>";
}

// Veritabanı bağlantısını test et
echo "<br>Veritabanı bağlantısı test ediliyor:<br>";
require_once '../../config/db.php';

try {
    $conn = connectDB();
    
    // Tabloları göster
    $result = $conn->query("SHOW TABLES");
    
    echo "<br>Tablolar:<br>";
    if ($result->num_rows > 0) {
        while($row = $result->fetch_row()) {
            echo "- " . $row[0] . "<br>";
        }
    } else {
        echo "Hiç tablo bulunamadı!<br>";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
?>
