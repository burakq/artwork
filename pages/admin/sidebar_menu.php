<!-- Sidebar Menu -->
<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
            </a>
        </li>
        
        <li class="nav-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['artworks.php', 'artwork_add.php', 'artwork_edit.php', 'artwork_view.php']) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['artworks.php', 'artwork_add.php', 'artwork_edit.php', 'artwork_view.php']) ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-palette"></i>
                <p>
                    Eser Yönetimi
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="artworks.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'artworks.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Eser Listesi</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="artwork_add.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'artwork_add.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Yeni Eser Ekle</p>
                    </a>
                </li>
            </ul>
        </li>
        
        <li class="nav-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['customers.php', 'customer_add.php', 'customer_edit.php', 'customer_view.php']) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['customers.php', 'customer_add.php', 'customer_edit.php', 'customer_view.php']) ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-users"></i>
                <p>
                    Müşteri Yönetimi
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="customers.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Müşteri Listesi</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="customer_add.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'customer_add.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Yeni Müşteri Ekle</p>
                    </a>
                </li>
            </ul>
        </li>
        
        <li class="nav-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['orders.php', 'order_view.php']) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['orders.php', 'order_view.php']) ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-shopping-cart"></i>
                <p>
                    Sipariş Yönetimi
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="orders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Sipariş Listesi</p>
                    </a>
                </li>
            </ul>
        </li>
        
        <li class="nav-item">
            <a href="verification_logs.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'verification_logs.php' ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-clipboard-check"></i>
                <p>Doğrulama Kayıtları</p>
            </a>
        </li>
        
        <li class="nav-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['users.php', 'user_add.php', 'user_edit.php']) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['users.php', 'user_add.php', 'user_edit.php']) ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-user-shield"></i>
                <p>
                    Kullanıcı Yönetimi
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Kullanıcı Listesi</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="user_add.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_add.php' ? 'active' : ''; ?>">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Yeni Kullanıcı Ekle</p>
                    </a>
                </li>
            </ul>
        </li>
        
        <li class="nav-item">
            <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
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