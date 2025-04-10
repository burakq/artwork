<?php
// Hata raporlamayı etkinleştir (geliştirme aşamasında)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

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
    $_SESSION['error'] = "Geçersiz sipariş ID.";
    header('Location: orders.php');
    exit;
}

$order_id = intval($_GET['id']);

// Veritabanı bağlantısı
$conn = connectDB();

// Sipariş bilgilerini getir
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    $_SESSION['error'] = "Sipariş bulunamadı.";
    header('Location: orders.php');
    exit;
}

$order = $order_result->fetch_assoc();

// Müşteri bilgilerini getir
if (!empty($order)) {
    $customer_id = $order['customer_id'];
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $customer_result = $stmt->get_result();
    $customer = $customer_result->fetch_assoc();
}

// Sipariş ürünlerini getir
$order_items = [];
if (!empty($order)) {
    $stmt = $conn->prepare("SELECT oa.*, a.title, a.artist_name, a.image_path, a.verification_code 
                           FROM order_artwork oa 
                           JOIN artworks a ON oa.artwork_id = a.id 
                           WHERE oa.order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_items_result = $stmt->get_result();
    
    while ($item = $order_items_result->fetch_assoc()) {
        $order_items[] = $item;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Artwork Auth | Sipariş Detayı</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Custom CSS -->
    <link href="../../assets/css/style.css" rel="stylesheet">
    
    <style>
        /* A5 yazdırma stili */
        @media print {
            body * {
                visibility: hidden;
            }
            .print-section, .print-section * {
                visibility: visible;
            }
            .print-section {
                position: absolute;
                left: 0;
                top: 0;
                width: 148mm; /* A5 genişlik */
                height: 210mm; /* A5 yükseklik */
                padding: 10mm;
                font-size: 14pt;
                line-height: 1.5;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="dashboard.php" class="nav-link">Ana Sayfa</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="../../logout.php" role="button">
                    <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                </a>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="dashboard.php" class="brand-link">
            <span class="brand-text font-weight-light">Artwork Auth</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="info">
                    <a href="#" class="d-block"><?php echo $_SESSION['user_name']; ?></a>
                </div>
            </div>

            <?php include 'sidebar_menu.php'; ?>
        </div>
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Sipariş #<?php echo htmlspecialchars($order['order_number']); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
                            <li class="breadcrumb-item"><a href="orders.php">Siparişler</a></li>
                            <li class="breadcrumb-item active">Sipariş Detayı</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Sipariş Bilgileri</h3>
                                <div class="card-tools no-print">
                                    <button type="button" class="btn btn-sm btn-primary" onclick="window.location.href='order_edit.php?id=<?php echo $order_id; ?>'">
                                        <i class="fas fa-edit"></i> Düzenle
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <tr>
                                        <th style="width: 200px">Sipariş Numarası</th>
                                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Durum</th>
                                        <td>
                                            <?php 
                                            $status_classes = [
                                                'pending' => 'badge badge-warning',
                                                'processing' => 'badge badge-info',
                                                'shipped' => 'badge badge-primary',
                                                'delivered' => 'badge badge-success',
                                                'cancelled' => 'badge badge-danger'
                                            ];
                                            $status_texts = [
                                                'pending' => 'Beklemede',
                                                'processing' => 'İşlemde',
                                                'shipped' => 'Gönderildi',
                                                'delivered' => 'Teslim Edildi',
                                                'cancelled' => 'İptal Edildi'
                                            ];
                                            $status_class = isset($status_classes[$order['status']]) ? $status_classes[$order['status']] : 'badge badge-secondary';
                                            $status_text = isset($status_texts[$order['status']]) ? $status_texts[$order['status']] : $order['status'];
                                            ?>
                                            <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Toplam Tutar</th>
                                        <td><?php echo number_format($order['total_amount'], 2, ',', '.'); ?> TL</td>
                                    </tr>
                                    <tr>
                                        <th>Oluşturulma Tarihi</th>
                                        <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Son Güncelleme</th>
                                        <td><?php echo date('d.m.Y H:i', strtotime($order['updated_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Müşteri Bilgileri</h3>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <tr>
                                        <th style="width: 200px">Müşteri Adı</th>
                                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Müşteri Tipi</th>
                                        <td><?php echo isset($customer['type']) && $customer['type'] === 'individual' ? 'Bireysel' : 'Galeri'; ?></td>
                                    </tr>
                                    <?php if (!empty($customer['company_name'])): ?>
                                    <tr>
                                        <th>Şirket Adı</th>
                                        <td><?php echo htmlspecialchars($customer['company_name']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($customer['tax_number'])): ?>
                                    <tr>
                                        <th>Vergi Numarası</th>
                                        <td><?php echo htmlspecialchars($customer['tax_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Vergi Dairesi</th>
                                        <td><?php echo htmlspecialchars($customer['tax_office']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <th>E-posta</th>
                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Telefon</th>
                                        <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Adres</th>
                                        <td><?php echo nl2br(htmlspecialchars($customer['address'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Kargo Bilgileri</h3>
                                <div class="card-tools no-print">
                                    <button type="button" class="btn btn-sm btn-success" onclick="printShippingLabel()">
                                        <i class="fas fa-print"></i> Kargo Etiketi Yazdır
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="shipping-label" class="print-section">
                                    <div style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px;">
                                        <h2 style="text-align: center; margin-bottom: 20px;">GÖNDERİM ADRESİ</h2>
                                        <p style="font-size: 16px; font-weight: bold;">
                                            <?php echo htmlspecialchars($customer['name']); ?>
                                            <?php if (!empty($customer['company_name'])): ?>
                                                <br><?php echo htmlspecialchars($customer['company_name']); ?>
                                            <?php endif; ?>
                                        </p>
                                        <p style="font-size: 14px;">
                                            <?php echo nl2br(htmlspecialchars($customer['shipping_address'] ?? $customer['address'])); ?>
                                            <br>
                                            <?php 
                                                $shipping_parts = [];
                                                if (!empty($customer['shipping_postal_code'])) {
                                                    $shipping_parts[] = $customer['shipping_postal_code'];
                                                }
                                                if (!empty($customer['shipping_city'])) {
                                                    $shipping_parts[] = $customer['shipping_city'];
                                                }
                                                if (!empty($customer['shipping_state'])) {
                                                    $shipping_parts[] = $customer['shipping_state'];
                                                }
                                                if (!empty($shipping_parts)) {
                                                    echo implode(', ', $shipping_parts);
                                                }
                                            ?>
                                            <br>
                                            <?php echo htmlspecialchars($customer['shipping_country'] ?? 'Türkiye'); ?>
                                        </p>
                                        <p style="font-size: 14px;">
                                            <strong>Telefon:</strong> <?php echo htmlspecialchars($customer['phone']); ?><br>
                                            <strong>E-posta:</strong> <?php echo htmlspecialchars($customer['email']); ?>
                                        </p>
                                        <div style="margin-top: 20px; border-top: 1px dashed #ccc; padding-top: 10px;">
                                            <p><strong>Sipariş No:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                                            <p><strong>Toplam Tutar:</strong> <?php echo number_format($order['total_amount'], 2, ',', '.'); ?> TL</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <table class="table table-bordered no-print">
                                    <tr>
                                        <th style="width: 200px">Alıcı Adı</th>
                                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Gönderim Adresi</th>
                                        <td><?php echo nl2br(htmlspecialchars($customer['shipping_address'] ?? $customer['address'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Şehir / İlçe</th>
                                        <td>
                                            <?php echo htmlspecialchars($customer['shipping_city'] ?? ''); ?>
                                            <?php if (!empty($customer['shipping_state'])): ?>
                                                / <?php echo htmlspecialchars($customer['shipping_state']); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Posta Kodu</th>
                                        <td><?php echo htmlspecialchars($customer['shipping_postal_code'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Ülke</th>
                                        <td><?php echo htmlspecialchars($customer['shipping_country'] ?? 'Türkiye'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Kargo Durumu</th>
                                        <td><?php echo isset($status_texts[$order['status']]) ? $status_texts[$order['status']] : $order['status']; ?></td>
                                    </tr>
                                    <?php if (!empty($order['tracking_number'])): ?>
                                    <tr>
                                        <th>Takip Numarası</th>
                                        <td><?php echo htmlspecialchars($order['tracking_number']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">İşlemler</h3>
                            </div>
                            <div class="card-body no-print">
                                <a href="order_edit.php?id=<?php echo $order_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Siparişi Düzenle
                                </a>
                                <?php if ($order['status'] === 'pending'): ?>
                                <a href="orders.php?action=process&id=<?php echo $order_id; ?>" class="btn btn-info">
                                    <i class="fas fa-cogs"></i> İşleme Al
                                </a>
                                <?php endif; ?>
                                <?php if ($order['status'] === 'processing'): ?>
                                <a href="orders.php?action=ship&id=<?php echo $order_id; ?>" class="btn btn-success">
                                    <i class="fas fa-shipping-fast"></i> Gönderildi Olarak İşaretle
                                </a>
                                <?php endif; ?>
                                <?php if ($order['status'] === 'shipped'): ?>
                                <a href="orders.php?action=deliver&id=<?php echo $order_id; ?>" class="btn btn-success">
                                    <i class="fas fa-check"></i> Teslim Edildi Olarak İşaretle
                                </a>
                                <?php endif; ?>
                                <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'delivered'): ?>
                                <a href="orders.php?action=cancel&id=<?php echo $order_id; ?>" class="btn btn-danger" onclick="return confirm('Bu siparişi iptal etmek istediğinizden emin misiniz?');">
                                    <i class="fas fa-times"></i> Siparişi İptal Et
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Sipariş Edilen Eserler</h3>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-hover text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Görsel</th>
                                            <th>Eser Adı</th>
                                            <th>Sanatçı</th>
                                            <th>Doğrulama Kodu</th>
                                            <th>Fiyat</th>
                                            <th class="no-print">İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td><?php echo $item['artwork_id']; ?></td>
                                            <td>
                                                <?php if (!empty($item['image_path'])): ?>
                                                <img src="../../<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" style="max-width: 50px; max-height: 50px;">
                                                <?php else: ?>
                                                <img src="../../assets/img/no-image.png" alt="Görsel Yok" style="max-width: 50px; max-height: 50px;">
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['title']); ?></td>
                                            <td><?php echo htmlspecialchars($item['artist_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['verification_code']); ?></td>
                                            <td><?php echo number_format($item['price'], 2, ',', '.'); ?> TL</td>
                                            <td class="no-print">
                                                <a href="artwork_view.php?id=<?php echo $item['artwork_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> Görüntüle
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
    
    <footer class="main-footer no-print">
        <div class="float-right d-none d-sm-block">
            <b>Version</b> 1.0.0
        </div>
        <strong>&copy; <?php echo date('Y'); ?> Sanat Eseri Doğrulama Sistemi</strong>
    </footer>
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- Yazdırma İşlevi -->
<script>
function printShippingLabel() {
    window.print();
}
</script>
</body>
</html> 