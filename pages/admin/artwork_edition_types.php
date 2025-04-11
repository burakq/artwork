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
            $edition_key = sanitize($_POST['edition_key']);
            $edition_name = sanitize($_POST['edition_name']);
            
            // Baskı türü anahtarı doğrulama
            if (!preg_match('/^[a-z0-9_]+$/', $edition_key)) {
                $error_message = "Baskı türü anahtarı sadece küçük harfler, rakamlar ve alt çizgi içerebilir.";
            } else {
                // Baskı türü anahtarının benzersiz olduğunu kontrol et
                $stmt = $conn->prepare("SELECT id FROM artwork_edition_types WHERE edition_key = ?");
                $stmt->bind_param("s", $edition_key);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error_message = "Bu baskı türü anahtarı zaten kullanılıyor.";
                } else {
                    // Yeni baskı türü ekle
                    $stmt = $conn->prepare("INSERT INTO artwork_edition_types (edition_key, edition_name) VALUES (?, ?)");
                    $stmt->bind_param("ss", $edition_key, $edition_name);
                    
                    if ($stmt->execute()) {
                        $success_message = "Baskı türü başarıyla eklendi.";
                    } else {
                        $error_message = "Baskı türü eklenirken bir hata oluştu.";
                    }
                }
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = (int)$_POST['id'];
            $edition_name = sanitize($_POST['edition_name']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Baskı türü adı doğrulama
            if (strlen($edition_name) < 2) {
                $error_message = "Baskı türü adı en az 2 karakter olmalıdır.";
            } else {
                // Baskı türünü güncelle
                $stmt = $conn->prepare("UPDATE artwork_edition_types SET edition_name = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("sii", $edition_name, $is_active, $id);
                
                if ($stmt->execute()) {
                    $success_message = "Baskı türü başarıyla güncellendi.";
                } else {
                    $error_message = "Baskı türü güncellenirken bir hata oluştu.";
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            
            // Baskı türünün kullanımda olup olmadığını kontrol et
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM artworks WHERE edition_type = (SELECT edition_key FROM artwork_edition_types WHERE id = ?)");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                $error_message = "Bu baskı türü kullanımda olduğu için silinemez. Önce bu baskı türünü kullanan eserleri güncelleyin.";
            } else {
                // Baskı türünü sil
                $stmt = $conn->prepare("DELETE FROM artwork_edition_types WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $success_message = "Baskı türü başarıyla silindi.";
                } else {
                    $error_message = "Baskı türü silinirken bir hata oluştu.";
                }
            }
        }
    }
}

// Baskı türlerini getir
$edition_types = [];
$query = "SELECT * FROM artwork_edition_types ORDER BY edition_name";
$result = $conn->query($query);

// Sayfa başlığı ve breadcrumb
$page_title = 'Baskı Türleri';
$breadcrumb = '<li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
               <li class="breadcrumb-item active">Baskı Türleri</li>';

// Header'ı dahil et
include 'templates/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Baskı Türleri Listesi</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addEditionTypeModal">
                <i class="fas fa-plus"></i> Yeni Baskı Türü Ekle
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
                    <th>Baskı Türü Anahtarı</th>
                    <th>Baskı Türü Adı</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['edition_key']); ?></td>
                        <td><?php echo htmlspecialchars($row['edition_name']); ?></td>
                        <td>
                            <?php if ($row['is_active']): ?>
                                <span class="badge badge-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Pasif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editEditionTypeModal<?php echo $row['id']; ?>">
                                <i class="fas fa-edit"></i> Düzenle
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                <i class="fas fa-trash"></i> Sil
                            </button>
                        </td>
                    </tr>
                    
                    <!-- Edit Modal -->
                    <div class="modal fade" id="editEditionTypeModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <form method="post" action="">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Baskı Türü Düzenle</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label>Baskı Türü Anahtarı</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['edition_key']); ?>" readonly>
                                            <small class="text-muted">Baskı türü anahtarı değiştirilemez.</small>
                                        </div>
                                        <div class="form-group">
                                            <label>Baskı Türü Adı</label>
                                            <input type="text" class="form-control" name="edition_name" value="<?php echo htmlspecialchars($row['edition_name']); ?>" required>
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

<!-- Add Edition Type Modal -->
<div class="modal fade" id="addEditionTypeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="" id="addEditionTypeForm">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Baskı Türü Ekle</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edition_key">Baskı Türü Anahtarı</label>
                        <input type="text" class="form-control" id="edition_key" name="edition_key" required 
                               pattern="[a-z0-9_]+" title="Sadece küçük harfler, rakamlar ve alt çizgi kullanabilirsiniz">
                        <small class="text-muted">Baskı türü anahtarı benzersiz olmalıdır ve sadece küçük harfler, rakamlar ve alt çizgi içerebilir.</small>
                    </div>
                    <div class="form-group">
                        <label for="edition_name">Baskı Türü Adı</label>
                        <input type="text" class="form-control" id="edition_name" name="edition_name" required>
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
    $('#addEditionTypeForm').on('submit', function(e) {
        const editionKey = $('#edition_key').val();
        const editionName = $('#edition_name').val();
        
        if (!/^[a-z0-9_]+$/.test(editionKey)) {
            alert('Baskı türü anahtarı sadece küçük harfler, rakamlar ve alt çizgi içerebilir.');
            e.preventDefault();
            return false;
        }
        
        if (editionName.length < 2) {
            alert('Baskı türü adı en az 2 karakter olmalıdır.');
            e.preventDefault();
            return false;
        }
    });
});

function confirmDelete(id) {
    if (confirm('Bu baskı türünü silmek istediğinizden emin misiniz?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>
EOT;

// Footer'ı dahil et
include 'templates/footer.php';
?> 