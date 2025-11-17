<?php
/**
 * Get Customer by ID
 * Bitta mijoz ma'lumotlarini olish
 */

require_once '../../config/database.php';
require_once '../../config/settings.php';

// Session tekshirish
if (!is_logged_in()) {
    send_error('Tizimga kirish kerak', 401);
}

// Faqat GET metodini qabul qilish
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Faqat GET metodi qabul qilinadi', 405);
}

// ID olish
$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($customer_id <= 0) {
    send_error('Noto\'g\'ri mijoz ID');
}

try {
    // Mijoz asosiy ma'lumotlari
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            u.full_name as created_by_name
        FROM customers c
        LEFT JOIN users u ON c.created_by = u.id
        WHERE c.id = ?
        LIMIT 1
    ");
    
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        send_error('Mijoz topilmadi', 404);
    }
    
    $customer = $result->fetch_assoc();
    
    // Paketlar ma'lumotlari
    $packages_stmt = $conn->prepare("
        SELECT 
            cp.*,
            p.name as package_name,
            p.ads_count as package_ads_count,
            p.price as package_price
        FROM customer_packages cp
        JOIN packages p ON cp.package_id = p.id
        WHERE cp.customer_id = ?
        ORDER BY cp.purchase_date DESC
    ");
    
    $packages_stmt->bind_param("i", $customer_id);
    $packages_stmt->execute();
    $packages_result = $packages_stmt->get_result();
    
    $packages = [];
    while ($pkg = $packages_result->fetch_assoc()) {
        $pkg['purchase_date'] = format_datetime($pkg['purchase_date']);
        $pkg['package_price'] = format_money($pkg['package_price']);
        $packages[] = $pkg;
    }
    
    // To'lovlar tarixi
    $payments_stmt = $conn->prepare("
        SELECT 
            pay.*,
            p.name as package_name,
            u.full_name as received_by_name
        FROM payments pay
        LEFT JOIN packages p ON pay.package_id = p.id
        LEFT JOIN users u ON pay.received_by = u.id
        WHERE pay.customer_id = ?
        ORDER BY pay.payment_date DESC
        LIMIT 10
    ");
    
    $payments_stmt->bind_param("i", $customer_id);
    $payments_stmt->execute();
    $payments_result = $payments_stmt->get_result();
    
    $payments = [];
    while ($payment = $payments_result->fetch_assoc()) {
        $payment['payment_date'] = format_datetime($payment['payment_date']);
        $payment['amount'] = format_money($payment['amount']);
        $payments[] = $payment;
    }
    
    // Bookinglar
    $bookings_stmt = $conn->prepare("
        SELECT 
            b.*,
            ts.slot_date,
            ts.slot_time,
            u.full_name as booked_by_name
        FROM bookings b
        JOIN time_slots ts ON b.time_slot_id = ts.id
        LEFT JOIN users u ON b.booked_by = u.id
        WHERE b.customer_id = ?
        ORDER BY ts.slot_date DESC, ts.slot_time DESC
        LIMIT 10
    ");
    
    $bookings_stmt->bind_param("i", $customer_id);
    $bookings_stmt->execute();
    $bookings_result = $bookings_stmt->get_result();
    
    $bookings = [];
    while ($booking = $bookings_result->fetch_assoc()) {
        $booking['slot_date'] = format_date($booking['slot_date']);
        $booking['slot_time'] = substr($booking['slot_time'], 0, 5); // HH:MM
        $booking['booking_date'] = format_datetime($booking['booking_date']);
        $bookings[] = $booking;
    }
    
    // Ma'lumotlarni formatlash
    $customer['phone'] = format_phone($customer['phone']);
    $customer['created_at'] = format_datetime($customer['created_at']);
    $customer['is_active'] = (bool)$customer['is_active'];
    
    // Statistika
    $customer['stats'] = [
        'total_packages' => count($packages),
        'active_packages' => count(array_filter($packages, fn($p) => $p['status'] === 'active')),
        'total_payments' => count($payments),
        'total_bookings' => count($bookings)
    ];
    
    send_success([
        'customer' => $customer,
        'packages' => $packages,
        'payments' => $payments,
        'bookings' => $bookings
    ]);
    
} catch (Exception $e) {
    error_log("Get customer by ID error: " . $e->getMessage());
    send_error('Mijoz ma\'lumotlarini olishda xatolik');
}

$conn->close();
?>