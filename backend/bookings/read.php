<?php
/**
 * Read Bookings
 * Bookinglar ro'yxatini olish
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
$status = isset($_GET['status']) ? clean_input($_GET['status']) : 'all'; // all, scheduled, published, cancelled
$date_from = isset($_GET['date_from']) ? clean_input($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? clean_input($_GET['date_to']) : '';
$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

$offset = ($page - 1) * $limit;

try {
    // WHERE condition yaratish
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $where_conditions[] = "(c.full_name LIKE ? OR c.phone LIKE ? OR b.ad_description LIKE ?)";
        $search_param = "%$search%";
        $params[] = &$search_param;
        $params[] = &$search_param;
        $params[] = &$search_param;
        $types .= 'sss';
    }
    
    if ($status !== 'all') {
        $where_conditions[] = "b.status = ?";
        $params[] = &$status;
        $types .= 's';
    }
    
    if (!empty($date_from)) {
        $where_conditions[] = "ts.slot_date >= ?";
        $params[] = &$date_from;
        $types .= 's';
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "ts.slot_date <= ?";
        $params[] = &$date_to;
        $types .= 's';
    }
    
    if ($customer_id > 0) {
        $where_conditions[] = "b.customer_id = ?";
        $params[] = &$customer_id;
        $types .= 'i';
    }
    
    $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Umumiy bookinglar sonini olish
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN time_slots ts ON b.time_slot_id = ts.id
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
    
    $total_bookings = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_bookings / $limit);
    
    // Bookinglar ro'yxatini olish
    $sql = "
        SELECT 
            b.*,
            c.full_name as customer_name,
            c.phone as customer_phone,
            cp.total_ads,
            cp.used_ads,
            cp.remaining_ads,
            p.name as package_name,
            ts.slot_date,
            ts.slot_time,
            ts.status as slot_status,
            u.full_name as booked_by_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN customer_packages cp ON b.customer_package_id = cp.id
        JOIN packages p ON cp.package_id = p.id
        JOIN time_slots ts ON b.time_slot_id = ts.id
        LEFT JOIN users u ON b.booked_by = u.id
        $where_sql
        ORDER BY ts.slot_date DESC, ts.slot_time DESC
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
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        // Ma'lumotlarni formatlash
        $row['booking_date'] = format_datetime($row['booking_date']);
        $row['slot_date'] = format_date($row['slot_date']);
        $row['slot_time'] = substr($row['slot_time'], 0, 5); // HH:MM
        $row['customer_phone'] = format_phone($row['customer_phone']);
        $row['notification_sent'] = (bool)$row['notification_sent'];
        
        if ($row['published_at']) {
            $row['published_at'] = format_datetime($row['published_at']);
        }
        
        // Paket qoldig'i
        $row['package_progress'] = [
            'used' => (int)$row['used_ads'],
            'total' => (int)$row['total_ads'],
            'remaining' => (int)$row['remaining_ads']
        ];
        
        $bookings[] = $row;
    }
    
    send_success([
        'bookings' => $bookings,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_items' => $total_bookings,
            'items_per_page' => $limit,
            'has_next' => $page < $total_pages,
            'has_prev' => $page > 1
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Read bookings error: " . $e->getMessage());
    send_error('Bookinglarni olishda xatolik yuz berdi');
}

$conn->close();
?>