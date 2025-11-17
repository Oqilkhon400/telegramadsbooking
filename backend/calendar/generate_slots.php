<?php
/**
 * Generate Time Slots
 * Yangi kunlar uchun vaqt slotlarini yaratish
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

// Parametrlar
$date_from = isset($_POST['date_from']) ? clean_input($_POST['date_from']) : '';
$date_to = isset($_POST['date_to']) ? clean_input($_POST['date_to']) : '';
$days_count = isset($_POST['days_count']) ? (int)$_POST['days_count'] : 0;

// Validatsiya
$errors = [];

if (empty($date_from) && $days_count <= 0) {
    $errors[] = 'date_from yoki days_count kiritilishi shart';
}

if (!empty($date_from) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $errors[] = 'date_from formati noto\'g\'ri (YYYY-MM-DD)';
}

if (!empty($date_to) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $errors[] = 'date_to formati noto\'g\'ri (YYYY-MM-DD)';
}

if (!empty($errors)) {
    send_error(implode(', ', $errors));
}

try {
    $generated_dates = [];
    $total_slots_created = 0;
    
    // Agar days_count berilgan bo'lsa
    if ($days_count > 0) {
        $date_from = date('Y-m-d');
        $date_to = date('Y-m-d', strtotime("+$days_count days"));
    }
    
    // Agar faqat date_from berilgan bo'lsa
    if (!empty($date_from) && empty($date_to)) {
        $date_to = $date_from;
    }
    
    $current_date = strtotime($date_from);
    $end_date = strtotime($date_to);
    
    while ($current_date <= $end_date) {
        $date_str = date('Y-m-d', $current_date);
        
        // Shu kun uchun slotlar borligini tekshirish
        $check_stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM time_slots 
            WHERE slot_date = ?
        ");
        $check_stmt->bind_param("s", $date_str);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $existing_count = $check_result->fetch_assoc()['count'];
        
        if ($existing_count == 0) {
            // Slotlarni yaratish
            $generate_stmt = $conn->prepare("CALL generate_time_slots(?)");
            $generate_stmt->bind_param("s", $date_str);
            
            if ($generate_stmt->execute()) {
                // Yaratilgan slotlar sonini olish
                $count_stmt = $conn->prepare("
                    SELECT COUNT(*) as count 
                    FROM time_slots 
                    WHERE slot_date = ?
                ");
                $count_stmt->bind_param("s", $date_str);
                $count_stmt->execute();
                $count_result = $count_stmt->get_result();
                $created_count = $count_result->fetch_assoc()['count'];
                
                $generated_dates[] = [
                    'date' => format_date($date_str),
                    'slots_created' => $created_count
                ];
                
                $total_slots_created += $created_count;
            }
            
            $generate_stmt->close();
        } else {
            $generated_dates[] = [
                'date' => format_date($date_str),
                'slots_created' => 0,
                'note' => 'Allaqachon mavjud'
            ];
        }
        
        $current_date = strtotime('+1 day', $current_date);
    }
    
    send_success([
        'generated_dates' => $generated_dates,
        'total_slots_created' => $total_slots_created,
        'date_range' => [
            'from' => format_date($date_from),
            'to' => format_date($date_to)
        ]
    ], "Jami $total_slots_created ta slot yaratildi");
    
} catch (Exception $e) {
    error_log("Generate slots error: " . $e->getMessage());
    send_error('Slotlar yaratishda xatolik yuz berdi');
}

$conn->close();
?>