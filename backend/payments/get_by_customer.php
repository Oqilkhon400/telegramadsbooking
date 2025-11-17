<?php
/**
 * Get Payments by Customer
 * Bitta mijoz to'lovlarini olish
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
$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

if ($customer_id <= 0) {
    send_error('Noto\'g\'ri mijoz ID');
}

try {
    // Mijoz mavjudligini tekshirish
    $customer_check = $conn->prepare("
        SELECT id, full_name, phone 
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
    $customer['phone'] = format_phone($customer['phone']);
    
    // Mijoz to'lovlarini olish
    $stmt = $conn->prepare("
        SELECT 
            p.*,
            pkg.name as package_name,
            pkg.ads_count as package_ads_count,
            u.full_name as received_by_name
        FROM payments p
        JOIN packages pkg ON p.package_id = pkg.id
        LEFT JOIN users u ON p.received_by = u.id
        WHERE p.customer_id = ?
        ORDER BY p.payment_date DESC
    ");
    
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $payments = [];
    $total_amount = 0;
    $payment_methods_stats = [];
    
    while ($row = $result->fetch_assoc()) {
        // Ma'lumotlarni formatlash
        $row['payment_date'] = format_datetime($row['payment_date']);
        $row['amount_formatted'] = format_money($row['amount']);
        
        $total_amount += $row['amount'];
        
        // To'lov turi bo'yicha statistika
        if (!isset($payment_methods_stats[$row['payment_method']])) {
            $payment_methods_stats[$row['payment_method']] = [
                'count' => 0,
                'total' => 0
            ];
        }
        $payment_methods_stats[$row['payment_method']]['count']++;
        $payment_methods_stats[$row['payment_method']]['total'] += $row['amount'];
        
        $payments[] = $row;
    }
    
    // Payment methods statistikasini formatlash
    $formatted_methods = [];
    foreach ($payment_methods_stats as $method => $stats) {
        $formatted_methods[] = [
            'method' => $method,
            'count' => $stats['count'],
            'total' => format_money($stats['total'])
        ];
    }
    
    // Oxirgi to'lov
    $last_payment = !empty($payments) ? $payments[0] : null;
    
    send_success([
        'customer' => $customer,
        'payments' => $payments,
        'statistics' => [
            'total_payments' => count($payments),
            'total_amount' => format_money($total_amount),
            'avg_payment' => count($payments) > 0 ? format_money($total_amount / count($payments)) : format_money(0),
            'payment_methods' => $formatted_methods,
            'last_payment' => $last_payment
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get customer payments error: " . $e->getMessage());
    send_error('Mijoz to\'lovlarini olishda xatolik yuz berdi');
}

$conn->close();
?>