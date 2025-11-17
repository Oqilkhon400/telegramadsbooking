<?php
/**
 * Create Customer
 * Yangi mijoz qo'shish
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
$full_name = isset($_POST['full_name']) ? clean_input($_POST['full_name']) : '';
$phone = isset($_POST['phone']) ? clean_input($_POST['phone']) : '';
$telegram_id = isset($_POST['telegram_id']) ? clean_input($_POST['telegram_id']) : null;
$telegram_username = isset($_POST['telegram_username']) ? clean_input($_POST['telegram_username']) : null;
$notes = isset($_POST['notes']) ? clean_input($_POST['notes']) : null;

// Validatsiya
$errors = [];

if (empty($full_name)) {
    $errors[] = 'Ism-familiya kiritilishi shart';
}

if (empty($phone)) {
    $errors[] = 'Telefon raqam kiritilishi shart';
}

// Telefon raqam formatini tekshirish
if (!empty($phone) && !preg_match('/^[\+]?[0-9]{9,15}$/', $phone)) {
    $errors[] = 'Telefon raqam formati noto\'g\'ri';
}

if (!empty($errors)) {
    send_error(implode(', ', $errors));
}

try {
    // Telefon raqam mavjudligini tekshirish
    $check_stmt = $conn->prepare("SELECT id FROM customers WHERE phone = ? LIMIT 1");
    $check_stmt->bind_param("s", $phone);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        send_error('Bu telefon raqam bilan mijoz allaqachon mavjud');
    }
    
    // Mijozni qo'shish
    $stmt = $conn->prepare("
        INSERT INTO customers 
        (full_name, phone, telegram_id, telegram_username, notes, created_by) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $created_by = $_SESSION['user_id'];
    
    $stmt->bind_param(
        "sssssi",
        $full_name,
        $phone,
        $telegram_id,
        $telegram_username,
        $notes,
        $created_by
    );
    
    if ($stmt->execute()) {
        $customer_id = $conn->insert_id;
        
        // Yangi mijoz ma'lumotlarini olish
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
        
        send_success($customer, 'Mijoz muvaffaqiyatli qo\'shildi');
    } else {
        throw new Exception('Mijoz qo\'shishda xatolik');
    }
    
} catch (Exception $e) {
    error_log("Create customer error: " . $e->getMessage());
    send_error('Mijoz qo\'shishda xatolik yuz berdi');
}

$conn->close();
?>