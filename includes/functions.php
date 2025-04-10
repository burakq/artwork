<?php
require_once __DIR__ . '/../config/db.php';

/**
 * Güvenli şekilde verileri temizler
 * @param string $data Temizlenecek veri
 * @return string Temizlenmiş veri
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Kullanıcı girişini kontrol eder
 * @param string $email Kullanıcı e-posta adresi
 * @param string $password Şifre
 * @return array|false Başarılı ise kullanıcı bilgileri, değilse false
 */
function loginUser($email, $password) {
    $conn = connectDB();
    
    $email = $conn->real_escape_string($email);
    
    $sql = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Şifre doğrulama
        if (password_verify($password, $user['password'])) {
            $conn->close();
            return $user;
        }
    }
    
    $conn->close();
    return false;
}

/**
 * Oturum başlatır
 * @param array $user Kullanıcı bilgileri
 */
function createUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['is_admin'] = isset($user['is_admin']) ? $user['is_admin'] : 0;
    $_SESSION['logged_in'] = true;
}

/**
 * Kullanıcının admin olup olmadığını kontrol eder
 * @return bool Admin ise true, değilse false
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

/**
 * Kullanıcının giriş yapmış olup olmadığını kontrol eder
 * @return bool Giriş yapmışsa true, değilse false
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Yönlendirme yapar
 * @param string $location Yönlendirilecek URL
 */
function redirect($location) {
    header("Location: $location");
    exit;
}

/**
 * Hata veya başarı mesajlarını gösterir
 * @param string $message Gösterilecek mesaj
 * @param string $type Mesaj tipi (success, danger, warning, info)
 * @return string HTML alert elementi
 */
function showAlert($message, $type = 'info') {
    return "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>";
}
?> 