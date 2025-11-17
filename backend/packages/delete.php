<?php
/**
 * Delete Package
 * Paketni o'chirish
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

// ID olish
$package_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($package_id <= 0) {
    send_error('Noto\'g\'ri paket ID');
}

try {
    // Paket mavjudligini tekshirish
    $check_stmt = $conn->prepare("
        SELECT id, name 
        FROM packages 
        WHERE id = ? 
        LIMIT 1
    ");
    $check_stmt->bind_param("i", $package_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        send_error('Paket topilmadi', 404);
    }
    
    $package = $result->fetch_assoc();
    
    // Paket mijozlarda ishlatilganligini tekshirish
    $usage_check = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM customer_packages 
        WHERE package_id = ?
    ");
    $usage_check->bind_param("i", $package_id);
    $usage_check->execute();
    $usage_result = $usage_check->get_result();
    $usage_count = $usage_result->fetch_assoc()['count'];
    
    if ($usage_count > 0) {
        send_error("Bu paket $usage_count ta mijozda ishlatilgan. O'chirib bo'lmaydi. O'rniga paketni faol emas (inactive) qiling.");
    }
    
    // Paketni o'chirish
    $delete_stmt = $conn->prepare("DELETE FROM packages WHERE id = ?");
    $delete_stmt->bind_param("i", $package_id);
    
    if ($delete_stmt->execute()) {
        send_success([
            'deleted_id' => $package_id,
            'package_name' => $package['name']
        ], 'Paket muvaffaqiyatli o\'chirildi');
    } else {
        throw new Exception('Paket o\'chirishda xatolik');
    }
    
} catch (Exception $e) {
    error_log("Delete package error: " . $e->getMessage());
    send_error('Paket o\'chirishda xatolik yuz berdi');
}

$conn->close();
?>