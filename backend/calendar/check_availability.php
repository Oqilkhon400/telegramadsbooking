<?php
/**
 * Check Availability
 * Vaqt bo'shligini tekshirish
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
$slot_date = isset($_GET['slot_date']) ? clean_input($_GET['slot_date']) : '';
$slot_time = isset($_GET['slot_time']) ? clean_input($_GET['slot_time']) : '';

// Validatsiya
$errors = [];

if (empty($slot_date)) {
    $errors[] = 'slot_date kiritilishi shart';
}

if (empty($slot_time)) {
    $errors[] = 'slot_time kiritilishi shart';
}

if (!empty($slot_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $slot_date)) {
    $errors[] = 'Sana formati noto\'g\'ri (YYYY-MM-DD)';
}

if (!empty($slot_time) && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $slot_time)) {
    $errors[] = 'Vaqt formati noto\'g\'ri (HH:MM)';
}

if (!empty($errors)) {
    send_error(implode(', ', $errors));
}

try {
    // Vaqt formatini to'g'rilash (agar sekund yo'q bo'lsa)
    if (strlen($slot_time) == 5) {
        $slot_time .= ':00';
    }
    
    // Time slot mavjudligini tekshirish
    $stmt = $conn->prepare("
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
        WHERE ts.slot_date = ? AND ts.slot_time = ?
        LIMIT 1
    ");
    
    $stmt->bind_param("ss", $slot_date, $slot_time);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $is_available = false;
    $slot_info = null;
    $reason = '';
    
    if ($result->num_rows === 0) {
        // Slot mavjud emas - yaratish mumkin
        $is_available = true;
        $reason = 'Slot mavjud emas, yangi yaratish mumkin';
        
        // O'tgan vaqtni tekshirish
        $selected_datetime = strtotime("$slot_date $slot_time");
        if ($selected_datetime < time()) {
            $is_available = false;
            $reason = 'Bu vaqt o\'tib ketgan';
        }
        
    } else {
        $slot = $result->fetch_assoc();
        
        $slot_info = [
            'slot_id' => $slot['id'],
            'date' => format_date($slot['slot_date']),
            'time' => substr($slot['slot_time'], 0, 5),
            'status' => $slot['status'],
            'is_booked' => $slot['booking_id'] !== null
        ];
        
        if ($slot['booking_id']) {
            $is_available = false;
            $reason = 'Bu vaqt band';
            
            $slot_info['booking'] = [
                'id' => $slot['booking_id'],
                'customer_name' => $slot['customer_name'],
                'customer_phone' => format_phone($slot['customer_phone']),
                'package_name' => $slot['package_name'],
                'ad_description' => $slot['ad_description'],
                'status' => $slot['booking_status']
            ];
            
        } elseif ($slot['status'] === 'past') {
            $is_available = false;
            $reason = 'Bu vaqt o\'tib ketgan';
            
        } elseif ($slot['status'] === 'available') {
            $is_available = true;
            $reason = 'Bo\'sh';
            
        } else {
            $is_available = false;
            $reason = 'Noma\'lum holat';
        }
    }
    
    send_success([
        'is_available' => $is_available,
        'reason' => $reason,
        'slot_date' => format_date($slot_date),
        'slot_time' => substr($slot_time, 0, 5),
        'slot_info' => $slot_info
    ]);
    
} catch (Exception $e) {
    error_log("Check availability error: " . $e->getMessage());
    send_error('Bo\'shlikni tekshirishda xatolik yuz berdi');
}

$conn->close();
?>