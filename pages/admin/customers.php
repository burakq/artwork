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

// Müşteri silme işlemi
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    
    if ($id) {
        // Silme işlemini gerçekleştir (soft delete)
        $stmt = $conn->prepare("UPDATE customers SET deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Müşteri başarıyla silindi.";
            $message_type = "success";
        } else {
            $message = "Müşteri silinirken bir hata oluştu: " . $conn->error;
            $message_type = "danger";
        }
        
        $stmt->close();
    }
}

// Müşterileri getir
$customers = [];
$sql = "SELECT * FROM customers WHERE deleted_at IS NULL ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}

// Bağlantıyı kapat
$conn->close();

// Sayfa başlığı ve breadcrumb
$page_title = "Müşteriler";
$breadcrumb = '<li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
               <li class="breadcrumb-item active">Müşteriler</li>';

// DataTables için ek CSS ve JS
$additional_css = '
<link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css">';

$additional_js = '
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    $("#customersTable").DataTable({
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json"
        },
        "order": [[0, "desc"]]
    });
});

function confirmDelete(id) {
    if (confirm("Bu müşteriyi silmek istediğinize emin misiniz?")) {
        window.location.href = "customers.php?delete=" + id;
    }
}
</script>';

// Header'ı dahil et
include 'templates/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Müşteri Listesi</h3>
        <div class="card-tools">
            <a href="customer_add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Yeni Müşteri Ekle
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
        
        <table id="customersTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th style="width: 10px">#</th>
                    <th>İsim</th>
                    <th>Tür</th>
                    <th>Firma Adı</th>
                    <th>E-posta</th>
                    <th>Telefon</th>
                    <th>Kayıt Tarihi</th>
                    <th style="width: 150px">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><?php echo $customer['id']; ?></td>
                    <td><?php echo htmlspecialchars($customer['name']); ?></td>
                    <td>
                        <?php
                        $typeClass = 'secondary';
                        $typeText = 'Bilinmiyor';
                        switch ($customer['type']) {
                            case 'individual':
                                $typeClass = 'primary';
                                $typeText = 'Bireysel';
                                break;
                            case 'corporate':
                                $typeClass = 'success';
                                $typeText = 'Kurumsal';
                                break;
                            case 'gallery':
                                $typeClass = 'info';
                                $typeText = 'Galeri';
                                break;
                            case 'collector':
                                $typeClass = 'warning';
                                $typeText = 'Koleksiyoner';
                                break;
                        }
                        ?>
                        <span class="badge badge-<?php echo $typeClass; ?>"><?php echo $typeText; ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($customer['company_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($customer['created_at'])); ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="customer_view.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-info" title="Görüntüle">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="customer_edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-warning" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $customer['id']; ?>)" class="btn btn-sm btn-danger" title="Sil">
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
include 'templates/footer.php';
?> 