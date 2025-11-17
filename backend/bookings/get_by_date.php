<?php
/**
 * Get Bookings by Date
 * Sanaga qarab bookinglarni olish (kalendar uchun)
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
$date = isset($_GET['date']) ? clean_input($_GET['date']) : date('Y-m-d');

// Sana formatini tekshirish
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    send_error('Sana formati noto\'g\'ri (YYYY-MM-DD)');
}

try {
    // Shu sanadagi barcha vaqt slotlarni olish
    $slots_sql = "
        SELECT 
            ts.id,
            ts.slot_time,
            ts.status,
            b.id as booking_id,
            b.ad_description,
            b.status as booking_status,
            c.full_name as customer_name,
            c.phone as customer_phone,
            p.name as package_name,
            cp.remaining_ads
        FROM time_slots ts
        LEFT JOIN bookings b ON ts.id = b.time_slot_id AND b.status != 'cancelled'
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN customer_packages cp ON b.customer_package_id = cp.id
        LEFT JOIN packages p ON cp.package_id = p.id
        WHERE ts.slot_date = ?
        ORDER BY ts.slot_time ASC
    ";
    
    $stmt = $conn->prepare($slots_sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $time_slots = [];
    $booked_count = 0;
    $available_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $slot_time = substr($row['slot_time'], 0, 5); // HH:MM
        
        $slot_data = [
            'slot_id' => $row['id'],
            'time' => $slot_time,
            'status' => $row['status'],
            'is_booked' => $row['booking_id'] !== null,
            'booking' => null
        ];
        
        if ($row['booking_id']) {
            $slot_data['booking'] = [
                'id' => $row['booking_id'],
                'customer_name' => $row['customer_name'],
                'customer_phone' => format_phone($row['customer_phone']),
                'package_name' => $row['package_name'],
                'remaining_ads' => (int)$row['remaining_ads'],
                'ad_description' => $row['ad_description'],
                'status' => $row['booking_status']
            ];
            $booked_count++;
        } else if ($row['status'] === 'available') {
            $available_count++;
        }
        
        $time_slots[] = $slot_data;
    }
    
    // Agar shu sanadagi slotlar yo'q bo'lsa, default slotlarni yaratish
    if (empty($time_slots)) {
        $generate_stmt = $conn->prepare("CALL generate_time_slots(?)");
        $generate_stmt->bind_param("s", $date);
        $generate_stmt->execute();
        
        // Qayta olish
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $time_slots[] = [
                'slot_id' => $row['id'],
                'time' => substr($row['slot_time'], 0, 5),
                'status' => $row['status'],
                'is_booked' => false,
                'booking' => null
            ];
            
            if ($row['status'] === 'available') {
                $available_count++;
            }
        }
    }
    
    send_success([
        'date' => format_date($date),
        'time_slots' => $time_slots,
        'statistics' => [
            'total_slots' => count($time_slots),
            'booked' => $booked_count,
            'available' => $available_count,
            'past' => count($time_slots) - $booked_count - $available_count
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get bookings by date error: " . $e->getMessage());
    send_error('Bookinglarni olishda xatolik yuz berdi');
}

$conn->close();
?>