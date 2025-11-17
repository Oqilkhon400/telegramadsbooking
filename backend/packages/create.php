<?php
/**
 * Create Package
 * Yangi paket yaratish
 */

// Debug uchun
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';
require_once '../../config/settings.php';

// Session tekshirish
if (!is_logged_in()) {
    send_error('Tizimga kirish kerak', 401);
}

// Faqat POST metodini qabul qilish
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Faqat POST metodi qabul qilinadi', 405);
}

// Ma'lumotlarni olish
$name = isset($_POST['name']) ? clean_input($_POST['name']) : '';
$ads_count = isset($_POST['ads_count']) ? (int)$_POST['ads_count'] : 0;
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
$description = isset($_POST['description']) ? clean_input($_POST['description']) : null;

// Validatsiya
$errors = [];

if (empty($name)) {
    $errors[] = 'Paket nomi kiritilishi shart';
}

if ($ads_count <= 0) {
    $errors[] = 'Reklama soni 0 dan katta bo\'lishi kerak';
}

if ($price <= 0) {
    $errors[] = 'Narx 0 dan katta bo\'lishi kerak';
}

if (!empty($errors)) {
    send_error(implode(', ', $errors));
}

try {
    // Paket nomi unique emas - bir xil nomda turli paketlar bo'lishi mumkin
    
    // Paket yaratish
    $stmt = $conn->prepare("
        INSERT INTO packages 
        (name, ads_count, price, description) 
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->bind_param("sids", $name, $ads_count, $price, $description);
    
    if ($stmt->execute()) {
        $package_id = $conn->insert_id;
        
        // Yangi paket ma'lumotlarini olish
        $get_stmt = $conn->prepare("SELECT * FROM packages WHERE id = ?");
        $get_stmt->bind_param("i", $package_id);
        $get_stmt->execute();
        $result = $get_stmt->get_result();
        $package = $result->fetch_assoc();
        
        // Ma'lumotlarni formatlash
        $package['price'] = format_money($package['price']);
        $package['created_at'] = format_datetime($package['created_at']);
        $package['is_active'] = (bool)$package['is_active'];
        
        send_success($package, 'Paket muvaffaqiyatli yaratildi');
    } else {
        throw new Exception('Paket yaratishda xatolik');
    }
    
} catch (Exception $e) {
    error_log("Create package error: " . $e->getMessage());
    send_error('Paket yaratishda xatolik yuz berdi');
}

$conn->close();
?>