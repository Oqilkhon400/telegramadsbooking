<?php
/**
 * Customers Statistics
 * Mijozlar statistikasi
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

try {
    // ============= UMUMIY STATISTIKA =============
    
    $total_query = "
        SELECT 
            COUNT(*) as total,
            SUM(is_active) as active,
            COUNT(*) - SUM(is_active) as inactive
        FROM customers
    ";
    $total_stats = $conn->query($total_query)->fetch_assoc();
    
    
    // ============= YANGI MIJOZLAR TRENDI =============
    
    $trend_query = "
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as count
        FROM customers
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ";
    $trend_result = $conn->query($trend_query);
    
    $new_customers_trend = [];
    while ($row = $trend_result->fetch_assoc()) {
        $new_customers_trend[] = [
            'date' => format_date($row['date']),
            'count' => (int)$row['count']
        ];
    }
    
    
    // ============= TOP MIJOZLAR (to'lovlar bo'yicha) =============
    
    $top_by_revenue = "
        SELECT 
            c.id,
            c.full_name,
            c.phone,
            COUNT(DISTINCT p.id) as payment_count,
            SUM(p.amount) as total_paid,
            COUNT(DISTINCT cp.id) as packages_purchased,
            COUNT(DISTINCT b.id) as ads_booked
        FROM customers c
        LEFT JOIN payments p ON c.id = p.customer_id
        LEFT JOIN customer_packages cp ON c.id = cp.customer_id
        LEFT JOIN bookings b ON c.id = b.customer_id
        GROUP BY c.id
        HAVING total_paid > 0
        ORDER BY total_paid DESC
        LIMIT 10
    ";
    $top_revenue_result = $conn->query($top_by_revenue);
    
    $top_customers_revenue = [];
    while ($customer = $top_revenue_result->fetch_assoc()) {
        $top_customers_revenue[] = [
            'id' => $customer['id'],
            'name' => $customer['full_name'],
            'phone' => format_phone($customer['phone']),
            'payment_count' => (int)$customer['payment_count'],
            'total_paid' => format_money($customer['total_paid']),
            'packages_purchased' => (int)$customer['packages_purchased'],
            'ads_booked' => (int)$customer['ads_booked']
        ];
    }
    
    
    // ============= TOP MIJOZLAR (reklamalar bo'yicha) =============
    
    $top_by_ads = "
        SELECT 
            c.id,
            c.full_name,
            c.phone,
            COUNT(DISTINCT b.id) as total_ads,
            SUM(CASE WHEN b.status = 'published' THEN 1 ELSE 0 END) as published_ads,
            SUM(CASE WHEN b.status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_ads
        FROM customers c
        LEFT JOIN bookings b ON c.id = b.customer_id
        GROUP BY c.id
        HAVING total_ads > 0
        ORDER BY total_ads DESC
        LIMIT 10
    ";
    $top_ads_result = $conn->query($top_by_ads);
    
    $top_customers_ads = [];
    while ($customer = $top_ads_result->fetch_assoc()) {
        $top_customers_ads[] = [
            'id' => $customer['id'],
            'name' => $customer['full_name'],
            'phone' => format_phone($customer['phone']),
            'total_ads' => (int)$customer['total_ads'],
            'published_ads' => (int)$customer['published_ads'],
            'scheduled_ads' => (int)$customer['scheduled_ads']
        ];
    }
    
    
    // ============= MIJOZLAR FAOLLIGI =============
    
    $activity_query = "
        SELECT 
            CASE 
                WHEN total_paid = 0 THEN 'Faol emas (to\'lov yo\'q)'
                WHEN remaining_ads = 0 THEN 'Paket tugagan'
                WHEN remaining_ads > 0 THEN 'Faol (paket mavjud)'
                ELSE 'Noaniq'
            END as activity_status,
            COUNT(*) as count
        FROM (
            SELECT 
                c.id,
                COALESCE(SUM(p.amount), 0) as total_paid,
                COALESCE(SUM(cp.remaining_ads), 0) as remaining_ads
            FROM customers c
            LEFT JOIN payments p ON c.id = p.customer_id
            LEFT JOIN customer_packages cp ON c.id = cp.customer_id AND cp.status = 'active'
            WHERE c.is_active = 1
            GROUP BY c.id
        ) as customer_activity
        GROUP BY activity_status
    ";
    $activity_result = $conn->query($activity_query);
    
    $activity_breakdown = [];
    while ($row = $activity_result->fetch_assoc()) {
        $activity_breakdown[] = [
            'status' => $row['activity_status'],
            'count' => (int)$row['count']
        ];
    }
    
    
    // ============= O'RTACHA STATISTIKA =============
    
    $avg_stats_query = "
        SELECT 
            AVG(payment_count) as avg_payments_per_customer,
            AVG(total_paid) as avg_revenue_per_customer,
            AVG(packages_count) as avg_packages_per_customer,
            AVG(ads_count) as avg_ads_per_customer
        FROM (
            SELECT 
                c.id,
                COUNT(DISTINCT p.id) as payment_count,
                COALESCE(SUM(p.amount), 0) as total_paid,
                COUNT(DISTINCT cp.id) as packages_count,
                COUNT(DISTINCT b.id) as ads_count
            FROM customers c
            LEFT JOIN payments p ON c.id = p.customer_id
            LEFT JOIN customer_packages cp ON c.id = cp.customer_id
            LEFT JOIN bookings b ON c.id = b.customer_id
            GROUP BY c.id
        ) as customer_stats
    ";
    $avg_stats = $conn->query($avg_stats_query)->fetch_assoc();
    
    
    // ============= JAVOB YUBORISH =============
    
    send_success([
        'summary' => [
            'total_customers' => (int)$total_stats['total'],
            'active' => (int)$total_stats['active'],
            'inactive' => (int)$total_stats['inactive'],
            'active_percentage' => $total_stats['total'] > 0 
                ? round(($total_stats['active'] / $total_stats['total']) * 100, 1) 
                : 0
        ],
        'new_customers_trend' => $new_customers_trend,
        'top_by_revenue' => $top_customers_revenue,
        'top_by_ads' => $top_customers_ads,
        'activity_breakdown' => $activity_breakdown,
        'averages' => [
            'payments_per_customer' => round($avg_stats['avg_payments_per_customer'], 2),
            'revenue_per_customer' => format_money($avg_stats['avg_revenue_per_customer']),
            'packages_per_customer' => round($avg_stats['avg_packages_per_customer'], 2),
            'ads_per_customer' => round($avg_stats['avg_ads_per_customer'], 2)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Customers statistics error: " . $e->getMessage());
    send_error('Mijozlar statistikasini olishda xatolik yuz berdi');
}

$conn->close();
?>