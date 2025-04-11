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

// İşlem mesajı
$message = '';
$message_type = '';

// Eser silme işlemi
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    
    if ($id) {
        $stmt = $conn->prepare("UPDATE artworks SET deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Eser başarıyla silindi.";
            $message_type = "success";
        } else {
            $message = "Eser silinirken bir hata oluştu: " . $conn->error;
            $message_type = "danger";
        }
        
        $stmt->close();
    }
}

// Sanat eserlerini getir
$artworks = [];
$sql = "SELECT * FROM artworks WHERE deleted_at IS NULL ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $artworks[] = $row;
    }
}

// Bağlantıyı kapat
$conn->close();

// Sayfa başlığı ve breadcrumb
$page_title = "Sanat Eserleri";
$breadcrumb = '<li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
               <li class="breadcrumb-item active">Sanat Eserleri</li>';

// DataTables için ek CSS
$additional_css = '
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
<style>
.artwork-thumbnail {
    max-width: 100px;
    max-height: 100px;
    object-fit: cover;
}
</style>';

// Header'ı dahil et
include 'templates/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Sanat Eserleri Listesi</h3>
        <div class="card-tools">
            <a href="artwork_add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Yeni Eser Ekle
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <table id="artworksTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Resim</th>
                    <th>Başlık</th>
                    <th>Sanatçı</th>
                    <th>Durum</th>
                    <th>Doğrulama Kodu</th>
                    <th>Fiyat</th>
                    <th>Oluşturulma Tarihi</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($artworks as $artwork): ?>
                <tr>
                    <td><?php echo $artwork['id']; ?></td>
                    <td>
                        <?php if (!empty($artwork['image_path'])): ?>
                            <img src="../../<?php echo $artwork['image_path']; ?>" alt="<?php echo htmlspecialchars($artwork['title']); ?>" class="artwork-thumbnail">
                        <?php else: ?>
                            <span class="badge badge-secondary">Resim Yok</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($artwork['title']); ?></td>
                    <td><?php echo htmlspecialchars($artwork['artist_name']); ?></td>
                    <td>
                        <?php
                        $statusClass = 'secondary';
                        $statusText = 'Bilinmiyor';
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
                                $statusClass = 'info';
                                $statusText = 'Satıldı';
                                break;
                            case 'reserved':
                                $statusClass = 'warning';
                                $statusText = 'Rezerve';
                                break;
                            case 'not_for_sale':
                                $statusClass = 'danger';
                                $statusText = 'Satılık Değil';
                                break;
                        }
                        ?>
                        <span class="badge badge-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </td>
                    <td><?php echo $artwork['verification_code']; ?></td>
                    <td><?php echo number_format($artwork['price'], 2, ',', '.') . ' ₺'; ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($artwork['created_at'])); ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="artwork_view.php?id=<?php echo $artwork['id']; ?>" class="btn btn-sm btn-info" title="Görüntüle">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="artwork_edit.php?id=<?php echo $artwork['id']; ?>" class="btn btn-sm btn-warning" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $artwork['id']; ?>)" class="btn btn-sm btn-danger" title="Sil">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// DataTables için ek JavaScript
$additional_js = '
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    $("#artworksTable").DataTable({
        "responsive": true,
        "autoWidth": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json"
        }
    });
});

function confirmDelete(id) {
    if (confirm("Bu eseri silmek istediğinize emin misiniz?")) {
        window.location.href = "?delete=" + id;
    }
}
</script>';

// Footer'ı dahil et
include 'templates/footer.php';
?> 