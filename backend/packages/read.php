<?php
/**
 * Read Packages
 * Paketlar ro'yxatini olish
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
$status = isset($_GET['status']) ? clean_input($_GET['status']) : 'all'; // all, active, inactive

try {
    // WHERE condition
    $where_sql = '';
    if ($status === 'active') {
        $where_sql = 'WHERE is_active = 1';
    } elseif ($status === 'inactive') {
        $where_sql = 'WHERE is_active = 0';
    }
    
    // Paketlarni olish
    $sql = "
        SELECT 
            p.*,
            COUNT(DISTINCT cp.id) as total_sold,
            COUNT(DISTINCT CASE WHEN cp.status = 'active' THEN cp.id END) as active_sold,
            SUM(CASE WHEN cp.status = 'completed' THEN 1 ELSE 0 END) as completed_sold
        FROM packages p
        LEFT JOIN customer_packages cp ON p.id = cp.package_id
        $where_sql
        GROUP BY p.id
        ORDER BY p.ads_count ASC, p.price ASC
    ";
    
    $result = $conn->query($sql);
    
    $packages = [];
    while ($row = $result->fetch_assoc()) {
        // Ma'lumotlarni formatlash
        $row['price_formatted'] = format_money($row['price']);
        $row['created_at'] = format_datetime($row['created_at']);
        $row['is_active'] = (bool)$row['is_active'];
        $row['total_sold'] = (int)$row['total_sold'];
        $row['active_sold'] = (int)$row['active_sold'];
        $row['completed_sold'] = (int)$row['completed_sold'];
        
        $packages[] = $row;
    }
    
    send_success([
        'packages' => $packages,
        'total_count' => count($packages)
    ]);
    
} catch (Exception $e) {
    error_log("Read packages error: " . $e->getMessage());
    send_error('Paketlarni olishda xatolik yuz berdi');
}

$conn->close();
?>