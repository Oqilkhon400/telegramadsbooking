<?php
/**
 * Dashboard Statistics
 * Asosiy dashboard statistika
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

try {
    // Bugungi sana
    $today = date('Y-m-d');
    $this_month = date('Y-m');
    
    // ============= MIJOZLAR STATISTIKASI =============
    
    // Jami mijozlar
    $customers_query = "SELECT COUNT(*) as total, SUM(is_active) as active FROM customers";
    $customers_result = $conn->query($customers_query);
    $customers_stats = $customers_result->fetch_assoc();
    
    // Bugun qo'shilgan mijozlar
    $today_customers = $conn->query("
        SELECT COUNT(*) as count 
        FROM customers 
        WHERE DATE(created_at) = '$today'
    ")->fetch_assoc()['count'];
    
    // Shu oy qo'shilgan mijozlar
    $month_customers = $conn->query("
        SELECT COUNT(*) as count 
        FROM customers 
        WHERE DATE_FORMAT(created_at, '%Y-%m') = '$this_month'
    ")->fetch_assoc()['count'];
    
    
    // ============= PAKETLAR STATISTIKASI =============
    
    // Jami sotilgan paketlar
    $packages_query = "
        SELECT 
            COUNT(*) as total_sold,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(total_ads) as total_ads_sold,
            SUM(used_ads) as total_ads_used,
            SUM(remaining_ads) as total_ads_remaining
        FROM customer_packages
    ";
    $packages_result = $conn->query($packages_query);
    $packages_stats = $packages_result->fetch_assoc();
    
    
    // ============= TO'LOVLAR STATISTIKASI =============
    
    // Jami to'lovlar
    $payments_query = "
        SELECT 
            COUNT(*) as total_count,
            SUM(amount) as total_amount
        FROM payments
    ";
    $payments_result = $conn->query($payments_query);
    $payments_stats = $payments_result->fetch_assoc();
    
    // Bugungi to'lovlar
    $today_payments = $conn->query("
        SELECT 
            COUNT(*) as count,
            SUM(amount) as amount
        FROM payments 
        WHERE DATE(payment_date) = '$today'
    ")->fetch_assoc();
    
    // Shu oy to'lovlar
    $month_payments = $conn->query("
        SELECT 
            COUNT(*) as count,
            SUM(amount) as amount
        FROM payments 
        WHERE DATE_FORMAT(payment_date, '%Y-%m') = '$this_month'
    ")->fetch_assoc();
    
    // To'lov turlari bo'yicha
    $payment_methods = $conn->query("
        SELECT 
            payment_method,
            COUNT(*) as count,
            SUM(amount) as total
        FROM payments
        GROUP BY payment_method
    ");
    
    $methods_stats = [];
    while ($method = $payment_methods->fetch_assoc()) {
        $methods_stats[] = [
            'method' => $method['payment_method'],
            'count' => (int)$method['count'],
            'total' => format_money($method['total'])
        ];
    }
    
    
    // ============= BOOKINGLAR STATISTIKASI =============
    
    // Jami bookinglar
    $bookings_query = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM bookings
    ";
    $bookings_result = $conn->query($bookings_query);
    $bookings_stats = $bookings_result->fetch_assoc();
    
    // Bugungi bookinglar
    $today_bookings = $conn->query("
        SELECT COUNT(*) as count 
        FROM bookings b
        JOIN time_slots ts ON b.time_slot_id = ts.id
        WHERE ts.slot_date = '$today'
    ")->fetch_assoc()['count'];
    
    // Ertangi bookinglar
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $tomorrow_bookings = $conn->query("
        SELECT COUNT(*) as count 
        FROM bookings b
        JOIN time_slots ts ON b.time_slot_id = ts.id
        WHERE ts.slot_date = '$tomorrow' AND b.status = 'scheduled'
    ")->fetch_assoc()['count'];
    
    
    // ============= OXIRGI FAOLIYATLAR =============
    
    // Oxirgi 5 ta mijoz
    $recent_customers = $conn->query("
        SELECT id, full_name, phone, created_at
        FROM customers
        ORDER BY created_at DESC
        LIMIT 5
    ");
    
    $recent_customers_data = [];
    while ($customer = $recent_customers->fetch_assoc()) {
        $recent_customers_data[] = [
            'id' => $customer['id'],
            'name' => $customer['full_name'],
            'phone' => format_phone($customer['phone']),
            'created_at' => format_datetime($customer['created_at'])
        ];
    }
    
    // Oxirgi 5 ta to'lov
    $recent_payments = $conn->query("
        SELECT 
            p.id,
            p.amount,
            p.payment_method,
            p.payment_date,
            c.full_name as customer_name
        FROM payments p
        JOIN customers c ON p.customer_id = c.id
        ORDER BY p.payment_date DESC
        LIMIT 5
    ");
    
    $recent_payments_data = [];
    while ($payment = $recent_payments->fetch_assoc()) {
        $recent_payments_data[] = [
            'id' => $payment['id'],
            'customer_name' => $payment['customer_name'],
            'amount' => format_money($payment['amount']),
            'method' => $payment['payment_method'],
            'date' => format_datetime($payment['payment_date'])
        ];
    }
    
    // Yaqin bookinglar (keyingi 3 kun)
    $upcoming_date = date('Y-m-d', strtotime('+3 days'));
    $upcoming_bookings = $conn->query("
        SELECT 
            b.id,
            c.full_name as customer_name,
            ts.slot_date,
            ts.slot_time,
            b.ad_description
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN time_slots ts ON b.time_slot_id = ts.id
        WHERE ts.slot_date BETWEEN '$today' AND '$upcoming_date'
        AND b.status = 'scheduled'
        ORDER BY ts.slot_date ASC, ts.slot_time ASC
        LIMIT 10
    ");
    
    $upcoming_bookings_data = [];
    while ($booking = $upcoming_bookings->fetch_assoc()) {
        $upcoming_bookings_data[] = [
            'id' => $booking['id'],
            'customer_name' => $booking['customer_name'],
            'date' => format_date($booking['slot_date']),
            'time' => substr($booking['slot_time'], 0, 5),
            'description' => $booking['ad_description']
        ];
    }
    
    
    // ============= JAVOB YUBORISH =============
    
    send_success([
        'customers' => [
            'total' => (int)$customers_stats['total'],
            'active' => (int)$customers_stats['active'],
            'inactive' => (int)$customers_stats['total'] - (int)$customers_stats['active'],
            'today' => (int)$today_customers,
            'this_month' => (int)$month_customers,
            'recent' => $recent_customers_data
        ],
        'packages' => [
            'total_sold' => (int)$packages_stats['total_sold'],
            'active' => (int)$packages_stats['active'],
            'completed' => (int)$packages_stats['completed'],
            'total_ads_sold' => (int)$packages_stats['total_ads_sold'],
            'total_ads_used' => (int)$packages_stats['total_ads_used'],
            'total_ads_remaining' => (int)$packages_stats['total_ads_remaining'],
            'usage_percentage' => $packages_stats['total_ads_sold'] > 0 
                ? round(($packages_stats['total_ads_used'] / $packages_stats['total_ads_sold']) * 100, 1) 
                : 0
        ],
        'payments' => [
            'total_count' => (int)$payments_stats['total_count'],
            'total_amount' => format_money($payments_stats['total_amount'] ?? 0),
            'today_count' => (int)$today_payments['count'],
            'today_amount' => format_money($today_payments['amount'] ?? 0),
            'month_count' => (int)$month_payments['count'],
            'month_amount' => format_money($month_payments['amount'] ?? 0),
            'by_method' => $methods_stats,
            'recent' => $recent_payments_data
        ],
        'bookings' => [
            'total' => (int)$bookings_stats['total'],
            'scheduled' => (int)$bookings_stats['scheduled'],
            'published' => (int)$bookings_stats['published'],
            'cancelled' => (int)$bookings_stats['cancelled'],
            'today' => (int)$today_bookings,
            'tomorrow' => (int)$tomorrow_bookings,
            'upcoming' => $upcoming_bookings_data
        ],
        'summary' => [
            'date' => format_date($today),
            'month' => date('F Y')
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Dashboard statistics error: " . $e->getMessage());
    send_error('Statistika olishda xatolik yuz berdi');
}

$conn->close();
?>