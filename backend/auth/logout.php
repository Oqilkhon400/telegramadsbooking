<?php
/**
 * Logout
 * Tizimdan chiqish
 */

require_once '../../config/database.php';
require_once '../../config/settings.php';

// Session ma'lumotlarini tozalash
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? 'Unknown';
    
    // Log qilish (ixtiyoriy)
    error_log("User logout: ID=$user_id, Username=$username");
}

// Session ni to'liq yo'q qilish
session_unset();
session_destroy();

// Cookie'larni tozalash (agar mavjud bo'lsa)
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Javob yuborish
send_success([
    'logged_out' => true,
    'redirect_url' => '../../frontend/login.php'
], 'Tizimdan muvaffaqiyatli chiqdingiz');
?>