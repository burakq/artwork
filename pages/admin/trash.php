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

// Eser geri getirme işlemi
if (isset($_GET['restore']) && !empty($_GET['restore'])) {
    $id = filter_var($_GET['restore'], FILTER_VALIDATE_INT);
    
    if ($id) {
        $stmt = $conn->prepare("UPDATE artworks SET deleted_at = NULL WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Eser başarıyla geri getirildi.";
            $message_type = "success";
        } else {
            $message = "Eser geri getirilirken bir hata oluştu: " . $conn->error;
            $message_type = "danger";
        }
        
        $stmt->close();
    }
}

// Eser kalıcı silme işlemi
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM artworks WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Eser kalıcı olarak silindi.";
            $message_type = "success";
        } else {
            $message = "Eser silinirken bir hata oluştu: " . $conn->error;
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
$sql = "SELECT * FROM artworks WHERE deleted_at IS NOT NULL ORDER BY id DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $artworks[] = $row;
    }
}

// Bağlantıyı kapat
$conn->close();

// Sayfa başlığı ve breadcrumb
$page_title = "Çöp Kutusu";
$breadcrumb = '<li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
               <li class="breadcrumb-item"><a href="artworks.php">Sanat Eserleri</a></li>
               <li class="breadcrumb-item active">Çöp Kutusu</li>';

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

// İşlem doğrulama fonksiyonları
function confirmRestore(id) {
    if (confirm("Bu eseri çöp kutusundan geri getirmek istediğinize emin misiniz?")) {
        window.location.href = "?restore=" + id;
    }
}

function confirmDelete(id) {
    if (confirm("DİKKAT: Bu eser kalıcı olarak silinecek ve geri getirilemeyecektir! Devam etmek istiyor musunuz?")) {
        window.location.href = "?delete=" + id;
    }
}
</script>';

// Header'ı dahil et
include 'templates/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Çöp Kutusu</h3>
        <div class="card-tools">
            <a href="artworks.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Aktif Eserlere Dön
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
        
        <?php if (empty($artworks)): ?>
            <div class="alert alert-info">
                Çöp kutusunda eser bulunmamaktadır.
            </div>
        <?php else: ?>
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
                        <th>Silinme Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($artworks as $artwork): ?>
                    <tr>
                        <td><?php echo $artwork['id']; ?></td>
                        <td>
                            <?php if (!empty($artwork['image_path'])): ?>
                                <img data-src="../../<?php echo $artwork['image_path']; ?>" alt="<?php echo htmlspecialchars($artwork['title']); ?>" class="artwork-thumbnail lazy-load" src="../../assets/img/placeholder.php">
                            <?php else: ?>
                                <span class="badge badge-secondary">Resim Yok</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($artwork['title']); ?></td>
                        <td><?php echo htmlspecialchars($artwork['verification_code']); ?></td>
                        <td><?php echo htmlspecialchars($artwork['dimensions'] . ' ' . $artwork['size']); ?></td>
                        <td><?php echo isset($techniques[$artwork['technique']]) ? htmlspecialchars($techniques[$artwork['technique']]) : htmlspecialchars($artwork['technique']); ?></td>
                        <td>
                            <?php
                            $status_class = '';
                            
                            // Status değerini kullanarak artwork_statuses tablosunda arama yap
                            $status_text = isset($statuses[$artwork['status']]) ? $statuses[$artwork['status']] : 'Bilinmiyor';
                            
                            switch($artwork['status']) {
                                case 'original':
                                    $status_class = 'badge-success';
                                    break;
                                case 'for_sale':
                                    $status_class = 'badge-warning';
                                    break;
                                case 'sold':
                                    $status_class = 'badge-danger';
                                    break;
                                case 'fake':
                                    $status_class = 'badge-dark';
                                    break;
                                case 'archived':
                                    $status_class = 'badge-secondary';
                                    break;
                                default:
                                    $status_class = 'badge-info';
                            }
                            ?>
                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </td>
                        <td><?php echo !empty($artwork['deleted_at']) ? date('d.m.Y H:i', strtotime($artwork['deleted_at'])) : '-'; ?></td>
                        <td>
                            <div class="btn-group">
                                <button type="button" onclick="confirmRestore(<?php echo $artwork['id']; ?>)" class="btn btn-sm btn-success" title="Geri Getir">
                                    <i class="fas fa-trash-restore"></i>
                                </button>
                                <button type="button" onclick="confirmDelete(<?php echo $artwork['id']; ?>)" class="btn btn-sm btn-danger" title="Kalıcı Sil">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
// Footer'ı dahil et
include 'templates/footer.php';
?> 