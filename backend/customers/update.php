<?php
/**
 * Update Customer
 * Mijoz ma'lumotlarini yangilash
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
$customer_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$full_name = isset($_POST['full_name']) ? clean_input($_POST['full_name']) : '';
$phone = isset($_POST['phone']) ? clean_input($_POST['phone']) : '';
$telegram_id = isset($_POST['telegram_id']) ? clean_input($_POST['telegram_id']) : null;
$telegram_username = isset($_POST['telegram_username']) ? clean_input($_POST['telegram_username']) : null;
$notes = isset($_POST['notes']) ? clean_input($_POST['notes']) : null;
$is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

// Validatsiya
$errors = [];

if ($customer_id <= 0) {
    $errors[] = 'Noto\'g\'ri mijoz ID';
}

if (empty($full_name)) {
    $errors[] = 'Ism-familiya kiritilishi shart';
}

if (empty($phone)) {
    $errors[] = 'Telefon raqam kiritilishi shart';
}

if (!empty($phone) && !preg_match('/^[\+]?[0-9]{9,15}$/', $phone)) {
    $errors[] = 'Telefon raqam formati noto\'g\'ri';
}

if (!empty($errors)) {
    send_error(implode(', ', $errors));
}

try {
    // Mijoz mavjudligini tekshirish
    $check_stmt = $conn->prepare("SELECT id FROM customers WHERE id = ? LIMIT 1");
    $check_stmt->bind_param("i", $customer_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        send_error('Mijoz topilmadi', 404);
    }
    
    // Telefon raqam boshqa mijozda mavjud emasligini tekshirish
    $phone_check = $conn->prepare("
        SELECT id FROM customers 
        WHERE phone = ? AND id != ? 
        LIMIT 1
    ");
    $phone_check->bind_param("si", $phone, $customer_id);
    $phone_check->execute();
    
    if ($phone_check->get_result()->num_rows > 0) {
        send_error('Bu telefon raqam bilan boshqa mijoz mavjud');
    }
    
    // Mijozni yangilash
    $stmt = $conn->prepare("
        UPDATE customers 
        SET full_name = ?,
            phone = ?,
            telegram_id = ?,
            telegram_username = ?,
            notes = ?,
            is_active = ?
        WHERE id = ?
    ");
    
    $stmt->bind_param(
        "sssssii",
        $full_name,
        $phone,
        $telegram_id,
        $telegram_username,
        $notes,
        $is_active,
        $customer_id
    );
    
    if ($stmt->execute()) {
        // Yangilangan mijoz ma'lumotlarini olish
        $get_stmt = $conn->prepare("
            SELECT c.*, u.full_name as created_by_name 
            FROM customers c
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.id = ?
        ");
        $get_stmt->bind_param("i", $customer_id);
        $get_stmt->execute();
        $result = $get_stmt->get_result();
        $customer = $result->fetch_assoc();
        
        // Ma'lumotlarni formatlash
        $customer['phone'] = format_phone($customer['phone']);
        $customer['created_at'] = format_datetime($customer['created_at']);
        $customer['is_active'] = (bool)$customer['is_active'];
        
        send_success($customer, 'Mijoz ma\'lumotlari yangilandi');
    } else {
        throw new Exception('Mijoz yangilashda xatolik');
    }
    
} catch (Exception $e) {
    error_log("Update customer error: " . $e->getMessage());
    send_error('Mijoz yangilashda xatolik yuz berdi');
}

$conn->close();
?>