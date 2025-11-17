<?php
/**
 * Send Reminder Notification
 * Reklama 1 soat oldin eslatma yuborish
 * 
 * Cron job: */10 * * * * php /path/to/send_reminder.php
 */

require_once '../bot.php';

// 1 soat oldidan eslatma yuborish
$upcomingBookings = $telegramBot->getUpcomingBookings(1);

echo "Found " . count($upcomingBookings) . " upcoming bookings\n";

foreach ($upcomingBookings as $booking) {
    if (!$booking['telegram_id']) {
        echo "Skipping booking #{$booking['id']} - no telegram_id\n";
        continue;
    }
    
    $chatId = $booking['telegram_id'];
    $date = $telegramBot->formatDate($booking['slot_date']);
    $time = $telegramBot->formatTime($booking['slot_time']);
    
    $text = "⏰ <b>Eslatma!</b>\n\n";
    $text .= "Hurmatli {$booking['customer_name']},\n\n";
    $text .= "Sizning reklamangiz 1 soatdan keyin chiqadi:\n\n";
    $text .= "📅 Sana: <b>{$date}</b>\n";
    $text .= "🕐 Vaqt: <b>{$time}</b>\n";
    $text .= "📦 Paket: {$booking['package_name']}\n\n";
    $text .= "📝 <b>Reklama tavsifi:</b>\n";
    $text .= $booking['ad_description'] . "\n\n";
    $text .= "━━━━━━━━━━━━━━━━━━━━\n";
    $text .= "Reklama chiqgandan so'ng, sizga yana xabar yuboramiz.\n\n";
    $text .= "✅ Muvaffaqiyat tilaymiz!";
    
    $result = $telegramBot->sendMessage($chatId, $text);
    
    if ($result && isset($result['ok']) && $result['ok']) {
        // Yuborilgan deb belgilash
        $telegramBot->markNotificationSent($booking['id']);
        echo "Sent reminder to {$booking['customer_name']} (ID: {$booking['id']})\n";
    } else {
        echo "Failed to send to {$booking['customer_name']} (ID: {$booking['id']})\n";
        error_log("Telegram send failed for booking #{$booking['id']}: " . json_encode($result));
    }
    
    // Rate limit (1 xabar/sekund)
    sleep(1);
}

echo "Reminder job completed\n";
?>