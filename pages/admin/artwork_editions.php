// Hata raporlamasını aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Oturum başlat
session_start();

// Gerekli dosyaları dahil et
require_once '../../includes/functions.php';
require_once '../../config/db.php';

// Oturum ve yetki kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

try {
    $conn = connectDB();
    
    // Başarı ve hata mesajları
    $success_message = '';
    $error_message = '';
    
    // Form gönderildiğinde
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add') {
                $edition_name = sanitize($_POST['edition_name']);
                $edition_description = sanitize($_POST['edition_description']);
                
                // Baskı türünü ekle
                $stmt = $conn->prepare("INSERT INTO artwork_editions (edition_name, edition_description) VALUES (?, ?)");
                $stmt->bind_param("ss", $edition_name, $edition_description);
                
                if ($stmt->execute()) {
                    $success_message = "Baskı türü başarıyla eklendi.";
                } else {
                    $error_message = "Baskı türü eklenirken bir hata oluştu: " . $stmt->error;
                }
            } elseif ($_POST['action'] === 'edit') {
                $id = (int)$_POST['id'];
                $edition_name = sanitize($_POST['edition_name']);
                $edition_description = sanitize($_POST['edition_description']);
                
                // Baskı türünü güncelle
                $stmt = $conn->prepare("UPDATE artwork_editions SET edition_name = ?, edition_description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $edition_name, $edition_description, $id);
                
                if ($stmt->execute()) {
                    $success_message = "Baskı türü başarıyla güncellendi.";
                } else {
                    $error_message = "Baskı türü güncellenirken bir hata oluştu: " . $stmt->error;
                }
            } elseif ($_POST['action'] === 'delete') {
                $id = (int)$_POST['id'];
                
                // Baskı türünün kullanımda olup olmadığını kontrol et
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM artworks WHERE edition_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                
                if ($row['count'] > 0) {
                    $error_message = "Bu baskı türü kullanımda olduğu için silinemez.";
                } else {
                    // Baskı türünü sil
                    $stmt = $conn->prepare("DELETE FROM artwork_editions WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Baskı türü başarıyla silindi.";
                    } else {
                        $error_message = "Baskı türü silinirken bir hata oluştu: " . $stmt->error;
                    }
                }
            }
        }
    }
    
    // Baskı türlerini getir
    $editions = [];
    $result = $conn->query("SELECT * FROM artwork_editions ORDER BY edition_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $editions[] = $row;
        }
    } else {
        $error_message = "Baskı türleri getirilirken bir hata oluştu: " . $conn->error;
    }
} catch (Exception $e) {
    $error_message = "Bir hata oluştu: " . $e->getMessage();
}

// Sayfa başlığı
$page_title = "Baskı Türleri";

// Header'ı dahil et
require_once 'templates/header.php';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?php echo $page_title; ?></h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Anasayfa</a></li>
                        <li class="breadcrumb-item active"><?php echo $page_title; ?></li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Baskı Türleri Listesi</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addEditionModal">
                            <i class="fas fa-plus"></i> Yeni Baskı Türü Ekle
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 10px">#</th>
                                <th>Baskı Türü</th>
                                <th>Açıklama</th>
                                <th style="width: 150px">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($editions as $edition): ?>
                            <tr>
                                <td><?php echo $edition['id']; ?></td>
                                <td><?php echo htmlspecialchars($edition['edition_name']); ?></td>
                                <td><?php echo htmlspecialchars($edition['edition_description']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editEditionModal<?php echo $edition['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteEditionModal<?php echo $edition['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Add Edition Modal -->
<div class="modal fade" id="addEditionModal" tabindex="-1" role="dialog" aria-labelledby="addEditionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditionModalLabel">Yeni Baskı Türü Ekle</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="edition_name">Baskı Türü Adı</label>
                        <input type="text" class="form-control" id="edition_name" name="edition_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edition_description">Açıklama</label>
                        <textarea class="form-control" id="edition_description" name="edition_description" rows="3"></textarea>
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

<?php foreach ($editions as $edition): ?>
<!-- Edit Edition Modal -->
<div class="modal fade" id="editEditionModal<?php echo $edition['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editEditionModalLabel<?php echo $edition['id']; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEditionModalLabel<?php echo $edition['id']; ?>">Baskı Türü Düzenle</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo $edition['id']; ?>">
                    <div class="form-group">
                        <label for="edition_name<?php echo $edition['id']; ?>">Baskı Türü Adı</label>
                        <input type="text" class="form-control" id="edition_name<?php echo $edition['id']; ?>" name="edition_name" value="<?php echo htmlspecialchars($edition['edition_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edition_description<?php echo $edition['id']; ?>">Açıklama</label>
                        <textarea class="form-control" id="edition_description<?php echo $edition['id']; ?>" name="edition_description" rows="3"><?php echo htmlspecialchars($edition['edition_description']); ?></textarea>
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

<!-- Delete Edition Modal -->
<div class="modal fade" id="deleteEditionModal<?php echo $edition['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteEditionModalLabel<?php echo $edition['id']; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteEditionModalLabel<?php echo $edition['id']; ?>">Baskı Türü Sil</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Bu baskı türünü silmek istediğinizden emin misiniz?</p>
                <p><strong><?php echo htmlspecialchars($edition['edition_name']); ?></strong></p>
            </div>
            <div class="modal-footer">
                <form action="" method="post">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $edition['id']; ?>">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-danger">Sil</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php
// Footer'ı dahil et
require_once 'templates/footer.php';
?> 