<?php
/**
 * Send Confirmation Notification
 * Reklama chiqgandan keyin tasdiqlash
 * 
 * Cron job: */15 * * * * php /path/to/send_confirmation.php
 */

require_once '../bot.php';

// O'tgan 15 daqiqada chiqishi kerak bo'lgan bookinglar
$stmt = $conn->prepare("
    SELECT 
        b.*,
        c.telegram_id,
        c.full_name as customer_name,
        ts.slot_date,
        ts.slot_time,
        p.name as package_name,
        cp.remaining_ads
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    JOIN time_slots ts ON b.time_slot_id = ts.id
    JOIN customer_packages cp ON b.customer_package_id = cp.id
    JOIN packages p ON cp.package_id = p.id
    WHERE b.status = 'scheduled'
    AND c.telegram_id IS NOT NULL
    AND CONCAT(ts.slot_date, ' ', ts.slot_time) <= NOW()
    AND CONCAT(ts.slot_date, ' ', ts.slot_time) >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
");

$stmt->execute();
$result = $stmt->get_result();

echo "Checking for published ads...\n";

while ($booking = $result->fetch_assoc()) {
    $chatId = $booking['telegram_id'];
    $date = $telegramBot->formatDate($booking['slot_date']);
    $time = $telegramBot->formatTime($booking['slot_time']);
    
    $text = "✅ <b>Reklamangiz chiqdi!</b>\n\n";
    $text .= "Hurmatli {$booking['customer_name']},\n\n";
    $text .= "Sizning reklamangiz muvaffaqiyatli chop etildi:\n\n";
    $text .= "📅 Sana: <b>{$date}</b>\n";
    $text .= "🕐 Vaqt: <b>{$time}</b>\n";
    $text .= "📦 Paket: {$booking['package_name']}\n\n";
    $text .= "📝 <b>Tavsif:</b>\n";
    $text .= $booking['ad_description'] . "\n\n";
    $text .= "━━━━━━━━━━━━━━━━━━━━\n\n";
    $text .= "📊 <b>Paket holati:</b>\n";
    $text .= "Qolgan reklamalar: <b>{$booking['remaining_ads']} ta</b>\n\n";
    
    if ($booking['remaining_ads'] <= 0) {
        $text .= "⚠️ <b>Diqqat!</b>\n";
        $text .= "Sizning paketingiz tugadi. Yangi paket sotib olish uchun administratorga murojaat qiling.\n\n";
    } elseif ($booking['remaining_ads'] <= 2) {
        $text .= "⚠️ <b>Eslatma:</b>\n";
        $text .= "Paketingizda kam reklama qoldi. Yangi paket sotib olishni unutmang!\n\n";
    }
    
    $text .= "📞 Keyingi reklama uchun administrator bilan bog'laning.\n\n";
    $text .= "Rahmat! 🙏";
    
    $sendResult = $telegramBot->sendMessage($chatId, $text);
    
    if ($sendResult && isset($sendResult['ok']) && $sendResult['ok']) {
        // Status ni published ga o'zgartirish
        $updateStmt = $conn->prepare("
            UPDATE bookings 
            SET status = 'published', published_at = NOW() 
            WHERE id = ?
        ");
        $updateStmt->bind_param("i", $booking['id']);
        $updateStmt->execute();
        
        echo "Sent confirmation to {$booking['customer_name']} (ID: {$booking['id']})\n";
    } else {
        echo "Failed to send to {$booking['customer_name']} (ID: {$booking['id']})\n";
        error_log("Telegram send failed for booking #{$booking['id']}: " . json_encode($sendResult));
    }
    
    // Rate limit
    sleep(1);
}

echo "Confirmation job completed\n";
?>