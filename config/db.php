<?php
// Hata gösterimi
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Veritabanı bağlantı ayarları
define('DB_HOST', 'localhost');
define('DB_PORT', '8889');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'artwork_auth');

// MySQLi bağlantısı oluşturma
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    // Bağlantı kontrolü
    if ($conn->connect_error) {
        die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// PDO bağlantısı (alternatif)
function connectPDO() {
    try {
        $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die("Veritabanı bağlantısı başarısız: " . $e->getMessage());
    }
}
?> 