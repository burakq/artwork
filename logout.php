<?php
session_start();

// Tüm session değişkenlerini kaldır
$_SESSION = array();

// Session cookie'sini yok et
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session'ı sonlandır
session_destroy();

// Login sayfasına yönlendir
header("Location: login.php");
exit;
?> 