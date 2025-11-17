<?php
/**
 * Read Payments
 * To'lovlar tarixini olish
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

// Parametrlar
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : ITEMS_PER_PAGE;
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$payment_method = isset($_GET['payment_method']) ? clean_input($_GET['payment_method']) : 'all';
$date_from = isset($_GET['date_from']) ? clean_input($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? clean_input($_GET['date_to']) : '';

$offset = ($page - 1) * $limit;

try {
    // WHERE condition yaratish
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $where_conditions[] = "(c.full_name LIKE ? OR c.phone LIKE ? OR pkg.name LIKE ?)";
        $search_param = "%$search%";
        $params[] = &$search_param;
        $params[] = &$search_param;
        $params[] = &$search_param;
        $types .= 'sss';
    }
    
    if ($payment_method !== 'all') {
        $where_conditions[] = "p.payment_method = ?";
        $params[] = &$payment_method;
        $types .= 's';
    }
    
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(p.payment_date) >= ?";
        $params[] = &$date_from;
        $types .= 's';
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(p.payment_date) <= ?";
        $params[] = &$date_to;
        $types .= 's';
    }
    
    $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Umumiy to'lovlar sonini olish
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM payments p
        JOIN customers c ON p.customer_id = c.id
        JOIN packages pkg ON p.package_id = pkg.id
        $where_sql
    ";
    
    if (!empty($params)) {
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param($types, ...$params);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
    } else {
        $count_result = $conn->query($count_sql);
    }
    
    $total_payments = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_payments / $limit);
    
    // To'lovlar ro'yxatini olish
    $sql = "
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
        $where_sql
        ORDER BY p.payment_date DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = &$limit;
    $params[] = &$offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $payments = [];
    $total_amount = 0;
    
    while ($row = $result->fetch_assoc()) {
        // Ma'lumotlarni formatlash
        $row['payment_date'] = format_datetime($row['payment_date']);
        $row['customer_phone'] = format_phone($row['customer_phone']);
        $row['amount_formatted'] = format_money($row['amount']);
        
        $total_amount += $row['amount'];
        $payments[] = $row;
    }
    
    // Umumiy statistika (filter bo'yicha)
    $stats_sql = "
        SELECT 
            COUNT(*) as total_count,
            SUM(amount) as total_amount,
            AVG(amount) as avg_amount
        FROM payments p
        JOIN customers c ON p.customer_id = c.id
        JOIN packages pkg ON p.package_id = pkg.id
        $where_sql
    ";
    
    if (!empty($where_conditions)) {
        // Remove last 2 params (limit, offset)
        $stats_params = array_slice($params, 0, -2);
        $stats_types = substr($types, 0, -2);
        
        $stats_stmt = $conn->prepare($stats_sql);
        if (!empty($stats_params)) {
            $stats_stmt->bind_param($stats_types, ...$stats_params);
        }
        $stats_stmt->execute();
        $stats_result = $stats_stmt->get_result();
    } else {
        $stats_result = $conn->query($stats_sql);
    }
    
    $stats = $stats_result->fetch_assoc();
    
    send_success([
        'payments' => $payments,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_items' => $total_payments,
            'items_per_page' => $limit,
            'has_next' => $page < $total_pages,
            'has_prev' => $page > 1
        ],
        'statistics' => [
            'total_count' => (int)$stats['total_count'],
            'total_amount' => format_money($stats['total_amount'] ?? 0),
            'avg_amount' => format_money($stats['avg_amount'] ?? 0),
            'current_page_amount' => format_money($total_amount)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Read payments error: " . $e->getMessage());
    send_error('To\'lovlarni olishda xatolik yuz berdi');
}

$conn->close();
?>