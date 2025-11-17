<?php
/**
 * General Settings
 * Umumiy sozlamalar va konstantalar
 */

// Loyiha sozlamalari
define('SITE_NAME', 'Reklama Booking Platform');
define('SITE_URL', 'https://reklama.kosonsoyliklar.uz'); // O'z domeningizni yozing
define('ADMIN_EMAIL', 'kosonsoyliklaruzb@gmail.com');

// Timezone
date_default_timezone_set('Asia/Tashkent');

// Telegram Bot sozlamalari
define('TELEGRAM_BOT_TOKEN', '8248487058:AAFJgp-hpzClms8gb7wKVyTVCxzgTaQn3nE'); // Botfather dan olingan token
define('TELEGRAM_API_URL', 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN);

// Reklama vaqt sozlamalari
define('AD_START_HOUR', 7);  // 07:00
define('AD_END_HOUR', 22);   // 22:00

// Eslatma sozlamalari (soatlarda)
define('REMINDER_HOURS_BEFORE', 1); // 1 soat oldin eslatma

// Pagination
define('ITEMS_PER_PAGE', 20);

// Session timeout (daqiqalarda)
define('SESSION_TIMEOUT', 480); // 8 soat

// Fayl upload sozlamalari (agar kerak bo'lsa)
define('UPLOAD_DIR', '../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Currency
define('CURRENCY', 'so\'m');
define('CURRENCY_SYMBOL', 'so\'m');

// Xatoliklarni ko'rsatish (development uchun)
// Production da ini_set('display_errors', 0); qiling
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Xatoliklarni log qilish
ini_set('log_errors', 1);
ini_set('error_log', '../logs/error.log'); // logs papkasini yarating

// CORS sozlamalari (agar API kerak bo'lsa)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Helper funksiyalar

/**
 * Sana formatlash
 */
function format_date($date, $format = 'd.m.Y') {
    return date($format, strtotime($date));
}

/**
 * Sana va vaqt formatlash
 */
function format_datetime($datetime, $format = 'd.m.Y H:i') {
    return date($format, strtotime($datetime));
}

/**
 * Summani formatlash
 */
function format_money($amount) {
    return number_format($amount, 0, ',', ' ') . ' ' . CURRENCY_SYMBOL;
}

/**
 * Telefon raqamni formatlash
 */
function format_phone($phone) {
    // +998901234567 -> +998 90 123 45 67
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    if (strlen($phone) == 12 && substr($phone, 0, 4) == '+998') {
        return '+998 ' . substr($phone, 4, 2) . ' ' . 
               substr($phone, 6, 3) . ' ' . 
               substr($phone, 9, 2) . ' ' . 
               substr($phone, 11, 2);
    }
    return $phone;
}

/**
 * Vaqt slotlarini generatsiya qilish
 */
function generate_time_slots() {
    $slots = [];
    for ($hour = AD_START_HOUR; $hour <= AD_END_HOUR; $hour++) {
        $slots[] = sprintf('%02d:00', $hour);
    }
    return $slots;
}

/**
 * Session timeout tekshirish
 */
function check_session_timeout() {
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > SESSION_TIMEOUT * 60) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Login tekshirish
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && check_session_timeout();
}

/**
 * Superadmin tekshirish
 */
function is_superadmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
}

/**
 * Redirect funksiyasi
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}
?>