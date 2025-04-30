<?php
// Mevcut sayfayı tespit et
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Menu -->
<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
            </a>
        </li>
        
        <li class="nav-item <?php echo in_array($current_page, ['artworks.php', 'artwork_add.php', 'artwork_edit.php', 'artwork_view.php', 'artwork_techniques.php', 'artwork_edition_types.php', 'artwork_statuses.php', 'artwork_locations.php']) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo in_array($current_page, ['artworks.php', 'artwork_add.php', 'artwork_edit.php', 'artwork_view.php', 'artwork_techniques.php', 'artwork_edition_types.php', 'artwork_statuses.php', 'artwork_locations.php']) ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-palette"></i>
                <p>
                    Eser Yönetimi
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="artworks.php" class="nav-link <?php echo $current_page == 'artworks.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Eser Listesi</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="artwork_add.php" class="nav-link <?php echo $current_page == 'artwork_add.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Yeni Eser Ekle</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="artwork_techniques.php" class="nav-link <?php echo $current_page == 'artwork_techniques.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Eser Teknikleri</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="artwork_edition_types.php" class="nav-link <?php echo $current_page == 'artwork_edition_types.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Baskı Türleri</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="artwork_statuses.php" class="nav-link <?php echo $current_page == 'artwork_statuses.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Eser Durumları</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="artwork_locations.php" class="nav-link <?php echo $current_page == 'artwork_locations.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Konumlar</p>
                    </a>
                </li>
            </ul>
        </li>
        
        <li class="nav-item <?php echo in_array($current_page, ['customers.php', 'customer_add.php', 'customer_edit.php', 'customer_view.php']) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo in_array($current_page, ['customers.php', 'customer_add.php', 'customer_edit.php', 'customer_view.php']) ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-users"></i>
                <p>
                    Müşteri Yönetimi
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="customers.php" class="nav-link <?php echo $current_page == 'customers.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Müşteri Listesi</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="customer_add.php" class="nav-link <?php echo $current_page == 'customer_add.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Yeni Müşteri Ekle</p>
                    </a>
                </li>
            </ul>
        </li>
        
        <li class="nav-item <?php echo in_array($current_page, ['orders.php', 'order_view.php']) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo in_array($current_page, ['orders.php', 'order_view.php']) ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-shopping-cart"></i>
                <p>
                    Sipariş Yönetimi
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="orders.php" class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Sipariş Listesi</p>
                    </a>
                </li>
            </ul>
        </li>
        
        <li class="nav-item">
            <a href="verification_logs.php" class="nav-link <?php echo $current_page == 'verification_logs.php' ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-clipboard-check"></i>
                <p>Doğrulama Kayıtları</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="nfc_writing_logs.php" class="nav-link <?php echo $current_page == 'nfc_writing_logs.php' ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-clipboard-check"></i>
                <p>NFC Yazdırma Kayıtları</p>
            </a>
        </li>

        <li class="nav-item <?php echo in_array($current_page, ['users.php', 'user_add.php', 'user_edit.php']) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo in_array($current_page, ['users.php', 'user_add.php', 'user_edit.php']) ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-user-shield"></i>
                <p>
                    Kullanıcı Yönetimi
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="users.php" class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Kullanıcı Listesi</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="user_add.php" class="nav-link <?php echo $current_page == 'user_add.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Yeni Kullanıcı Ekle</p>
                    </a>
                </li>
            </ul>
        </li>
        
        <li class="nav-item">
            <a href="certificate_design.php" class="nav-link <?php echo $current_page == 'certificate_design.php' ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-certificate"></i>
                <p>Sertifika Tasarımı</p>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-cog"></i>
                <p>Ayarlar</p>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="../../logout.php" class="nav-link">
                <i class="nav-icon fas fa-sign-out-alt"></i>
                <p>Çıkış Yap</p>
            </a>
        </li>
    </ul>
</nav>
<!-- /.sidebar-menu -->

<script>
// Sidebar menü davranışını düzelt - doğrudan sidebar_menu.php içinde
$(document).ready(function() {
    // Sayfa yüklendiğinde doğru menüyü açık tut
    $('.nav-item.menu-open > .nav-link').addClass('active');
    
    // Aktif menülerin kapanmasını engelle
    $('[data-widget="treeview"]').on('collapsed.lte.treeview', function(event) {
        const $navItem = $(event.target);
        if ($navItem.find('.nav-link.active').length > 0) {
            setTimeout(function() {
                $navItem.addClass('menu-open');
                $navItem.find('> .nav-treeview').slideDown();
            }, 10);
        }
    });
    
    // Doğrulama Kayıtları menüsüne özel davranış
    if ($('a[href="verification_logs.php"]').hasClass('active')) {
        $('a[href="verification_logs.php"]').parents('.nav-sidebar').find('.menu-open').removeClass('menu-open');
        $('a[href="verification_logs.php"]').parents('.nav-sidebar').find('.nav-treeview:visible').slideUp();
    }
});
</script>
 
 