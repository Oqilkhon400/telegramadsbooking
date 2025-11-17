<?php
/**
 * Delete Customer
 * Mijozni o'chirish
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

// ID olish
$customer_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($customer_id <= 0) {
    send_error('Noto\'g\'ri mijoz ID');
}

try {
    // Mijoz mavjudligini tekshirish
    $check_stmt = $conn->prepare("
        SELECT id, full_name 
        FROM customers 
        WHERE id = ? 
        LIMIT 1
    ");
    $check_stmt->bind_param("i", $customer_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        send_error('Mijoz topilmadi', 404);
    }
    
    $customer = $result->fetch_assoc();
    
    // Aktiv bookinglar borligini tekshirish
    $booking_check = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM bookings 
        WHERE customer_id = ? 
        AND status = 'scheduled'
    ");
    $booking_check->bind_param("i", $customer_id);
    $booking_check->execute();
    $booking_result = $booking_check->get_result();
    $active_bookings = $booking_result->fetch_assoc()['count'];
    
    if ($active_bookings > 0) {
        send_error("Bu mijozning $active_bookings ta aktiv bronlari bor. Avval ularni o'chiring.");
    }
    
    // Mijozni o'chirish (CASCADE bilan bog'liq ma'lumotlar ham o'chadi)
    $delete_stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $delete_stmt->bind_param("i", $customer_id);
    
    if ($delete_stmt->execute()) {
        send_success([
            'deleted_id' => $customer_id,
            'customer_name' => $customer['full_name']
        ], 'Mijoz muvaffaqiyatli o\'chirildi');
    } else {
        throw new Exception('Mijoz o\'chirishda xatolik');
    }
    
} catch (Exception $e) {
    error_log("Delete customer error: " . $e->getMessage());
    send_error('Mijoz o\'chirishda xatolik yuz berdi');
}

$conn->close();
?>