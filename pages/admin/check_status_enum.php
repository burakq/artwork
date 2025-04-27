<?php
require_once '../../includes/functions.php';

// Veritabanı bağlantısı
$conn = connectDB();

// Artworks tablosunun yapısını ve status kolonunun türünü kontrol et
$sql = "SHOW COLUMNS FROM artworks WHERE Field = 'status'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $column = $result->fetch_assoc();
    
    echo "<h2>Status Kolonu ENUM Değerleri:</h2>";
    echo "<pre>";
    print_r($column);
    echo "</pre>";
    
    // ENUM değerlerini ayıkla
    $type = $column['Type'];
    preg_match("/^enum\((.*)\)$/", $type, $matches);
    
    if (isset($matches[1])) {
        $values = explode(',', $matches[1]);
        $cleanValues = array();
        
        foreach ($values as $value) {
            // Tırnak işaretlerini temizle
            $cleanValues[] = trim($value, "'\"");
        }
        
        echo "<h3>ENUM Değerleri:</h3>";
        echo "<ul>";
        foreach ($cleanValues as $value) {
            echo "<li>" . htmlspecialchars($value) . "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "Status kolonunun bilgileri bulunamadı.";
}

// Mevcut eser durumlarını kontrol et
$sql = "SELECT * FROM artwork_statuses";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<h2>Artwork Statuses Tablosundaki Değerler:</h2>";
    echo "<table border='1' cellpadding='10'>
            <tr>
                <th>ID</th>
                <th>Status Key</th>
                <th>Status Name</th>
                <th>Is Active</th>
            </tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row['id'] . "</td>
                <td>" . htmlspecialchars($row['status_key']) . "</td>
                <td>" . htmlspecialchars($row['status_name']) . "</td>
                <td>" . ($row['is_active'] ? 'Evet' : 'Hayır') . "</td>
              </tr>";
    }
    
    echo "</table>";
} else {
    echo "Artwork Statuses tablosunda kayıt bulunamadı.";
}

// Bağlantıyı kapat
$conn->close();
?> 