<?php
/**
 * Read Customers
 * Mijozlar ro'yxatini olish
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
$status = isset($_GET['status']) ? clean_input($_GET['status']) : 'all'; // all, active, inactive

$offset = ($page - 1) * $limit;

try {
    // WHERE condition yaratish
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $where_conditions[] = "(c.full_name LIKE ? OR c.phone LIKE ? OR c.telegram_username LIKE ?)";
        $search_param = "%$search%";
        $params[] = &$search_param;
        $params[] = &$search_param;
        $params[] = &$search_param;
        $types .= 'sss';
    }
    
    if ($status !== 'all') {
        $is_active = ($status === 'active') ? 1 : 0;
        $where_conditions[] = "c.is_active = ?";
        $params[] = &$is_active;
        $types .= 'i';
    }
    
    $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Umumiy mijozlar sonini olish
    $count_sql = "SELECT COUNT(*) as total FROM customers c $where_sql";
    
    if (!empty($params)) {
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param($types, ...$params);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
    } else {
        $count_result = $conn->query($count_sql);
    }
    
    $total_customers = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_customers / $limit);
    
    // Mijozlar ro'yxatini olish
    $sql = "
        SELECT 
            c.*,
            u.full_name as created_by_name,
            COUNT(DISTINCT cp.id) as total_packages,
            SUM(CASE WHEN cp.status = 'active' THEN 1 ELSE 0 END) as active_packages,
            SUM(CASE WHEN cp.status = 'active' THEN cp.remaining_ads ELSE 0 END) as total_remaining_ads
        FROM customers c
        LEFT JOIN users u ON c.created_by = u.id
        LEFT JOIN customer_packages cp ON c.id = cp.customer_id
        $where_sql
        GROUP BY c.id
        ORDER BY c.created_at DESC
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
    
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        // Ma'lumotlarni formatlash
        $row['phone'] = format_phone($row['phone']);
        $row['created_at'] = format_datetime($row['created_at']);
        $row['is_active'] = (bool)$row['is_active'];
        $row['total_packages'] = (int)$row['total_packages'];
        $row['active_packages'] = (int)$row['active_packages'];
        $row['total_remaining_ads'] = (int)$row['total_remaining_ads'];
        
        $customers[] = $row;
    }
    
    send_success([
        'customers' => $customers,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_items' => $total_customers,
            'items_per_page' => $limit,
            'has_next' => $page < $total_pages,
            'has_prev' => $page > 1
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Read customers error: " . $e->getMessage());
    send_error('Mijozlarni olishda xatolik yuz berdi');
}

$conn->close();
?>