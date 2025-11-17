<?php
/**
 * Revenue Statistics
 * Daromad tahlili
 */

require_once '../../config/database.php';
require_once '../../config/settings.php';

// Session tekshirish
if (!is_logged_in()) {
    send_error('Tizimga kirish kerak', 401);
}

// Faqat superadmin
if (!is_superadmin()) {
    send_error('Faqat superadmin ko\'ra oladi', 403);
}

// Faqat GET metodini qabul qilish
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Faqat GET metodi qabul qilinadi', 405);
}

// Parametrlar
$period = isset($_GET['period']) ? clean_input($_GET['period']) : 'month'; // day, week, month, year, custom
$date_from = isset($_GET['date_from']) ? clean_input($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? clean_input($_GET['date_to']) : '';

try {
    // Sana oralig'ini aniqlash
    $today = date('Y-m-d');
    
    switch ($period) {
        case 'day':
            $date_from = $today;
            $date_to = $today;
            break;
        case 'week':
            $date_from = date('Y-m-d', strtotime('monday this week'));
            $date_to = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'month':
            $date_from = date('Y-m-01');
            $date_to = date('Y-m-t');
            break;
        case 'year':
            $date_from = date('Y-01-01');
            $date_to = date('Y-12-31');
            break;
        case 'custom':
            // date_from va date_to parametrlardan olinadi
            if (empty($date_from) || empty($date_to)) {
                send_error('Custom period uchun date_from va date_to kiritilishi shart');
            }
            break;
        default:
            $date_from = date('Y-m-01');
            $date_to = date('Y-m-t');
    }
    
    // ============= UMUMIY DAROMAD =============
    
    $revenue_query = "
        SELECT 
            COUNT(*) as payment_count,
            SUM(amount) as total_revenue,
            AVG(amount) as avg_payment,
            MIN(amount) as min_payment,
            MAX(amount) as max_payment
        FROM payments
        WHERE DATE(payment_date) BETWEEN ? AND ?
    ";
    
    $stmt = $conn->prepare($revenue_query);
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $revenue_stats = $stmt->get_result()->fetch_assoc();
    
    
    // ============= TO'LOV TURLARI BO'YICHA =============
    
    $methods_query = "
        SELECT 
            payment_method,
            COUNT(*) as count,
            SUM(amount) as total,
            AVG(amount) as average
        FROM payments
        WHERE DATE(payment_date) BETWEEN ? AND ?
        GROUP BY payment_method
        ORDER BY total DESC
    ";
    
    $stmt = $conn->prepare($methods_query);
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $methods_result = $stmt->get_result();
    
    $by_method = [];
    while ($method = $methods_result->fetch_assoc()) {
        $by_method[] = [
            'method' => $method['payment_method'],
            'count' => (int)$method['count'],
            'total' => format_money($method['total']),
            'average' => format_money($method['average']),
            'percentage' => $revenue_stats['total_revenue'] > 0 
                ? round(($method['total'] / $revenue_stats['total_revenue']) * 100, 1) 
                : 0
        ];
    }
    
    
    // ============= PAKETLAR BO'YICHA =============
    
    $packages_query = "
        SELECT 
            pkg.name as package_name,
            pkg.price,
            COUNT(*) as sold_count,
            SUM(p.amount) as total_revenue
        FROM payments p
        JOIN packages pkg ON p.package_id = pkg.id
        WHERE DATE(p.payment_date) BETWEEN ? AND ?
        GROUP BY pkg.id
        ORDER BY total_revenue DESC
    ";
    
    $stmt = $conn->prepare($packages_query);
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $packages_result = $stmt->get_result();
    
    $by_package = [];
    while ($package = $packages_result->fetch_assoc()) {
        $by_package[] = [
            'package_name' => $package['package_name'],
            'price' => format_money($package['price']),
            'sold_count' => (int)$package['sold_count'],
            'total_revenue' => format_money($package['total_revenue']),
            'percentage' => $revenue_stats['total_revenue'] > 0 
                ? round(($package['total_revenue'] / $revenue_stats['total_revenue']) * 100, 1) 
                : 0
        ];
    }
    
    
    // ============= KUNLIK TREND =============
    
    $daily_query = "
        SELECT 
            DATE(payment_date) as date,
            COUNT(*) as count,
            SUM(amount) as total
        FROM payments
        WHERE DATE(payment_date) BETWEEN ? AND ?
        GROUP BY DATE(payment_date)
        ORDER BY date ASC
    ";
    
    $stmt = $conn->prepare($daily_query);
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $daily_result = $stmt->get_result();
    
    $daily_trend = [];
    while ($day = $daily_result->fetch_assoc()) {
        $daily_trend[] = [
            'date' => format_date($day['date']),
            'count' => (int)$day['count'],
            'total' => (float)$day['total'],
            'total_formatted' => format_money($day['total'])
        ];
    }
    
    
    // ============= TOP MIJOZLAR =============
    
    $top_customers_query = "
        SELECT 
            c.id,
            c.full_name,
            c.phone,
            COUNT(p.id) as payment_count,
            SUM(p.amount) as total_paid
        FROM customers c
        JOIN payments p ON c.id = p.customer_id
        WHERE DATE(p.payment_date) BETWEEN ? AND ?
        GROUP BY c.id
        ORDER BY total_paid DESC
        LIMIT 10
    ";
    
    $stmt = $conn->prepare($top_customers_query);
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $top_customers_result = $stmt->get_result();
    
    $top_customers = [];
    while ($customer = $top_customers_result->fetch_assoc()) {
        $top_customers[] = [
            'id' => $customer['id'],
            'name' => $customer['full_name'],
            'phone' => format_phone($customer['phone']),
            'payment_count' => (int)$customer['payment_count'],
            'total_paid' => format_money($customer['total_paid'])
        ];
    }
    
    
    // ============= JAVOB YUBORISH =============
    
    send_success([
        'period' => [
            'type' => $period,
            'from' => format_date($date_from),
            'to' => format_date($date_to),
            'days' => (strtotime($date_to) - strtotime($date_from)) / 86400 + 1
        ],
        'summary' => [
            'payment_count' => (int)$revenue_stats['payment_count'],
            'total_revenue' => format_money($revenue_stats['total_revenue'] ?? 0),
            'avg_payment' => format_money($revenue_stats['avg_payment'] ?? 0),
            'min_payment' => format_money($revenue_stats['min_payment'] ?? 0),
            'max_payment' => format_money($revenue_stats['max_payment'] ?? 0)
        ],
        'by_method' => $by_method,
        'by_package' => $by_package,
        'daily_trend' => $daily_trend,
        'top_customers' => $top_customers
    ]);
    
} catch (Exception $e) {
    error_log("Revenue statistics error: " . $e->getMessage());
    send_error('Daromad statistikasini olishda xatolik yuz berdi');
}

$conn->close();
?>