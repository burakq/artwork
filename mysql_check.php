<?php
// Config dosyasını dahil et
include_once 'config/db.php';

// Veritabanı bağlantısını kur
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

// Artworks tablosunun yapısını incele
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
}

// Status sütununun collation'ını kontrol et
echo "<h2>Artworks Tablosu Status Sütunu Collation:</h2>";
$result = $conn->query("SHOW FULL COLUMNS FROM artworks WHERE Field = 'status'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<pre>";
    print_r($row);
    echo "</pre>";
}

// artworks tablosundaki status değerlerini kontrol et
echo "<h2>Artworks Tablosundaki Status Değerleri:</h2>";
$result = $conn->query("SELECT DISTINCT status FROM artworks");
if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>{$row['status']}</li>";
    }
    echo "</ul>";
}

// Artwork_statuses tablosunun yapısını incele
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
}

// Status_key sütununun collation'ını kontrol et
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
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Status Key</th><th>Status Name</th><th>Is Active</th><th>Created At</th><th>Updated At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['status_key']}</td>";
        echo "<td>{$row['status_name']}</td>";
        echo "<td>{$row['is_active']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "<td>{$row['updated_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Bağlantıyı kapat
$conn->close();
?> 