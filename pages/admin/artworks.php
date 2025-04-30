<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sanat Eserleri</title>
    <!-- Önce jQuery yüklenmeli -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
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
        // Yumuşak silme (soft delete)
        $stmt = $conn->prepare("UPDATE artworks SET deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Eser çöp kutusuna taşındı.";
            $message_type = "success";
        } else {
            $message = "Eser çöp kutusuna taşınırken bir hata oluştu: " . $conn->error;
            $message_type = "danger";
        }
        
        $stmt->close();
    }
}

// Teknikleri getir
$techniques = [];
$techniques_query = "SELECT technique_key, technique_name FROM artwork_techniques";
$techniques_result = $conn->query($techniques_query);

if ($techniques_result && $techniques_result->num_rows > 0) {
    while ($row = $techniques_result->fetch_assoc()) {
        $techniques[$row['technique_key']] = $row['technique_name'];
    }
}

// Durumları getir
$statuses = [];
$statuses_query = "SELECT * FROM artwork_statuses";
$statuses_result = $conn->query($statuses_query);

if ($statuses_result && $statuses_result->num_rows > 0) {
    while ($row = $statuses_result->fetch_assoc()) {
        $statuses[$row['status_key']] = $row['status_name'];
    }
}

// Sanat eserlerini getir - ID'ye göre azalan sırada (en yüksek ID ilk başta)
$artworks = [];
$sql = "SELECT DISTINCT a.*, 
        CASE WHEN n.id IS NOT NULL THEN 1 ELSE 0 END as nfc_written
        FROM artworks a
        LEFT JOIN nfc_written_logs n ON a.id = n.artwork_id
        WHERE a.deleted_at IS NULL 
        ORDER BY a.id DESC";
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
.lazy-load {
    opacity: 0;
    transition: opacity 0.3s;
}
.lazy-load.loaded {
    opacity: 1;
}
</style>';

// DataTables için ek JavaScript
$additional_js = '
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
<script>
// Türkçe dil ayarları - CORS hatasını önlemek için yerel olarak tanımlandı
var dataTables_turkish = {
    "emptyTable": "Tabloda herhangi bir veri mevcut değil",
    "info": "_TOTAL_ kayıttan _START_ - _END_ arasındaki kayıtlar gösteriliyor",
    "infoEmpty": "Kayıt yok",
    "infoFiltered": "(_MAX_ kayıt içerisinden bulunan)",
    "infoThousands": ".",
    "lengthMenu": "Sayfada _MENU_ kayıt göster",
    "loadingRecords": "Yükleniyor...",
    "processing": "İşleniyor...",
    "search": "Ara:",
    "zeroRecords": "Eşleşen kayıt bulunamadı",
    "paginate": {
        "first": "İlk",
        "last": "Son",
        "next": "Sonraki",
        "previous": "Önceki"
    },
    "aria": {
        "sortAscending": ": artan sütun sıralamasını aktifleştir",
        "sortDescending": ": azalan sütun sıralamasını aktifleştir"
    }
};

$(document).ready(function() {
    var table = $("#artworksTable").DataTable({
        "responsive": true,
        "autoWidth": false,
        "processing": true,
        "pageLength": 10,
        "order": [[0, "desc"]], // ID sütununa göre azalan sırada sırala (en yüksek ID en üstte)
        "language": dataTables_turkish,
        "columnDefs": [
            { "orderable": false, "targets": [1, 8] } // Resim ve İşlemler sütunları sıralanabilir olmasın
        ],
        "drawCallback": function(settings) {
            lazyLoadImages();
        }
    });

    // Lazy loading görsel yükleme fonksiyonu
    function lazyLoadImages() {
        var lazyImages = document.querySelectorAll("img.lazy-load:not(.loaded)");
        
        if ("IntersectionObserver" in window) {
            let lazyImageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        let lazyImage = entry.target;
                        lazyImage.src = lazyImage.dataset.src;
                        lazyImage.onload = function() {
                            lazyImage.classList.add("loaded");
                        };
                        lazyImageObserver.unobserve(lazyImage);
                    }
                });
            });

            lazyImages.forEach(function(lazyImage) {
                lazyImageObserver.observe(lazyImage);
            });
        } else {
            // Intersection Observer desteği yoksa basit bir yükleme
            lazyImages.forEach(function(lazyImage) {
                lazyImage.src = lazyImage.dataset.src;
                lazyImage.classList.add("loaded");
            });
        }
    }

    // Sayfa ilk yüklendiğinde görüntülenen görselleri yükle
    lazyLoadImages();

    // Sayfa boyutu değiştiğinde görselleri kontrol et
    $(window).on("resize scroll", function() {
        lazyLoadImages();
    });
});

// Silme işlemi doğrulama fonksiyonu
function confirmDelete(id) {
    if (confirm("Bu eseri çöp kutusuna taşımak istiyor musunuz?")) {
        window.location.href = "?delete=" + id;
    }
}
</script>';

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
            <a href="trash.php" class="btn btn-secondary ml-2">
                <i class="fas fa-trash"></i> Çöp Kutusu
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
                    <th>Eser Adı</th>
                    <th>Doğrulama Kodu</th>
                    <th>Boyut</th>
                    <th>Teknik</th>
                    <th>Durum</th>
                    <th>NFC Yazıldı</th>
                    <th>Tarih</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($artworks as $artwork): ?>
                <tr>
                    <td><?php echo htmlspecialchars($artwork['id']); ?></td>
                    <td>
                        <?php if (!empty($artwork['image_path'])): ?>
                            <img data-src="../../<?php echo htmlspecialchars($artwork['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($artwork['title']); ?>"
                                 class="artwork-thumbnail lazy-load" 
                                 src="../../assets/img/placeholder.php">
                        <?php else: ?>
                            <span class="badge badge-secondary">Resim Yok</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($artwork['title']); ?></td>
                    <td><?php echo htmlspecialchars($artwork['verification_code']); ?></td>
                    <td><?php echo htmlspecialchars($artwork['dimensions']); ?></td>
                    <td><?php echo isset($techniques[$artwork['technique']]) ? htmlspecialchars($techniques[$artwork['technique']]) : ''; ?></td>
                    <td><?php echo isset($statuses[$artwork['status']]) ? htmlspecialchars($statuses[$artwork['status']]) : ''; ?></td>
                    <td>
                        <?php if ($artwork['nfc_written']): ?>
                            <span class="badge badge-success">Yazıldı</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Yazılmadı</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('d.m.Y', strtotime($artwork['created_at'])); ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="artwork_view.php?id=<?php echo $artwork['id']; ?>" class="btn btn-sm btn-info" title="Görüntüle">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="artwork_edit.php?id=<?php echo $artwork['id']; ?>" class="btn btn-sm btn-warning" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" onclick="confirmDelete(<?php echo $artwork['id']; ?>)" class="btn btn-sm btn-secondary" title="Çöp Kutusuna Taşı">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Footer'ı dahil et
include 'templates/footer.php';
?>
</body>
</html> 