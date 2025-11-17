<?php
/**
 * Delete Booking
 * Booking o'chirish
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
$booking_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($booking_id <= 0) {
    send_error('Noto\'g\'ri booking ID');
}

try {
    // Booking mavjudligini tekshirish
    $check_stmt = $conn->prepare("
        SELECT 
            b.id,
            b.status,
            c.full_name as customer_name,
            ts.slot_date,
            ts.slot_time
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN time_slots ts ON b.time_slot_id = ts.id
        WHERE b.id = ?
        LIMIT 1
    ");
    
    $check_stmt->bind_param("i", $booking_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        send_error('Booking topilmadi', 404);
    }
    
    $booking = $result->fetch_assoc();
    
    // Published bookingni o'chirib bo'lmaydi
    if ($booking['status'] === 'published') {
        send_error('Published booking o\'chirib bo\'lmaydi. O\'rniga cancelled qiling.');
    }
    
    // Booking o'chirish (Trigger avtomatik paket qoldiqni qaytaradi va time slot ni available qiladi)
    $delete_stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $delete_stmt->bind_param("i", $booking_id);
    
    if ($delete_stmt->execute()) {
        send_success([
            'deleted_id' => $booking_id,
            'customer_name' => $booking['customer_name'],
            'slot_date' => format_date($booking['slot_date']),
            'slot_time' => substr($booking['slot_time'], 0, 5)
        ], 'Booking muvaffaqiyatli o\'chirildi');
    } else {
        throw new Exception('Booking o\'chirishda xatolik');
    }
    
} catch (Exception $e) {
    error_log("Delete booking error: " . $e->getMessage());
    send_error('Booking o\'chirishda xatolik yuz berdi');
}

$conn->close();
?>