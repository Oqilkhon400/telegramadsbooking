<?php
/**
 * Database Configuration
 * Database ulanish sozlamalari
 */

// Session boshlash
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database parametrlari
define('DB_HOST', 'localhost');
define('DB_USER', 'kosonsoy_hisobot');
define('DB_PASS', 'Jd5NE3RhhwNv2YzwvNM9');
define('DB_NAME', 'kosonsoy_hisobot');

// Database ulanish
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Ulanish xatoligini tekshirish
    if ($conn->connect_error) {
        throw new Exception("Database ulanishida xatolik: " . $conn->connect_error);
    }
    
    // UTF-8 encoding o'rnatish
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Xatolikni log qilish (production uchun)
    error_log($e->getMessage());
    
    // Foydalanuvchiga xabar
    die("
        <div style='text-align:center; margin-top:50px; font-family:Arial;'>
            <h2>⚠️ Tizimda xatolik</h2>
            <p>Database bilan bog'lanishda muammo yuz berdi.</p>
            <p>Iltimos, administratorga murojaat qiling.</p>
        </div>
    ");
}

// Xavfsizlik: SQL injection oldini olish uchun helper funksiya
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// JSON response yuborish uchun helper
function send_json($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Xatolik response yuborish
function send_error($message, $status_code = 400) {
    send_json([
        'success' => false,
        'error' => $message
    ], $status_code);
}

// Muvaffaqiyatli response yuborish
function send_success($data, $message = null) {
    $response = [
        'success' => true,
        'data' => $data
    ];
    
    if ($message) {
        $response['message'] = $message;
    }
    
    send_json($response);
}
?>