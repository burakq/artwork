<?php
require_once 'includes/functions.php';

// Veritabanı bağlantısı
$conn = connectDB();

// Artworks tablosunun yapısını kontrol et
echo "<h2>Artworks Tablosu Yapısı:</h2>";
$result = $conn->query("DESCRIBE artworks");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Alan</th><th>Tip</th><th>Null</th><th>Anahtar</th><th>Varsayılan</th><th>Ekstra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Artworks tablosu bulunamadı.";
}

// Status sütunun collation'ını kontrol et
echo "<h2>Artworks Tablosu Status Sütunu Collation:</h2>";
$result = $conn->query("SHOW FULL COLUMNS FROM artworks WHERE Field = 'status'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<pre>";
    print_r($row);
    echo "</pre>";
}

// Artwork_statuses tablosunun yapısını kontrol et
echo "<h2>Artwork_statuses Tablosu Yapısı:</h2>";
$result = $conn->query("DESCRIBE artwork_statuses");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Alan</th><th>Tip</th><th>Null</th><th>Anahtar</th><th>Varsayılan</th><th>Ekstra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Artwork_statuses tablosu bulunamadı.";
}

// Status_key sütunun collation'ını kontrol et
echo "<h2>Artwork_statuses Tablosu Status_key Sütunu Collation:</h2>";
$result = $conn->query("SHOW FULL COLUMNS FROM artwork_statuses WHERE Field = 'status_key'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<pre>";
    print_r($row);
    echo "</pre>";
}

// Artwork_statuses tablosundaki verileri göster
echo "<h2>Artwork_statuses Tablosu Verileri:</h2>";
$result = $conn->query("SELECT * FROM artwork_statuses");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Status Key</th><th>Status Name</th><th>Is Active</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['status_key']}</td>";
        echo "<td>{$row['status_name']}</td>";
        echo "<td>{$row['is_active']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Artwork_statuses tablosunda veri bulunamadı.";
}

// Artworks tablosundaki status değerlerini kontrol et
echo "<h2>Artworks Tablosu Status Değerleri:</h2>";
$result = $conn->query("SELECT DISTINCT status FROM artworks");
if ($result && $result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>{$row['status']}</li>";
    }
    echo "</ul>";
} else {
    echo "Artworks tablosunda status değeri bulunamadı.";
}

// Bağlantıyı kapat
$conn->close();
?> 