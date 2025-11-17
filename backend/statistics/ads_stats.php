<?php
/**
 * Ads Statistics
 * Reklamalar statistikasi
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
$period = isset($_GET['period']) ? clean_input($_GET['period']) : 'month'; // day, week, month, year
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
            if (empty($date_from) || empty($date_to)) {
                send_error('Custom period uchun date_from va date_to kiritilishi shart');
            }
            break;
        default:
            $date_from = date('Y-m-01');
            $date_to = date('Y-m-t');
    }
    
    
    // ============= UMUMIY STATISTIKA =============
    
    $total_query = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN b.status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
            SUM(CASE WHEN b.status = 'published' THEN 1 ELSE 0 END) as published,
            SUM(CASE WHEN b.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM bookings b
        JOIN time_slots ts ON b.time_slot_id = ts.id
        WHERE ts.slot_date BETWEEN ? AND ?
    ";
    
    $stmt = $conn->prepare($total_query);
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $total_stats = $stmt->get_result()->fetch_assoc();
    
    
    // ============= KUNLIK TREND =============
    
    $daily_query = "
        SELECT 
            ts.slot_date as date,
            COUNT(*) as total,
            SUM(CASE WHEN b.status = 'published' THEN 1 ELSE 0 END) as published,
            SUM(CASE WHEN b.status = 'scheduled' THEN 1 ELSE 0 END) as scheduled
        FROM bookings b
        JOIN time_slots ts ON b.time_slot_id = ts.id
        WHERE ts.slot_date BETWEEN ? AND ?
        GROUP BY ts.slot_date
        ORDER BY ts.slot_date ASC
    ";
    
    $stmt = $conn->prepare($daily_query);
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $daily_result = $stmt->get_result();
    
    $daily_trend = [];
    while ($row = $daily_result->fetch_assoc()) {
        $daily_trend[] = [
            'date' => format_date($row['date']),
            'total' => (int)$row['total'],
            'published' => (int)$row['published'],
            'scheduled' => (int)$row['scheduled']
        ];
    }
    
    
    // ============= SOATLIK TAQSIMOT =============
    
    $hourly_query = "
        SELECT 
            HOUR(ts.slot_time) as hour,
            COUNT(*) as count
        FROM bookings b
        JOIN time_slots ts ON b.time_slot_id = ts.id
        WHERE ts.slot_date BETWEEN ? AND ?
        GROUP BY HOUR(ts.slot_time)
        ORDER BY hour ASC
    ";
    
    $stmt = $conn->prepare($hourly_query);
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $hourly_result = $stmt->get_result();
    
    $hourly_distribution = [];
    while ($row = $hourly_result->fetch_assoc()) {
        $hourly_distribution[] = [
            'hour' => sprintf('%02d:00', $row['hour']),
            'count' => (int)$row['count']
        ];
    }
    
    
    // ============= ENG BAND KUNLAR =============
    
    $busiest_days_query = "
        SELECT 
            ts.slot_date,
            COUNT(*) as booking_count
        FROM bookings b
        JOIN time_slots ts ON b.time_slot_id = ts.id
        WHERE ts.slot_date BETWEEN ? AND ?
        GROUP BY ts.slot_date
        ORDER BY booking_count DESC
        LIMIT 10
    ";
    
    $stmt = $conn->prepare($busiest_days_query);
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $busiest_result = $stmt->get_result();
    
    $busiest_days = [];
    while ($row = $busiest_result->fetch_assoc()) {
        $busiest_days[] = [
            'date' => format_date($row['slot_date']),
            'booking_count' => (int)$row['booking_count']
        ];
    }
    
    
    // ============= PAKETLAR BO'YICHA =============
    
    $packages_query = "
        SELECT 
            p.name as package_name,
            COUNT(DISTINCT b.id) as ads_count,
            COUNT(DISTINCT b.customer_id) as customers_count
        FROM bookings b
        JOIN customer_packages cp ON b.customer_package_id = cp.id
        JOIN packages p ON cp.package_id = p.id
        JOIN time_slots ts ON b.time_slot_id = ts.id
        WHERE ts.slot_date BETWEEN ? AND ?
        GROUP BY p.id
        ORDER BY ads_count DESC
    ";
    
    $stmt = $conn->prepare($packages_query);
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $packages_result = $stmt->get_result();
    
    $by_package = [];
    while ($row = $packages_result->fetch_assoc()) {
        $by_package[] = [
            'package_name' => $row['package_name'],
            'ads_count' => (int)$row['ads_count'],
            'customers_count' => (int)$row['customers_count'],
            'percentage' => $total_stats['total'] > 0 
                ? round(($row['ads_count'] / $total_stats['total']) * 100, 1) 
                : 0
        ];
    }
    
    
    // ============= YAQIN REKLAMALAR =============
    
    $upcoming_query = "
        SELECT 
            b.id,
            c.full_name as customer_name,
            ts.slot_date,
            ts.slot_time,
            b.ad_description,
            b.status
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN time_slots ts ON b.time_slot_id = ts.id
        WHERE ts.slot_date >= CURDATE()
        AND b.status = 'scheduled'
        ORDER BY ts.slot_date ASC, ts.slot_time ASC
        LIMIT 20
    ";
    
    $upcoming_result = $conn->query($upcoming_query);
    
    $upcoming_ads = [];
    while ($row = $upcoming_result->fetch_assoc()) {
        $upcoming_ads[] = [
            'id' => $row['id'],
            'customer_name' => $row['customer_name'],
            'date' => format_date($row['slot_date']),
            'time' => substr($row['slot_time'], 0, 5),
            'description' => $row['ad_description'],
            'status' => $row['status']
        ];
    }
    
    
    // ============= SLOT BAND BO'LISHI FOIZI =============
    
    $utilization_query = "
        SELECT 
            COUNT(DISTINCT ts.id) as total_slots,
            COUNT(DISTINCT CASE WHEN b.id IS NOT NULL THEN ts.id END) as booked_slots
        FROM time_slots ts
        LEFT JOIN bookings b ON ts.id = b.time_slot_id AND b.status != 'cancelled'
        WHERE ts.slot_date BETWEEN ? AND ?
    ";
    
    $stmt = $conn->prepare($utilization_query);
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $utilization_stats = $stmt->get_result()->fetch_assoc();
    
    $utilization_percentage = $utilization_stats['total_slots'] > 0 
        ? round(($utilization_stats['booked_slots'] / $utilization_stats['total_slots']) * 100, 1) 
        : 0;
    
    
    // ============= JAVOB YUBORISH =============
    
    send_success([
        'period' => [
            'type' => $period,
            'from' => format_date($date_from),
            'to' => format_date($date_to)
        ],
        'summary' => [
            'total' => (int)$total_stats['total'],
            'scheduled' => (int)$total_stats['scheduled'],
            'published' => (int)$total_stats['published'],
            'cancelled' => (int)$total_stats['cancelled'],
            'utilization_percentage' => $utilization_percentage
        ],
        'daily_trend' => $daily_trend,
        'hourly_distribution' => $hourly_distribution,
        'busiest_days' => $busiest_days,
        'by_package' => $by_package,
        'upcoming_ads' => $upcoming_ads,
        'utilization' => [
            'total_slots' => (int)$utilization_stats['total_slots'],
            'booked_slots' => (int)$utilization_stats['booked_slots'],
            'available_slots' => (int)$utilization_stats['total_slots'] - (int)$utilization_stats['booked_slots'],
            'percentage' => $utilization_percentage
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Ads statistics error: " . $e->getMessage());
    send_error('Reklamalar statistikasini olishda xatolik yuz berdi');
}

$conn->close();
?>