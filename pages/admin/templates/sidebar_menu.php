<nav class="sidebar">
    <div class="sidebar-content">
        <div class="sidebar-header">
            <h1>Yönetim Paneli</h1>
        </div>
        <div class="sidebar-body">
            <ul class="sidebar-menu">
                <!-- NFC Yönetimi -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'nfc_management.php') ? 'active' : ''; ?>" href="nfc_management.php">
                        <i class="fas fa-qrcode"></i>
                        <span>NFC Yönetimi</span>
                    </a>
                </li>
                <!-- Eser Yönetimi -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'artworks.php') ? 'active' : ''; ?>" href="artworks.php">
                        <i class="fas fa-paint-brush"></i>
                        <span>Eser Yönetimi</span>
                    </a>
                </li>
                <!-- Müşteri Yönetimi -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'customers.php') ? 'active' : ''; ?>" href="customers.php">
                        <i class="fas fa-users"></i>
                        <span>Müşteri Yönetimi</span>
                    </a>
                </li>
                <!-- Sipariş Yönetimi -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'orders.php') ? 'active' : ''; ?>" href="orders.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Sipariş Yönetimi</span>
                    </a>
                </li>
                <!-- Doğrulama Logları -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'verification_logs.php') ? 'active' : ''; ?>" href="verification_logs.php">
                        <i class="fas fa-check-circle"></i>
                        <span>Doğrulama Logları</span>
                    </a>
                </li>
                <!-- Kullanıcı Yönetimi -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>" href="users.php">
                        <i class="fas fa-user-cog"></i>
                        <span>Kullanıcı Yönetimi</span>
                    </a>
                </li>
                <!-- Ayarlar -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>" href="settings.php">
                        <i class="fas fa-cog"></i>
                        <span>Ayarlar</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav> 