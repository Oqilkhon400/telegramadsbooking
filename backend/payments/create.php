<?php
/**
 * Create Payment
 * To'lov qabul qilish
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
$customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
$package_id = isset($_POST['package_id']) ? (int)$_POST['package_id'] : 0;
$customer_package_id = isset($_POST['customer_package_id']) ? (int)$_POST['customer_package_id'] : null;
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$payment_method = isset($_POST['payment_method']) ? clean_input($_POST['payment_method']) : '';
$notes = isset($_POST['notes']) ? clean_input($_POST['notes']) : null;

// Validatsiya
$errors = [];

if ($customer_id <= 0) {
    $errors[] = 'Mijoz tanlanishi shart';
}

if ($package_id <= 0) {
    $errors[] = 'Paket tanlanishi shart';
}

if ($amount <= 0) {
    $errors[] = 'To\'lov summasi 0 dan katta bo\'lishi kerak';
}

$allowed_methods = ['naqd', 'karta', 'click', 'payme'];
if (!in_array($payment_method, $allowed_methods)) {
    $errors[] = 'To\'lov turi noto\'g\'ri. Ruxsat etilgan: ' . implode(', ', $allowed_methods);
}

if (!empty($errors)) {
    send_error(implode(', ', $errors));
}

try {
    // Mijoz mavjudligini tekshirish
    $customer_check = $conn->prepare("
        SELECT id, full_name 
        FROM customers 
        WHERE id = ? AND is_active = 1
        LIMIT 1
    ");
    $customer_check->bind_param("i", $customer_id);
    $customer_check->execute();
    $customer_result = $customer_check->get_result();
    
    if ($customer_result->num_rows === 0) {
        send_error('Mijoz topilmadi yoki faol emas', 404);
    }
    
    $customer = $customer_result->fetch_assoc();
    
    // Paket mavjudligini tekshirish
    $package_check = $conn->prepare("
        SELECT id, name, price 
        FROM packages 
        WHERE id = ?
        LIMIT 1
    ");
    $package_check->bind_param("i", $package_id);
    $package_check->execute();
    $package_result = $package_check->get_result();
    
    if ($package_result->num_rows === 0) {
        send_error('Paket topilmadi', 404);
    }
    
    $package = $package_result->fetch_assoc();
    
    // Customer package tekshirish (agar berilgan bo'lsa)
    if ($customer_package_id !== null) {
        $cp_check = $conn->prepare("
            SELECT id 
            FROM customer_packages 
            WHERE id = ? AND customer_id = ? AND package_id = ?
            LIMIT 1
        ");
        $cp_check->bind_param("iii", $customer_package_id, $customer_id, $package_id);
        $cp_check->execute();
        
        if ($cp_check->get_result()->num_rows === 0) {
            send_error('Mijoz paket ma\'lumotlari noto\'g\'ri');
        }
    }
    
    // To'lovni qabul qilish
    $stmt = $conn->prepare("
        INSERT INTO payments 
        (customer_id, package_id, customer_package_id, amount, payment_method, received_by, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $received_by = $_SESSION['user_id'];
    
    $stmt->bind_param(
        "iiidsss",
        $customer_id,
        $package_id,
        $customer_package_id,
        $amount,
        $payment_method,
        $received_by,
        $notes
    );
    
    if ($stmt->execute()) {
        $payment_id = $conn->insert_id;
        
        // To'lov ma'lumotlarini olish
        $get_stmt = $conn->prepare("
            SELECT 
                p.*,
                c.full_name as customer_name,
                c.phone as customer_phone,
                pkg.name as package_name,
                pkg.ads_count as package_ads_count,
                u.full_name as received_by_name
            FROM payments p
            JOIN customers c ON p.customer_id = c.id
            JOIN packages pkg ON p.package_id = pkg.id
            LEFT JOIN users u ON p.received_by = u.id
            WHERE p.id = ?
        ");
        $get_stmt->bind_param("i", $payment_id);
        $get_stmt->execute();
        $result = $get_stmt->get_result();
        $payment = $result->fetch_assoc();
        
        // Ma'lumotlarni formatlash
        $payment['payment_date'] = format_datetime($payment['payment_date']);
        $payment['customer_phone'] = format_phone($payment['customer_phone']);
        $payment['amount_formatted'] = format_money($payment['amount']);
        
        send_success($payment, "To'lov muvaffaqiyatli qabul qilindi: " . format_money($amount));
    } else {
        throw new Exception('To\'lov qabul qilishda xatolik');
    }
    
} catch (Exception $e) {
    error_log("Create payment error: " . $e->getMessage());
    send_error('To\'lov qabul qilishda xatolik yuz berdi');
}

$conn->close();
?>