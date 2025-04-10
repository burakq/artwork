<?php
// Hata raporlamayı etkinleştir
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Oturum kontrolü
session_start();
echo "<h2>Oturum Bilgileri:</h2>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

echo "<h2>GET Parametreleri:</h2>";
echo "<pre>";
var_dump($_GET);
echo "</pre>";

// Veritabanı bağlantısı
require_once '../../config/db.php';
require_once '../../includes/functions.php';

echo "<h2>Veritabanı Kontrolü:</h2>";
try {
    $conn = connectDB();
    echo "Veritabanı bağlantısı başarılı.<br>";
    
    // Sipariş kontrolü
    $order_id = isset($_GET['id']) ? intval($_GET['id']) : 1;
    echo "Kontrol edilecek sipariş ID: $order_id<br>";
    
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_result = $stmt->get_result();
    
    if ($order_result->num_rows === 0) {
        echo "Sipariş bulunamadı!<br>";
    } else {
        echo "Sipariş bulundu:<br>";
        $order = $order_result->fetch_assoc();
        echo "<pre>";
        print_r($order);
        echo "</pre>";
        
        // Müşteri bilgisini al
        $customer_id = $order['customer_id'];
        $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $customer_result = $stmt->get_result();
        
        if ($customer_result->num_rows === 0) {
            echo "Müşteri bulunamadı!<br>";
        } else {
            echo "Müşteri bilgileri:<br>";
            $customer = $customer_result->fetch_assoc();
            echo "<pre>";
            print_r($customer);
            echo "</pre>";
        }
        
        // Sipariş ürünlerini al
        $stmt = $conn->prepare("SELECT oa.*, a.title, a.artist_name, a.image_path, a.verification_code 
                             FROM order_artwork oa 
                             JOIN artworks a ON oa.artwork_id = a.id 
                             WHERE oa.order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order_items_result = $stmt->get_result();
        
        if ($order_items_result->num_rows === 0) {
            echo "Sipariş ürünleri bulunamadı!<br>";
        } else {
            echo "Sipariş ürünleri:<br>";
            $items = [];
            while ($item = $order_items_result->fetch_assoc()) {
                $items[] = $item;
            }
            echo "<pre>";
            print_r($items);
            echo "</pre>";
        }
    }
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
?> 