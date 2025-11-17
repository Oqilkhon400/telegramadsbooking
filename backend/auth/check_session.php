<?php
/**
 * Check Session
 * Session mavjudligini va aktiv ekanligini tekshirish
 */

require_once '../../config/database.php';
require_once '../../config/settings.php';

// Session tekshirish
if (!is_logged_in()) {
    send_error('Session tugagan yoki mavjud emas', 401);
}

// Foydalanuvchi ma'lumotlarini olish
try {
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("
        SELECT id, username, full_name, role, is_active, last_login 
        FROM users 
        WHERE id = ? 
        LIMIT 1
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        session_destroy();
        send_error('Foydalanuvchi topilmadi', 401);
    }
    
    $user = $result->fetch_assoc();
    
    // Faollik tekshiruvi
    if ($user['is_active'] != 1) {
        session_destroy();
        send_error('Hisobingiz bloklangan', 403);
    }
    
    // Session ma'lumotlarini yangilash
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    
    // Session qolgan vaqtini hisoblash
    $session_age = time() - $_SESSION['login_time'];
    $session_remaining = (SESSION_TIMEOUT * 60) - (time() - $_SESSION['last_activity']);
    
    // Muvaffaqiyatli javob
    send_success([
        'authenticated' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'last_login' => format_datetime($user['last_login'])
        ],
        'session' => [
            'age_seconds' => $session_age,
            'remaining_seconds' => $session_remaining,
            'timeout_minutes' => SESSION_TIMEOUT
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Check session error: " . $e->getMessage());
    send_error('Session tekshirishda xatolik', 500);
}

$conn->close();
?>