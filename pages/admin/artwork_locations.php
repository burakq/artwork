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

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $location_key = sanitize($_POST['location_key']);
            $location_name = sanitize($_POST['location_name']);
            
            // Konum anahtarı doğrulama
            if (!preg_match('/^[a-z0-9_]+$/', $location_key)) {
                $error_message = "Konum anahtarı sadece küçük harfler, rakamlar ve alt çizgi içerebilir.";
            } else {
                // Konum anahtarının benzersiz olduğunu kontrol et
                $stmt = $conn->prepare("SELECT id FROM artwork_locations WHERE location_key = ?");
                $stmt->bind_param("s", $location_key);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error_message = "Bu konum anahtarı zaten kullanılıyor.";
                } else {
                    // Yeni konum ekle
                    $stmt = $conn->prepare("INSERT INTO artwork_locations (location_key, location_name) VALUES (?, ?)");
                    $stmt->bind_param("ss", $location_key, $location_name);
                    
                    if ($stmt->execute()) {
                        $success_message = "Konum başarıyla eklendi.";
                    } else {
                        $error_message = "Konum eklenirken bir hata oluştu.";
                    }
                }
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = (int)$_POST['id'];
            $location_name = sanitize($_POST['location_name']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Konum adı doğrulama
            if (strlen($location_name) < 2) {
                $error_message = "Konum adı en az 2 karakter olmalıdır.";
            } else {
                // Konumu güncelle
                $stmt = $conn->prepare("UPDATE artwork_locations SET location_name = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("sii", $location_name, $is_active, $id);
                
                if ($stmt->execute()) {
                    $success_message = "Konum başarıyla güncellendi.";
                } else {
                    $error_message = "Konum güncellenirken bir hata oluştu.";
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            
            // Konumun kullanımda olup olmadığını kontrol et
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM artworks WHERE location = (SELECT location_key FROM artwork_locations WHERE id = ?)");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                $error_message = "Bu konum kullanımda olduğu için silinemez. Önce bu konumu kullanan eserleri güncelleyin.";
            } else {
                // Konumu sil
                $stmt = $conn->prepare("DELETE FROM artwork_locations WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $success_message = "Konum başarıyla silindi.";
                } else {
                    $error_message = "Konum silinirken bir hata oluştu.";
                }
            }
        }
    }
}

// Konumları getir
$locations = [];
$query = "SELECT * FROM artwork_locations ORDER BY location_name";
$result = $conn->query($query);

// Sayfa başlığı ve breadcrumb
$page_title = 'Eser Konumları';
$breadcrumb = '<li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
               <li class="breadcrumb-item active">Eser Konumları</li>';

// Header'ı dahil et
include 'templates/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Eser Konumları Listesi</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addLocationModal">
                <i class="fas fa-plus"></i> Yeni Konum Ekle
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Konum Anahtarı</th>
                    <th>Konum Adı</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['location_key']); ?></td>
                        <td><?php echo htmlspecialchars($row['location_name']); ?></td>
                        <td>
                            <?php if ($row['is_active']): ?>
                                <span class="badge badge-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Pasif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editLocationModal<?php echo $row['id']; ?>">
                                <i class="fas fa-edit"></i> Düzenle
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                <i class="fas fa-trash"></i> Sil
                            </button>
                        </td>
                    </tr>
                    
                    <!-- Edit Modal -->
                    <div class="modal fade" id="editLocationModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <form method="post" action="">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Konum Düzenle</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label>Konum Anahtarı</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['location_key']); ?>" readonly>
                                            <small class="text-muted">Konum anahtarı değiştirilemez.</small>
                                        </div>
                                        <div class="form-group">
                                            <label>Konum Adı</label>
                                            <input type="text" class="form-control" name="location_name" value="<?php echo htmlspecialchars($row['location_name']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="isActive<?php echo $row['id']; ?>" name="is_active" <?php echo $row['is_active'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="isActive<?php echo $row['id']; ?>">Aktif</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                                        <button type="submit" class="btn btn-primary">Kaydet</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Location Modal -->
<div class="modal fade" id="addLocationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="" id="addLocationForm">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Konum Ekle</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="location_key">Konum Anahtarı</label>
                        <input type="text" class="form-control" id="location_key" name="location_key" required 
                               pattern="[a-z0-9_]+" title="Sadece küçük harfler, rakamlar ve alt çizgi kullanabilirsiniz">
                        <small class="text-muted">Konum anahtarı benzersiz olmalıdır ve sadece küçük harfler, rakamlar ve alt çizgi içerebilir.</small>
                    </div>
                    <div class="form-group">
                        <label for="location_name">Konum Adı</label>
                        <input type="text" class="form-control" id="location_name" name="location_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="post" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<?php
// JavaScript kodları
$additional_js = <<<EOT
<script>
$(document).ready(function() {
    // Form doğrulama
    $('#addLocationForm').on('submit', function(e) {
        const locationKey = $('#location_key').val();
        const locationName = $('#location_name').val();
        
        if (!/^[a-z0-9_]+$/.test(locationKey)) {
            alert('Konum anahtarı sadece küçük harfler, rakamlar ve alt çizgi içerebilir.');
            e.preventDefault();
            return false;
        }
        
        if (locationName.length < 2) {
            alert('Konum adı en az 2 karakter olmalıdır.');
            e.preventDefault();
            return false;
        }
    });
});

function confirmDelete(id) {
    if (confirm('Bu konumu silmek istediğinizden emin misiniz?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>
EOT;

// Footer'ı dahil et
include 'templates/footer.php';
?> 