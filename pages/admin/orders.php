<?php
session_start();
require_once '../../includes/functions.php';

// Giriş yapılmış mı kontrol et
if (!isLoggedIn()) {
    redirect('../../login.php');
}

// Admin yetkisi kontrolü
if (!isAdmin()) {
    redirect('../../index.php');
}

// Veritabanı bağlantısı
$conn = connectDB();

// İşlem mesajları
$message = '';
$message_type = '';

// Sipariş durumunu güncelle
if (isset($_GET['action']) && isset($_GET['id'])) {
    $order_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $action = $_GET['action'];
    $new_status = '';
    
    // Action'a göre yeni durumu belirle
    switch ($action) {
        case 'process':
            $new_status = 'processing';
            $message = "Sipariş işleme alındı.";
            break;
        case 'ship':
            $new_status = 'shipped';
            $message = "Sipariş gönderildi olarak işaretlendi.";
            break;
        case 'deliver':
            $new_status = 'delivered';
            $message = "Sipariş teslim edildi olarak işaretlendi.";
            break;
        case 'cancel':
            $new_status = 'cancelled';
            $message = "Sipariş iptal edildi.";
            break;
        default:
            $message = "Geçersiz işlem.";
            $message_type = "danger";
            break;
    }
    
    if (!empty($new_status) && $order_id) {
        // Sipariş durumunu güncelle
        $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        
        if ($stmt->execute()) {
            $message_type = "success";
            
            // Eğer sipariş iptal edildiyse ve bir eser varsa, eserin durumunu tekrar satışa çıkar
            if ($new_status === 'cancelled') {
                $artwork_stmt = $conn->prepare("UPDATE artworks a 
                                               JOIN order_artwork oa ON a.id = oa.artwork_id 
                                               SET a.status = 'for_sale', a.updated_at = NOW() 
                                               WHERE oa.order_id = ?");
                $artwork_stmt->bind_param("i", $order_id);
                $artwork_stmt->execute();
                $artwork_stmt->close();
            }
        } else {
            $message = "Sipariş durumu güncellenirken bir hata oluştu: " . $conn->error;
            $message_type = "danger";
        }
        
        $stmt->close();
    }
    
    // Sipariş görüntüleme sayfasına yönlendir
    if ($message_type === "success") {
        $_SESSION['success'] = $message;
        header("Location: order_view.php?id=" . $order_id);
        exit;
    }
}

// Sipariş ekleme işlemi
if (isset($_POST['add_order'])) {
    $customer_id = filter_var($_POST['customer_id'], FILTER_VALIDATE_INT);
    $artwork_id = filter_var($_POST['artwork_id'], FILTER_VALIDATE_INT);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    
    if ($customer_id && $artwork_id && $price) {
        // Sipariş numarası oluştur
        $order_number = 'ORD' . date('YmdHis') . rand(100, 999);
        
        // Müşteri bilgisini al
        $customer_stmt = $conn->prepare("SELECT name FROM customers WHERE id = ? AND deleted_at IS NULL");
        $customer_stmt->bind_param("i", $customer_id);
        $customer_stmt->execute();
        $customer_result = $customer_stmt->get_result();
        
        if ($customer_result->num_rows > 0) {
            $customer = $customer_result->fetch_assoc();
            $customer_name = $customer['name'];
            
            // Sipariş ekleme işlemi
            $stmt = $conn->prepare("INSERT INTO orders (order_number, customer_id, customer_name, status, total_amount, created_at, updated_at) 
                                   VALUES (?, ?, ?, 'pending', ?, NOW(), NOW())");
            $stmt->bind_param("sids", $order_number, $customer_id, $customer_name, $price);
            
            if ($stmt->execute()) {
                $order_id = $stmt->insert_id;
                
                // Sipariş-Eser ilişkisi ekleme
                $artwork_stmt = $conn->prepare("INSERT INTO order_artwork (order_id, artwork_id, price, created_at, updated_at) 
                                               VALUES (?, ?, ?, NOW(), NOW())");
                $artwork_stmt->bind_param("iid", $order_id, $artwork_id, $price);
                
                if ($artwork_stmt->execute()) {
                    // Eser durumunu güncelle
                    $update_stmt = $conn->prepare("UPDATE artworks SET status = 'sold', updated_at = NOW() WHERE id = ?");
                    $update_stmt->bind_param("i", $artwork_id);
                    $update_stmt->execute();
                    
                    $message = "Sipariş başarıyla oluşturuldu ve eser satıldı olarak işaretlendi.";
                    $message_type = "success";
                } else {
                    $message = "Sipariş-Eser ilişkisi eklenirken bir hata oluştu: " . $conn->error;
                    $message_type = "danger";
                }
                
                $artwork_stmt->close();
            } else {
                $message = "Sipariş eklenirken bir hata oluştu: " . $conn->error;
                $message_type = "danger";
            }
            
            $stmt->close();
        } else {
            $message = "Müşteri bulunamadı.";
            $message_type = "danger";
        }
        
        $customer_stmt->close();
    } else {
        $message = "Geçersiz müşteri, eser veya fiyat bilgisi.";
        $message_type = "danger";
    }
}

// Siparişleri getir
$orders = [];
$sql = "SELECT o.*, oa.artwork_id, a.title AS artwork_name 
        FROM orders o 
        LEFT JOIN order_artwork oa ON o.id = oa.order_id 
        LEFT JOIN artworks a ON oa.artwork_id = a.id 
        WHERE o.deleted_at IS NULL 
        ORDER BY o.created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Müşteri listesini getir
$customers = [];
$customer_sql = "SELECT id, name, email FROM customers WHERE deleted_at IS NULL ORDER BY name";
$customer_result = $conn->query($customer_sql);

if ($customer_result && $customer_result->num_rows > 0) {
    while ($row = $customer_result->fetch_assoc()) {
        $customers[] = $row;
    }
}

// Satılabilir eserleri getir
$artworks = [];
$artwork_sql = "SELECT id, title, artist_name, price FROM artworks WHERE status = 'for_sale' AND deleted_at IS NULL ORDER BY title";
$artwork_result = $conn->query($artwork_sql);

if ($artwork_result && $artwork_result->num_rows > 0) {
    while ($row = $artwork_result->fetch_assoc()) {
        $artworks[] = $row;
    }
}

// Satılan eserleri getir
$sold_artworks = [];
$sold_artwork_sql = "SELECT a.id, a.title, a.artist_name, a.price, a.verification_code, c.name AS customer_name, o.created_at AS sale_date 
                    FROM artworks a 
                    JOIN order_artwork oa ON a.id = oa.artwork_id 
                    JOIN orders o ON oa.order_id = o.id 
                    JOIN customers c ON o.customer_id = c.id 
                    WHERE a.status = 'sold' AND a.deleted_at IS NULL 
                    ORDER BY o.created_at DESC";
$sold_artwork_result = $conn->query($sold_artwork_sql);

if ($sold_artwork_result && $sold_artwork_result->num_rows > 0) {
    while ($row = $sold_artwork_result->fetch_assoc()) {
        $sold_artworks[] = $row;
    }
}

// Bağlantıyı kapat
$conn->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Artwork Auth | Sipariş Yönetimi</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css">
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Custom CSS -->
    <link href="../../assets/css/style.css" rel="stylesheet">
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


            <?php include 'sidebar_menu.php'; ?>
        </div>
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Sipariş Yönetimi</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
                            <li class="breadcrumb-item active">Sipariş Yönetimi</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <div class="content">
            <div class="container-fluid">
                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <!-- Müşteriye Eser Satışı -->
                <div class="card card-primary mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Yeni Eser Satışı</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="customer_id">Müşteri Seçin</label>
                                        <select class="form-control" id="customer_id" name="customer_id" required>
                                            <option value="">-- Müşteri Seçin --</option>
                                            <?php foreach ($customers as $customer): ?>
                                            <option value="<?php echo $customer['id']; ?>"><?php echo $customer['name']; ?> (<?php echo $customer['email']; ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="artwork_id">Eser Seçin</label>
                                        <select class="form-control" id="artwork_id" name="artwork_id" required onchange="updatePrice()">
                                            <option value="">-- Eser Seçin --</option>
                                            <?php foreach ($artworks as $artwork): ?>
                                            <option value="<?php echo $artwork['id']; ?>" data-price="<?php echo $artwork['price']; ?>">
                                                <?php echo $artwork['title']; ?> (<?php echo $artwork['artist_name']; ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="price">Fiyat (₺)</label>
                                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <button type="submit" name="add_order" class="btn btn-primary">Eser Satışını Kaydet</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Satılan Eserler -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Satılan Eserler</h3>
                    </div>
                    <div class="card-body">
                        <table id="soldArtworksTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Eser Adı</th>
                                    <th>Sanatçı</th>
                                    <th>Müşteri</th>
                                    <th>Fiyat</th>
                                    <th>Doğrulama Kodu</th>
                                    <th>Satış Tarihi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sold_artworks as $index => $artwork): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $artwork['title']; ?></td>
                                    <td><?php echo $artwork['artist_name']; ?></td>
                                    <td><?php echo $artwork['customer_name']; ?></td>
                                    <td><?php echo number_format($artwork['price'], 2, ',', '.') . ' ₺'; ?></td>
                                    <td><?php echo $artwork['verification_code']; ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($artwork['sale_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Sipariş Listesi -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Sipariş Listesi</h3>
                    </div>
                    <div class="card-body">
                        <table id="ordersTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Sipariş ID</th>
                                    <th>Sipariş No</th>
                                    <th>Müşteri</th>
                                    <th>Eser</th>
                                    <th>Tutar</th>
                                    <th>Durum</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td><?php echo $order['order_number']; ?></td>
                                    <td><?php echo $order['customer_name']; ?></td>
                                    <td><?php echo $order['artwork_name']; ?></td>
                                    <td><?php echo number_format($order['total_amount'], 2, ',', '.') . ' ₺'; ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = 'secondary';
                                        $statusText = 'Beklemede';
                                        
                                        switch ($order['status']) {
                                            case 'pending':
                                                $statusClass = 'warning';
                                                $statusText = 'Beklemede';
                                                break;
                                            case 'processing':
                                                $statusClass = 'info';
                                                $statusText = 'İşleniyor';
                                                break;
                                            case 'completed':
                                                $statusClass = 'success';
                                                $statusText = 'Tamamlandı';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'danger';
                                                $statusText = 'İptal Edildi';
                                                break;
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="order_view.php?id=<?php echo $order['id']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="order_edit.php?id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
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
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Footer -->
    <footer class="main-footer">
        <strong>Copyright &copy; 2023 <a href="#">Artwork Auth</a>.</strong>
        Tüm hakları saklıdır.
        <div class="float-right d-none d-sm-inline-block">
            <b>Versiyon</b> 1.0.0
        </div>
    </footer>
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
$(document).ready(function() {
    $('#ordersTable').DataTable({
        "responsive": true,
        "autoWidth": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.12.1/i18n/tr.json"
        }
    });
    
    $('#soldArtworksTable').DataTable({
        "responsive": true,
        "autoWidth": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.12.1/i18n/tr.json"
        }
    });
});

function updatePrice() {
    var artworkSelect = document.getElementById('artwork_id');
    var priceInput = document.getElementById('price');
    
    if (artworkSelect.selectedIndex > 0) {
        var selectedOption = artworkSelect.options[artworkSelect.selectedIndex];
        var price = selectedOption.getAttribute('data-price');
        priceInput.value = price;
    } else {
        priceInput.value = '';
    }
}
</script>
</body>
</html> 