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
$error = '';

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Form verilerini al
    $name = sanitize($_POST['name']);
    $type = sanitize($_POST['type']);
    $company_name = isset($_POST['company_name']) ? sanitize($_POST['company_name']) : null;
    $tax_number = isset($_POST['tax_number']) ? sanitize($_POST['tax_number']) : null;
    $tax_office = isset($_POST['tax_office']) ? sanitize($_POST['tax_office']) : null;
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $notes = sanitize($_POST['notes']);
    $shipping_address = sanitize($_POST['shipping_address']);
    $shipping_city = sanitize($_POST['shipping_city']);
    $shipping_state = sanitize($_POST['shipping_state']);
    $shipping_postal_code = sanitize($_POST['shipping_postal_code']);
    $shipping_country = sanitize($_POST['shipping_country']);
    
    // E-posta adresi kontrolü
    $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ? AND deleted_at IS NULL LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $error = "Bu e-posta adresi ile kayıtlı bir müşteri zaten mevcut.";
    } else {
        // Müşteri ekle
        $stmt = $conn->prepare("INSERT INTO customers (name, type, company_name, tax_number, tax_office, email, phone, address, notes, 
                               shipping_address, shipping_city, shipping_state, shipping_postal_code, shipping_country, created_at, updated_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        
        $stmt->bind_param("ssssssssssssss", 
            $name, $type, $company_name, $tax_number, $tax_office, $email, $phone, $address, $notes, 
            $shipping_address, $shipping_city, $shipping_state, $shipping_postal_code, $shipping_country);
        
        if ($stmt->execute()) {
            // Başarıyla eklendi
            redirect('customers.php?success=1');
        } else {
            // Hata oluştu
            $error = "Müşteri eklenirken bir hata oluştu: " . $conn->error;
        }
    }
    
    $stmt->close();
}

// Bağlantıyı kapat
$conn->close();

// Sayfa başlığı ve breadcrumb
$page_title = "Yeni Müşteri Ekle";
$breadcrumb = '<li class="breadcrumb-item"><a href="dashboard.php">Ana Sayfa</a></li>
               <li class="breadcrumb-item"><a href="customers.php">Müşteriler</a></li>
               <li class="breadcrumb-item active">Yeni Müşteri Ekle</li>';

// Select2 için ek CSS
$additional_css = '
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">';

// Header'ı dahil et
include 'templates/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Müşteri Bilgileri</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="name">Ad Soyad / Kurum Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Müşteri Türü <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="type" name="type" required>
                            <option value="individual">Bireysel</option>
                            <option value="corporate">Kurumsal</option>
                            <option value="gallery">Galeri</option>
                            <option value="collector">Koleksiyoner</option>
                        </select>
                    </div>
                    
                    <div id="companyFields" style="display: none;">
                        <div class="form-group">
                            <label for="company_name">Firma Adı</label>
                            <input type="text" class="form-control" id="company_name" name="company_name">
                        </div>
                        
                        <div class="form-group">
                            <label for="tax_number">Vergi Numarası</label>
                            <input type="text" class="form-control" id="tax_number" name="tax_number">
                        </div>
                        
                        <div class="form-group">
                            <label for="tax_office">Vergi Dairesi</label>
                            <input type="text" class="form-control" id="tax_office" name="tax_office">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-posta <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Telefon</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Adres</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notlar</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="shipping_address">Teslimat Adresi</label>
                        <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="shipping_city">Şehir</label>
                        <input type="text" class="form-control" id="shipping_city" name="shipping_city">
                    </div>
                    
                    <div class="form-group">
                        <label for="shipping_state">İlçe</label>
                        <input type="text" class="form-control" id="shipping_state" name="shipping_state">
                    </div>
                    
                    <div class="form-group">
                        <label for="shipping_postal_code">Posta Kodu</label>
                        <input type="text" class="form-control" id="shipping_postal_code" name="shipping_postal_code">
                    </div>
                    
                    <div class="form-group">
                        <label for="shipping_country">Ülke</label>
                        <input type="text" class="form-control" id="shipping_country" name="shipping_country" value="Türkiye">
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <a href="customers.php" class="btn btn-secondary">İptal</a>
                    <button type="submit" class="btn btn-primary float-right">Kaydet</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
// Select2 ve özel JavaScript
$additional_js = '
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Select2 başlat
    $(".select2").select2({
        theme: "bootstrap-5"
    });
    
    // Müşteri türü değiştiğinde firma alanlarını göster/gizle
    $("#type").change(function() {
        if ($(this).val() === "corporate") {
            $("#companyFields").slideDown();
            $("#company_name, #tax_number, #tax_office").prop("required", true);
        } else {
            $("#companyFields").slideUp();
            $("#company_name, #tax_number, #tax_office").prop("required", false);
        }
    });
});
</script>';

// Footer'ı dahil et
include 'templates/footer.php';
?> 