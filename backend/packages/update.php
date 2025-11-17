<?php
/**
 * Update Package
 * Paket ma'lumotlarini yangilash
 */

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
$package_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name = isset($_POST['name']) ? clean_input($_POST['name']) : '';
$ads_count = isset($_POST['ads_count']) ? (int)$_POST['ads_count'] : 0;
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
$description = isset($_POST['description']) ? clean_input($_POST['description']) : null;
$is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

// Validatsiya
$errors = [];

if ($package_id <= 0) {
    $errors[] = 'Noto\'g\'ri paket ID';
}

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
    // Paket mavjudligini tekshirish
    $check_stmt = $conn->prepare("SELECT id FROM packages WHERE id = ? LIMIT 1");
    $check_stmt->bind_param("i", $package_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        send_error('Paket topilmadi', 404);
    }
    
    // Paket nomini tekshirish (boshqa paketda)
    $name_check = $conn->prepare("
        SELECT id FROM packages 
        WHERE name = ? AND id != ? 
        LIMIT 1
    ");
    $name_check->bind_param("si", $name, $package_id);
    $name_check->execute();
    
    if ($name_check->get_result()->num_rows > 0) {
        send_error('Bu nom bilan boshqa paket mavjud');
    }
    
    // Paketni yangilash
    $stmt = $conn->prepare("
        UPDATE packages 
        SET name = ?,
            ads_count = ?,
            price = ?,
            description = ?,
            is_active = ?
        WHERE id = ?
    ");
    
    $stmt->bind_param("sidsii", $name, $ads_count, $price, $description, $is_active, $package_id);
    
    if ($stmt->execute()) {
        // Yangilangan paket ma'lumotlarini olish
        $get_stmt = $conn->prepare("SELECT * FROM packages WHERE id = ?");
        $get_stmt->bind_param("i", $package_id);
        $get_stmt->execute();
        $result = $get_stmt->get_result();
        $package = $result->fetch_assoc();
        
        // Ma'lumotlarni formatlash
        $package['price'] = format_money($package['price']);
        $package['created_at'] = format_datetime($package['created_at']);
        $package['is_active'] = (bool)$package['is_active'];
        
        send_success($package, 'Paket ma\'lumotlari yangilandi');
    } else {
        throw new Exception('Paket yangilashda xatolik');
    }
    
} catch (Exception $e) {
    error_log("Update package error: " . $e->getMessage());
    send_error('Paket yangilashda xatolik yuz berdi');
}

$conn->close();
?>