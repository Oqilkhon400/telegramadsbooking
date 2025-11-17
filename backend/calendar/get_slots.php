<?php
/**
 * Get Time Slots
 * Vaqt slotlarini olish (bir necha kun uchun)
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
$date_from = isset($_GET['date_from']) ? clean_input($_GET['date_from']) : date('Y-m-d');
$date_to = isset($_GET['date_to']) ? clean_input($_GET['date_to']) : date('Y-m-d', strtotime('+7 days'));

// Sana formatini tekshirish
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    send_error('Sana formati noto\'g\'ri (YYYY-MM-DD)');
}

try {
    // Slotlarni olish
    $sql = "
        SELECT 
            ts.id,
            ts.slot_date,
            ts.slot_time,
            ts.status,
            b.id as booking_id,
            b.ad_description,
            b.status as booking_status,
            c.full_name as customer_name,
            c.phone as customer_phone,
            p.name as package_name
        FROM time_slots ts
        LEFT JOIN bookings b ON ts.id = b.time_slot_id AND b.status != 'cancelled'
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN customer_packages cp ON b.customer_package_id = cp.id
        LEFT JOIN packages p ON cp.package_id = p.id
        WHERE ts.slot_date >= ? AND ts.slot_date <= ?
        ORDER BY ts.slot_date ASC, ts.slot_time ASC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $slots_by_date = [];
    $stats = [
        'total_slots' => 0,
        'available' => 0,
        'booked' => 0,
        'past' => 0
    ];
    
    while ($row = $result->fetch_assoc()) {
        $date = $row['slot_date'];
        
        if (!isset($slots_by_date[$date])) {
            $slots_by_date[$date] = [
                'date' => format_date($date),
                'date_raw' => $date,
                'slots' => []
            ];
        }
        
        $slot_data = [
            'slot_id' => $row['id'],
            'time' => substr($row['slot_time'], 0, 5), // HH:MM
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
                'ad_description' => $row['ad_description'],
                'status' => $row['booking_status']
            ];
        }
        
        $slots_by_date[$date]['slots'][] = $slot_data;
        
        // Statistika
        $stats['total_slots']++;
        if ($row['booking_id']) {
            $stats['booked']++;
        } elseif ($row['status'] === 'available') {
            $stats['available']++;
        } elseif ($row['status'] === 'past') {
            $stats['past']++;
        }
    }
    
    // Agar ba'zi kunlar uchun slotlar yo'q bo'lsa, ularni yaratish
    $current_date = strtotime($date_from);
    $end_date = strtotime($date_to);
    
    while ($current_date <= $end_date) {
        $date_str = date('Y-m-d', $current_date);
        
        if (!isset($slots_by_date[$date_str])) {
            // Slotlarni yaratish
            $generate_stmt = $conn->prepare("CALL generate_time_slots(?)");
            $generate_stmt->bind_param("s", $date_str);
            $generate_stmt->execute();
            $generate_stmt->close();
            
            $slots_by_date[$date_str] = [
                'date' => format_date($date_str),
                'date_raw' => $date_str,
                'slots' => []
            ];
            
            // Yaratilgan slotlarni olish
            $get_new_slots = $conn->prepare("
                SELECT id, slot_time, status 
                FROM time_slots 
                WHERE slot_date = ?
                ORDER BY slot_time ASC
            ");
            $get_new_slots->bind_param("s", $date_str);
            $get_new_slots->execute();
            $new_slots_result = $get_new_slots->get_result();
            
            while ($new_slot = $new_slots_result->fetch_assoc()) {
                $slots_by_date[$date_str]['slots'][] = [
                    'slot_id' => $new_slot['id'],
                    'time' => substr($new_slot['slot_time'], 0, 5),
                    'status' => $new_slot['status'],
                    'is_booked' => false,
                    'booking' => null
                ];
                
                $stats['total_slots']++;
                if ($new_slot['status'] === 'available') {
                    $stats['available']++;
                } elseif ($new_slot['status'] === 'past') {
                    $stats['past']++;
                }
            }
        }
        
        $current_date = strtotime('+1 day', $current_date);
    }
    
    // Sanaga qarab tartibga solish
    ksort($slots_by_date);
    $calendar_data = array_values($slots_by_date);
    
    send_success([
        'date_range' => [
            'from' => format_date($date_from),
            'to' => format_date($date_to)
        ],
        'calendar' => $calendar_data,
        'statistics' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Get slots error: " . $e->getMessage());
    send_error('Slotlarni olishda xatolik yuz berdi');
}

$conn->close();
?>