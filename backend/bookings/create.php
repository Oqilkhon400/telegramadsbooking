<?php
/**
 * Create Booking
 * Reklama booking qilish
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
$customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
$customer_package_id = isset($_POST['customer_package_id']) ? (int)$_POST['customer_package_id'] : 0;
$slot_date = isset($_POST['slot_date']) ? clean_input($_POST['slot_date']) : '';
$slot_time = isset($_POST['slot_time']) ? clean_input($_POST['slot_time']) : '';
$ad_description = isset($_POST['ad_description']) ? clean_input($_POST['ad_description']) : '';
$notes = isset($_POST['notes']) ? clean_input($_POST['notes']) : null;

// Validatsiya
$errors = [];

if ($customer_id <= 0) {
    $errors[] = 'Mijoz tanlanishi shart';
}

if ($customer_package_id <= 0) {
    $errors[] = 'Paket tanlanishi shart';
}

if (empty($slot_date)) {
    $errors[] = 'Sana tanlanishi shart';
}

if (empty($slot_time)) {
    $errors[] = 'Vaqt tanlanishi shart';
}

if (empty($ad_description)) {
    $errors[] = 'Reklama tavsifi kiritilishi shart';
}

// Sana formatini tekshirish
if (!empty($slot_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $slot_date)) {
    $errors[] = 'Sana formati noto\'g\'ri (YYYY-MM-DD)';
}

// Vaqt formatini tekshirish
if (!empty($slot_time) && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $slot_time)) {
    $errors[] = 'Vaqt formati noto\'g\'ri (HH:MM)';
}

if (!empty($errors)) {
    send_error(implode(', ', $errors));
}

try {
    // Mijoz paket mavjudligini va qoldiq borligini tekshirish
    $cp_stmt = $conn->prepare("
        SELECT 
            cp.*,
            c.full_name as customer_name,
            c.is_active as customer_active,
            p.name as package_name
        FROM customer_packages cp
        JOIN customers c ON cp.customer_id = c.id
        JOIN packages p ON cp.package_id = p.id
        WHERE cp.id = ? AND cp.customer_id = ?
        LIMIT 1
    ");
    
    $cp_stmt->bind_param("ii", $customer_package_id, $customer_id);
    $cp_stmt->execute();
    $cp_result = $cp_stmt->get_result();
    
    if ($cp_result->num_rows === 0) {
        send_error('Mijoz paket ma\'lumotlari topilmadi', 404);
    }
    
    $customer_package = $cp_result->fetch_assoc();
    
    // Mijoz faol emasligini tekshirish
    if ($customer_package['customer_active'] != 1) {
        send_error('Bu mijoz faol emas');
    }
    
    // Paket statusini tekshirish
    if ($customer_package['status'] !== 'active') {
        send_error('Bu paket faol emas yoki tugagan');
    }
    
    // Qoldiq borligini tekshirish
    if ($customer_package['remaining_ads'] <= 0) {
        send_error('Bu paketda qoldiq reklama yo\'q');
    }
    
    // Time slot mavjudligini va bo'shligini tekshirish
    $slot_stmt = $conn->prepare("
        SELECT id, status 
        FROM time_slots 
        WHERE slot_date = ? AND slot_time = ?
        LIMIT 1
    ");
    
    $slot_stmt->bind_param("ss", $slot_date, $slot_time);
    $slot_stmt->execute();
    $slot_result = $slot_stmt->get_result();
    
    $time_slot_id = null;
    
    if ($slot_result->num_rows === 0) {
        // Time slot yo'q - yaratish kerak
        $create_slot = $conn->prepare("
            INSERT INTO time_slots (slot_date, slot_time, status) 
            VALUES (?, ?, 'available')
        ");
        $create_slot->bind_param("ss", $slot_date, $slot_time);
        
        if ($create_slot->execute()) {
            $time_slot_id = $conn->insert_id;
        } else {
            throw new Exception('Time slot yaratishda xatolik');
        }
    } else {
        $slot = $slot_result->fetch_assoc();
        $time_slot_id = $slot['id'];
        
        // Bo'shligini tekshirish
        if ($slot['status'] !== 'available') {
            send_error('Bu vaqt band yoki o\'tgan');
        }
    }
    
    // O'tgan vaqtni tekshirish
    $selected_datetime = strtotime("$slot_date $slot_time");
    if ($selected_datetime < time()) {
        send_error('O\'tgan vaqtga booking qilib bo\'lmaydi');
    }
    
    // Booking yaratish
    $stmt = $conn->prepare("
        INSERT INTO bookings 
        (customer_id, customer_package_id, time_slot_id, ad_description, booked_by, notes) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $booked_by = $_SESSION['user_id'];
    
    $stmt->bind_param(
        "iiisis",
        $customer_id,
        $customer_package_id,
        $time_slot_id,
        $ad_description,
        $booked_by,
        $notes
    );
    
    if ($stmt->execute()) {
        $booking_id = $conn->insert_id;
        
        // Booking ma'lumotlarini olish
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
                ts.status as slot_status,
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
        $booking = $result->fetch_assoc();
        
        // Ma'lumotlarni formatlash
        $booking['booking_date'] = format_datetime($booking['booking_date']);
        $booking['slot_date'] = format_date($booking['slot_date']);
        $booking['slot_time'] = substr($booking['slot_time'], 0, 5); // HH:MM
        $booking['customer_phone'] = format_phone($booking['customer_phone']);
        
        // Paket qoldig'i
        $booking['package_progress'] = [
            'used' => (int)$booking['used_ads'],
            'total' => (int)$booking['total_ads'],
            'remaining' => (int)$booking['remaining_ads'],
            'percentage' => round(((int)$booking['used_ads'] / (int)$booking['total_ads']) * 100, 1)
        ];
        
        send_success($booking, 'Reklama muvaffaqiyatli booking qilindi');
    } else {
        throw new Exception('Booking yaratishda xatolik');
    }
    
} catch (Exception $e) {
    error_log("Create booking error: " . $e->getMessage());
    send_error('Booking qilishda xatolik yuz berdi');
}

$conn->close();
?>