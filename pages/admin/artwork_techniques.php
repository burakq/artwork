<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Giriş yapılmış mı kontrol et
if (!isLoggedIn()) {
    redirect('../../login.php');
    exit;
}

// Admin yetkisi kontrolü
if (!isAdmin()) {
    redirect('../../index.php');
    exit;
}

// Veritabanı bağlantısı
$conn = connectDB();

$success_message = '';
$error_message = '';

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $technique_key = sanitize($_POST['technique_key']);
            $technique_name = sanitize($_POST['technique_name']);
            
            // Teknik anahtarı doğrulama
            if (!preg_match('/^[a-z0-9_]+$/', $technique_key)) {
                $error_message = "Teknik anahtarı sadece küçük harfler, rakamlar ve alt çizgi içerebilir.";
            } else {
                // Teknik anahtarının benzersiz olduğunu kontrol et
                $stmt = $conn->prepare("SELECT id FROM artwork_techniques WHERE technique_key = ?");
                $stmt->bind_param("s", $technique_key);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error_message = "Bu teknik anahtarı zaten kullanılıyor.";
                } else {
                    // Yeni teknik ekle
                    $stmt = $conn->prepare("INSERT INTO artwork_techniques (technique_key, technique_name) VALUES (?, ?)");
                    $stmt->bind_param("ss", $technique_key, $technique_name);
                    
                    if ($stmt->execute()) {
                        $success_message = "Teknik başarıyla eklendi.";
                    } else {
                        $error_message = "Teknik eklenirken bir hata oluştu.";
                    }
                }
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = (int)$_POST['id'];
            $technique_name = sanitize($_POST['technique_name']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Teknik adı doğrulama
            if (strlen($technique_name) < 2) {
                $error_message = "Teknik adı en az 2 karakter olmalıdır.";
            } else {
                // Tekniği güncelle
                $stmt = $conn->prepare("UPDATE artwork_techniques SET technique_name = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("sii", $technique_name, $is_active, $id);
                
                if ($stmt->execute()) {
                    $success_message = "Teknik başarıyla güncellendi.";
                } else {
                    $error_message = "Teknik güncellenirken bir hata oluştu.";
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            
            // Tekniğin kullanımda olup olmadığını kontrol et
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM artworks WHERE technique = (SELECT technique_key FROM artwork_techniques WHERE id = ?)");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                $error_message = "Bu teknik kullanımda olduğu için silinemez. Önce bu tekniği kullanan eserleri güncelleyin.";
            } else {
                // Tekniği sil
                $stmt = $conn->prepare("DELETE FROM artwork_techniques WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $success_message = "Teknik başarıyla silindi.";
                } else {
                    $error_message = "Teknik silinirken bir hata oluştu.";
                }
            }
        }
    }
}

// Teknikleri getir
$techniques = [];
$query = "SELECT * FROM artwork_techniques ORDER BY technique_name";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $techniques[] = $row;
    }
}

// Bağlantıyı kapat
$conn->close();

// Sayfa başlığı ve breadcrumb
$page_title = "Eser Teknikleri";
$breadcrumb = '<li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
               <li class="breadcrumb-item"><a href="artworks.php">Eserler</a></li>
               <li class="breadcrumb-item active">Eser Teknikleri</li>';

// Header'ı dahil et
include 'templates/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Eser Teknikleri Listesi</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addTechniqueModal">
                <i class="fas fa-plus"></i> Yeni Teknik Ekle
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th style="width: 10px">#</th>
                    <th>Teknik Anahtarı</th>
                    <th>Teknik Adı</th>
                    <th>Durum</th>
                    <th style="width: 150px">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($techniques as $technique): ?>
                <tr>
                    <td><?php echo $technique['id']; ?></td>
                    <td><?php echo htmlspecialchars($technique['technique_key']); ?></td>
                    <td><?php echo htmlspecialchars($technique['technique_name']); ?></td>
                    <td>
                        <?php if ($technique['is_active']): ?>
                            <span class="badge badge-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Pasif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editTechniqueModal<?php echo $technique['id']; ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $technique['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editTechniqueModal<?php echo $technique['id']; ?>" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form method="post">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?php echo $technique['id']; ?>">
                                
                                <div class="modal-header">
                                    <h5 class="modal-title">Teknik Düzenle</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Teknik Anahtarı</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($technique['technique_key']); ?>" readonly>
                                        <small class="text-muted">Teknik anahtarı değiştirilemez.</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Teknik Adı</label>
                                        <input type="text" class="form-control" name="technique_name" value="<?php echo htmlspecialchars($technique['technique_name']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="isActive<?php echo $technique['id']; ?>" name="is_active" <?php echo $technique['is_active'] ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="isActive<?php echo $technique['id']; ?>">Aktif</label>
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
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addTechniqueModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Teknik Ekle</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label>Teknik Anahtarı</label>
                        <input type="text" class="form-control" name="technique_key" required pattern="[a-z0-9_]+">
                        <small class="text-muted">Sadece küçük harfler, rakamlar ve alt çizgi kullanabilirsiniz.</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Teknik Adı</label>
                        <input type="text" class="form-control" name="technique_name" required>
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

<?php
// Sayfa JavaScript
$additional_js = '
<script>
function confirmDelete(id) {
    if (confirm("Bu tekniği silmek istediğinize emin misiniz?")) {
        var form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>';

// Footer'ı dahil et
include 'templates/footer.php';
?> 
 
 
 