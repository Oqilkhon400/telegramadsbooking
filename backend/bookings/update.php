<?php
/**
 * Update Booking
 * Booking yangilash (vaqt o'zgartirish, tavsif yangilash)
 */

require_once '../../config/database.php';
require_once '../../config/settings.php';

// Session tekshirish
if (!is_logged_in()) {
    send_error('Tizimga kirish kerak', 401);
}

// Faqat POST metodini qabul qilish
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Faqat POST metodi qabul qilinadi', 405);
}

// Ma'lumotlarni olish
$booking_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$new_slot_date = isset($_POST['slot_date']) ? clean_input($_POST['slot_date']) : null;
$new_slot_time = isset($_POST['slot_time']) ? clean_input($_POST['slot_time']) : null;
$ad_description = isset($_POST['ad_description']) ? clean_input($_POST['ad_description']) : null;
$status = isset($_POST['status']) ? clean_input($_POST['status']) : null;
$notes = isset($_POST['notes']) ? clean_input($_POST['notes']) : null;

// Validatsiya
if ($booking_id <= 0) {
    send_error('Noto\'g\'ri booking ID');
}

// Status validatsiya
$allowed_statuses = ['scheduled', 'published', 'cancelled'];
if ($status !== null && !in_array($status, $allowed_statuses)) {
    send_error('Noto\'g\'ri status. Ruxsat etilgan: ' . implode(', ', $allowed_statuses));
}

try {
    // Booking mavjudligini tekshirish
    $check_stmt = $conn->prepare("
        SELECT 
            b.*,
            ts.slot_date as current_slot_date,
            ts.slot_time as current_slot_time,
            ts.id as current_slot_id
        FROM bookings b
        JOIN time_slots ts ON b.time_slot_id = ts.id
        WHERE b.id = ?
        LIMIT 1
    ");
    
    $check_stmt->bind_param("i", $booking_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        send_error('Booking topilmadi', 404);
    }
    
    $booking = $result->fetch_assoc();
    
    // Agar booking published yoki cancelled bo'lsa, vaqtni o'zgartirib bo'lmaydi
    if (($new_slot_date || $new_slot_time) && in_array($booking['status'], ['published', 'cancelled'])) {
        send_error('Published yoki cancelled booking vaqtini o\'zgartirib bo\'lmaydi');
    }
    
    $new_time_slot_id = null;
    
    // Agar yangi vaqt berilgan bo'lsa
    if ($new_slot_date && $new_slot_time) {
        // O'tgan vaqtni tekshirish
        $selected_datetime = strtotime("$new_slot_date $new_slot_time");
        if ($selected_datetime < time()) {
            send_error('O\'tgan vaqtga ko\'chirib bo\'lmaydi');
        }
        
        // Yangi time slot mavjudligini tekshirish
        $slot_stmt = $conn->prepare("
            SELECT id, status 
            FROM time_slots 
            WHERE slot_date = ? AND slot_time = ?
            LIMIT 1
        ");
        
        $slot_stmt->bind_param("ss", $new_slot_date, $new_slot_time);
        $slot_stmt->execute();
        $slot_result = $slot_stmt->get_result();
        
        if ($slot_result->num_rows === 0) {
            // Time slot yo'q - yaratish
            $create_slot = $conn->prepare("
                INSERT INTO time_slots (slot_date, slot_time, status) 
                VALUES (?, ?, 'available')
            ");
            $create_slot->bind_param("ss", $new_slot_date, $new_slot_time);
            
            if ($create_slot->execute()) {
                $new_time_slot_id = $conn->insert_id;
            } else {
                throw new Exception('Yangi time slot yaratishda xatolik');
            }
        } else {
            $slot = $slot_result->fetch_assoc();
            
            // Bo'shligini tekshirish (o'sha booking emas)
            if ($slot['status'] !== 'available' && $slot['id'] != $booking['current_slot_id']) {
                send_error('Yangi vaqt band yoki o\'tgan');
            }
            
            $new_time_slot_id = $slot['id'];
        }
        
        // Eski time slot ni available qilish
        $update_old_slot = $conn->prepare("
            UPDATE time_slots 
            SET status = 'available' 
            WHERE id = ?
        ");
        $update_old_slot->bind_param("i", $booking['current_slot_id']);
        $update_old_slot->execute();
    }
    
    // Booking yangilash
    $update_fields = [];
    $update_params = [];
    $update_types = '';
    
    if ($new_time_slot_id) {
        $update_fields[] = "time_slot_id = ?";
        $update_params[] = &$new_time_slot_id;
        $update_types .= 'i';
    }
    
    if ($ad_description !== null) {
        $update_fields[] = "ad_description = ?";
        $update_params[] = &$ad_description;
        $update_types .= 's';
    }
    
    if ($status !== null) {
        $update_fields[] = "status = ?";
        $update_params[] = &$status;
        $update_types .= 's';
        
        // Agar published bo'lsa, published_at ni o'rnatish
        if ($status === 'published') {
            $update_fields[] = "published_at = NOW()";
        }
    }
    
    if ($notes !== null) {
        $update_fields[] = "notes = ?";
        $update_params[] = &$notes;
        $update_types .= 's';
    }
    
    if (empty($update_fields)) {
        send_error('Yangilash uchun hech qanday ma\'lumot berilmagan');
    }
    
    $update_sql = "UPDATE bookings SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $update_params[] = &$booking_id;
    $update_types .= 'i';
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param($update_types, ...$update_params);
    
    if ($update_stmt->execute()) {
        // Yangi time slot ni booked qilish
        if ($new_time_slot_id) {
            $update_new_slot = $conn->prepare("
                UPDATE time_slots 
                SET status = 'booked' 
                WHERE id = ?
            ");
            $update_new_slot->bind_param("i", $new_time_slot_id);
            $update_new_slot->execute();
        }
        
        // Yangilangan booking ma'lumotlarini olish
        $get_stmt = $conn->prepare("
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
                u.full_name as booked_by_name
            FROM bookings b
            JOIN customers c ON b.customer_id = c.id
            JOIN customer_packages cp ON b.customer_package_id = cp.id
            JOIN packages p ON cp.package_id = p.id
            JOIN time_slots ts ON b.time_slot_id = ts.id
            LEFT JOIN users u ON b.booked_by = u.id
            WHERE b.id = ?
        ");
        
        $get_stmt->bind_param("i", $booking_id);
        $get_stmt->execute();
        $result = $get_stmt->get_result();
        $updated_booking = $result->fetch_assoc();
        
        // Ma'lumotlarni formatlash
        $updated_booking['booking_date'] = format_datetime($updated_booking['booking_date']);
        $updated_booking['slot_date'] = format_date($updated_booking['slot_date']);
        $updated_booking['slot_time'] = substr($updated_booking['slot_time'], 0, 5);
        $updated_booking['customer_phone'] = format_phone($updated_booking['customer_phone']);
        
        if ($updated_booking['published_at']) {
            $updated_booking['published_at'] = format_datetime($updated_booking['published_at']);
        }
        
        send_success($updated_booking, 'Booking muvaffaqiyatli yangilandi');
    } else {
        throw new Exception('Booking yangilashda xatolik');
    }
    
} catch (Exception $e) {
    error_log("Update booking error: " . $e->getMessage());
    send_error('Booking yangilashda xatolik yuz berdi');
}

$conn->close();
?>