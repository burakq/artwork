<?php
// Oturum kontrolü
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Giriş yapmamış kullanıcıları login sayfasına yönlendir
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../../login.php');
    exit;
}

// Sipariş ID kontrol
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Geçersiz sipariş ID.");
}

$order_id = intval($_GET['id']);

// Veritabanı bağlantısı
$conn = connectDB();

// Site URL'sini ve logo yolunu al
$site_url = "";
$logo_url = "";
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_url', 'site_logo')");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if ($row['setting_key'] == 'site_url') {
        $site_url = $row['setting_value'];
    } elseif ($row['setting_key'] == 'site_logo') {
        $logo_url = $site_url . '/' . $row['setting_value'];
    }
}

// Sipariş bilgilerini getir
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    die("Sipariş bulunamadı.");
}

$order = $order_result->fetch_assoc();

// Müşteri bilgilerini getir
$customer_id = $order['customer_id'];
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer_result = $stmt->get_result();
$customer = $customer_result->fetch_assoc();

$conn->close();

// Adres bilgisini tek satırda hazırla
$address_parts = [];
$address_parts[] = $customer['shipping_address'] ?? $customer['address'];
if (!empty($customer['shipping_postal_code'])) {
    $address_parts[] = $customer['shipping_postal_code'];
}
if (!empty($customer['shipping_city'])) {
    $address_parts[] = $customer['shipping_city'];
}
if (!empty($customer['shipping_state'])) {
    $address_parts[] = $customer['shipping_state'];
}
$address_parts[] = $customer['shipping_country'] ?? 'Türkiye';
$full_address = implode(', ', array_filter($address_parts));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kargo Etiketi</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: Arial;
        }
        .label {
            width: 21cm; /* A4 genişliği */
            height: 3cm;
            border: 1px solid black;
            padding: 5mm;
            position: absolute;
            left: 0;
            top: 0;
            font-size: 8pt;
            line-height: 1.2;
            display: flex;
            align-items: center;
        }
        .logo-container {
            width: 2cm;
            height: 2cm;
            margin-right: 5mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .content {
            flex: 1;
        }
        .label h2 {
            font-size: 10pt;
            text-align: center;
            margin: 0 0 2mm 0;
            color: #f05657;
            border-bottom: 1px solid #f05657;
            padding-bottom: 1mm;
        }
        @media print {
            .label {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <div class="label">
        <div class="logo-container">
            <img src="https://<?php echo $logo_url; ?>" alt="Logo" class="logo">
        </div>
        <div class="content">
            <h2>GÖNDERİM ADRESİ</h2>
            <div class="recipient" style="font-size: 14pt; font-weight: bold; margin-bottom: 1mm;">
                <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                <?php if (!empty($customer['company_name'])): ?>
                    <br><?php echo htmlspecialchars($customer['company_name']); ?>
                <?php endif; ?>
            </div>
            <div class="address" style="font-size: 17px; margin-bottom: 1mm;">
                <?php echo htmlspecialchars($full_address); ?>
            </div>
            <div class="contact" style="font-size: 14px; margin-bottom: 1mm;">
                <strong>Tel:</strong> <?php echo htmlspecialchars($customer['phone']); ?><br>
                <strong>E-posta:</strong> <?php echo htmlspecialchars($customer['email']); ?>
            </div>
        </div>
    </div>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html> 