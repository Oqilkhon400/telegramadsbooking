<?php
/**
 * Assign Package to Customer
 * Mijozga paket biriktirish
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
$customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
$package_id = isset($_POST['package_id']) ? (int)$_POST['package_id'] : 0;
$notes = isset($_POST['notes']) ? clean_input($_POST['notes']) : null;

// Validatsiya
$errors = [];

if ($customer_id <= 0) {
    $errors[] = 'Mijoz tanlanishi shart';
}

if ($package_id <= 0) {
    $errors[] = 'Paket tanlanishi shart';
}

if (!empty($errors)) {
    send_error(implode(', ', $errors));
}

try {
    // Mijoz mavjudligini tekshirish
    $customer_check = $conn->prepare("
        SELECT id, full_name, is_active 
        FROM customers 
        WHERE id = ? 
        LIMIT 1
    ");
    $customer_check->bind_param("i", $customer_id);
    $customer_check->execute();
    $customer_result = $customer_check->get_result();
    
    if ($customer_result->num_rows === 0) {
        send_error('Mijoz topilmadi', 404);
    }
    
    $customer = $customer_result->fetch_assoc();
    
    // is_active tekshiruvi olib tashlandi - paket biriktirishda to'siq bo'lmasin
    
    // Paket mavjudligini tekshirish
    $package_check = $conn->prepare("
        SELECT id, name, ads_count, price, is_active 
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
    
    // is_active tekshiruvi olib tashlandi - yangi paketlar uchun muammo
    
    // Paketni mijozga biriktirish
    $stmt = $conn->prepare("
        INSERT INTO customer_packages 
        (customer_id, package_id, total_ads, used_ads, remaining_ads, notes) 
        VALUES (?, ?, ?, 0, ?, ?)
    ");
    
    $stmt->bind_param(
        "iiiis",
        $customer_id,
        $package_id,
        $package['ads_count'],
        $package['ads_count'],
        $notes
    );
    
    if ($stmt->execute()) {
        $customer_package_id = $conn->insert_id;
        
        // Biriktirilgan paket ma'lumotlarini olish
        $get_stmt = $conn->prepare("
            SELECT 
                cp.*,
                c.full_name as customer_name,
                c.phone as customer_phone,
                p.name as package_name,
                p.ads_count as package_ads_count,
                p.price as package_price
            FROM customer_packages cp
            JOIN customers c ON cp.customer_id = c.id
            JOIN packages p ON cp.package_id = p.id
            WHERE cp.id = ?
        ");
        $get_stmt->bind_param("i", $customer_package_id);
        $get_stmt->execute();
        $result = $get_stmt->get_result();
        $customer_package = $result->fetch_assoc();
        
        // Ma'lumotlarni formatlash
        $customer_package['purchase_date'] = format_datetime($customer_package['purchase_date']);
        $customer_package['customer_phone'] = format_phone($customer_package['customer_phone']);
        $customer_package['package_price'] = format_money($customer_package['package_price']);
        
        send_success($customer_package, "Paket \"{$package['name']}\" mijozga muvaffaqiyatli biriktirildi");
    } else {
        // Aniq SQL xatolikni ko'rsatish
        $sql_error = $stmt->error;
        error_log("SQL Error: " . $sql_error);
        send_error('SQL xatolik: ' . $sql_error);
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $error_trace = $e->getTraceAsString();
    error_log("Assign package error: " . $error_message);
    error_log("Trace: " . $error_trace);
    
    // Aniq xatolikni qaytarish
    send_error('Xatolik: ' . $error_message . ' (Line: ' . $e->getLine() . ')');
}

$conn->close();
?>