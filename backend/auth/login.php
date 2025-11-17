<?php
// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS request uchun
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Error reporting (debug uchun)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';
require_once '../../config/settings.php';

// Faqat POST metodini qabul qilish
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Faqat POST metodi qabul qilinadi', 405);
}

// Ma'lumotlarni olish
$username = isset($_POST['username']) ? clean_input($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Validatsiya
if (empty($username) || empty($password)) {
    send_error('Username va parol kiritilishi shart');
}

try {
    // Foydalanuvchini topish
    $stmt = $conn->prepare("
        SELECT id, username, password, full_name, role, is_active 
        FROM users 
        WHERE username = ? 
        LIMIT 1
    ");
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Foydalanuvchi topilmadi
    if ($result->num_rows === 0) {
        send_error('Username yoki parol noto\'g\'ri');
    }
    
    $user = $result->fetch_assoc();
    
    // Faol emasligini tekshirish
    if ($user['is_active'] != 1) {
        send_error('Sizning hisobingiz bloklangan. Administratorga murojaat qiling.');
    }
    
    // Parolni tekshirish
    if (!password_verify($password, $user['password'])) {
        send_error('Username yoki parol noto\'g\'ri');
    }
    
    // Session ma'lumotlarini saqlash
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();
    $_SESSION['login_time'] = time();
    
    // Last login vaqtini yangilash
    $update_stmt = $conn->prepare("
        UPDATE users 
        SET last_login = NOW() 
        WHERE id = ?
    ");
    $update_stmt->bind_param("i", $user['id']);
    $update_stmt->execute();
    
    // Muvaffaqiyatli javob
    send_success([
        'user_id' => $user['id'],
        'username' => $user['username'],
        'full_name' => $user['full_name'],
        'role' => $user['role']
    ], 'Xush kelibsiz, ' . $user['full_name'] . '!');
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    send_error('Tizimda xatolik yuz berdi. Iltimos, qayta urinib ko\'ring.');
}

$conn->close();
?>