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

// ID kontrolü
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('artworks.php');
}

$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$id) {
    redirect('artworks.php');
}

// Veritabanı bağlantısı
$conn = connectDB();

// Eser bilgilerini getir
$artwork = null;
$stmt = $conn->prepare("SELECT * FROM artworks WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Eser bulunamadı
    redirect('artworks.php');
}

$artwork = $result->fetch_assoc();
$stmt->close();

// Doğrulama istatistiklerini getir
$verification_count = 0;
$last_verification = null;

$stmt = $conn->prepare("SELECT COUNT(*) as count, MAX(created_at) as last_date FROM verification_logs WHERE artwork_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $verification_count = $row['count'];
    $last_verification = $row['last_date'];
}
$stmt->close();

// Teknikleri getir
$techniques = [];
$query = "SELECT * FROM artwork_techniques WHERE is_active = 1 ORDER BY technique_name";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $techniques[$row['technique_key']] = $row['technique_name'];
    }
}

// Bağlantıyı kapat
$conn->close();

// Verify.php sayfasına yönlendir
redirect('../../verify.php?code=' . $artwork['verification_code']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Artwork Auth | Eser Detayı</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Custom CSS -->
    <link href="../../assets/css/style.css" rel="stylesheet">
    <style>
        .artwork-image {
            max-height: 400px;
            object-fit: contain;
        }
        .verification-qr {
            text-align: center;
            margin-top: 20px;
        }
        .verification-qr img {
            max-width: 200px;
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
                    <a href="#" class="d-block"><?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin'; ?></a>
                </div>
            </div>

            <?php include 'sidebar_menu.php'; ?>
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Eser Detayı</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
                            <li class="breadcrumb-item"><a href="artworks.php">Eserler</a></li>
                            <li class="breadcrumb-item active">Detay</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo $artwork['title']; ?></h3>
                                <div class="card-tools">
                                    <a href="artwork_edit.php?id=<?php echo $artwork['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Düzenle
                                    </a>
                                    <a href="artworks.php" class="btn btn-secondary btn-sm ml-1">
                                        <i class="fas fa-arrow-left"></i> Listeye Dön
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <?php if (!empty($artwork['image_path'])): ?>
                                            <img src="../../<?php echo $artwork['image_path']; ?>" alt="<?php echo $artwork['title']; ?>" class="img-fluid artwork-image">
                                        <?php else: ?>
                                            <div class="text-center p-5 bg-light">
                                                <i class="fas fa-image fa-5x text-muted"></i>
                                                <p class="mt-3">Görsel mevcut değil</p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="verification-qr">
                                            <h5>Doğrulama QR Kodu</h5>
                                            <img src="https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . '/verify.php?code=' . $artwork['verification_code']); ?>&choe=UTF-8" alt="QR Kodu">
                                            <p class="mt-2 small">Bu QR kodu taratarak eser bilgilerini doğrulayabilirsiniz.</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-bordered">
                                            <tr>
                                                <th style="width: 30%">Eser Adı</th>
                                                <td><?php echo $artwork['title']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Sanatçı</th>
                                                <td><?php echo $artwork['artist_name']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Doğrulama Kodu</th>
                                                <td><span class="badge bg-info"><?php echo $artwork['verification_code']; ?></span></td>
                                            </tr>
                                            <tr>
                                                <th>Durum</th>
                                                <td>
                                                    <?php
                                                    $statusClass = 'secondary';
                                                    switch ($artwork['status']) {
                                                        case 'original':
                                                            $statusClass = 'primary';
                                                            $statusText = 'Orijinal';
                                                            break;
                                                        case 'for_sale':
                                                            $statusClass = 'success';
                                                            $statusText = 'Satışta';
                                                            break;
                                                        case 'sold':
                                                            $statusClass = 'warning';
                                                            $statusText = 'Satıldı';
                                                            break;
                                                        case 'fake':
                                                            $statusClass = 'danger';
                                                            $statusText = 'Sahte';
                                                            break;
                                                        case 'archived':
                                                            $statusClass = 'info';
                                                            $statusText = 'Arşivlendi';
                                                            break;
                                                        default:
                                                            $statusText = $artwork['status'];
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Fiyat</th>
                                                <td><?php echo number_format($artwork['price'], 2, ',', '.') . ' ₺'; ?></td>
                                            </tr>
                                            <?php if (!empty($artwork['year'])): ?>
                                            <tr>
                                                <th>Yapım Yılı</th>
                                                <td><?php echo $artwork['year']; ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <!-- Teknik bilgisi -->
                                            <tr>
                                                <th>Teknik</th>
                                                <td><?php echo isset($techniques[$artwork['technique']]) ? htmlspecialchars($techniques[$artwork['technique']]) : htmlspecialchars($artwork['technique']); ?></td>
                                            </tr>
                                            <?php if (!empty($artwork['location'])): ?>
                                            <tr>
                                                <th>Konum</th>
                                                <td><?php echo $artwork['location']; ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php
                                            $dimensions = [];
                                            if (!empty($artwork['width'])) $dimensions[] = "Genişlik: {$artwork['width']} {$artwork['dimension_unit']}";
                                            if (!empty($artwork['height'])) $dimensions[] = "Yükseklik: {$artwork['height']} {$artwork['dimension_unit']}";
                                            if (!empty($artwork['depth'])) $dimensions[] = "Derinlik: {$artwork['depth']} {$artwork['dimension_unit']}";
                                            
                                            if (!empty($dimensions)):
                                            ?>
                                            <tr>
                                                <th>Boyutlar</th>
                                                <td><?php echo implode(', ', $dimensions); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <th>Eklenme Tarihi</th>
                                                <td><?php echo date('d.m.Y H:i', strtotime($artwork['created_at'])); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Son Güncelleme</th>
                                                <td><?php echo date('d.m.Y H:i', strtotime($artwork['updated_at'])); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Doğrulama Sayısı</th>
                                                <td><?php echo $verification_count; ?> kez</td>
                                            </tr>
                                            <?php if ($last_verification): ?>
                                            <tr>
                                                <th>Son Doğrulama</th>
                                                <td><?php echo date('d.m.Y H:i', strtotime($last_verification)); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                        </table>
                                        
                                        <?php if (!empty($artwork['description'])): ?>
                                        <div class="mt-4">
                                            <h5>Açıklama</h5>
                                            <p><?php echo nl2br($artwork['description']); ?></p>
                                        </div>
                                        <?php endif; ?>

                                        <div class="mt-4">
                                            <h5>Doğrulama Bağlantısı</h5>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control" value="https://<?php echo $_SERVER['HTTP_HOST']; ?>/verify.php?code=<?php echo $artwork['verification_code']; ?>" id="verification-link" readonly>
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="copyVerificationLink()">Kopyala</button>
                                                </div>
                                            </div>
                                            <p class="small text-muted">Bu bağlantıyı müşterilere göndererek eserin doğrulanmasını sağlayabilirsiniz.</p>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <a href="artwork_edit.php?id=<?php echo $artwork['id']; ?>" class="btn btn-primary">
                                                <i class="fas fa-edit"></i> Düzenle
                                            </a>
                                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $artwork['id']; ?>)" class="btn btn-danger">
                                                <i class="fas fa-trash"></i> Sil
                                            </a>
                                            <a href="artworks.php" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left"></i> Listeye Dön
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <footer class="main-footer">
        <strong>Copyright &copy; 2023 <a href="#">Artwork Auth</a>.</strong>
        Tüm hakları saklıdır.
        <div class="float-right d-none d-sm-inline-block">
            <b>Versiyon</b> 1.0.0
        </div>
    </footer>
</div>
<!-- ./wrapper -->

<!-- Silme Onay Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Silme Onayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Bu eseri silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Sil</a>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE 3 App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
function confirmDelete(id) {
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    document.getElementById('confirmDeleteBtn').href = 'artworks.php?delete=' + id;
    modal.show();
}

function copyVerificationLink() {
    var copyText = document.getElementById("verification-link");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    
    alert("Doğrulama bağlantısı kopyalandı!");
}
</script>
</body>
</html> 
 
 
 
 