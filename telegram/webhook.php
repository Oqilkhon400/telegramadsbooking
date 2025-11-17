<?php
/**
 * Telegram Webhook Handler
 * Telegram dan kelgan xabarlarni qabul qilish
 */

require_once 'bot.php';
require_once 'commands/start.php';
require_once 'commands/my_ads.php';
require_once 'commands/help.php';

// Telegram dan kelgan ma'lumotni olish
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Log qilish (debug uchun)
file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - " . $content . "\n", FILE_APPEND);

if (!$update) {
    exit;
}

// Message yoki callback query
$message = $update['message'] ?? null;
$callbackQuery = $update['callback_query'] ?? null;

if ($message) {
    handleMessage($message);
} elseif ($callbackQuery) {
    handleCallbackQuery($callbackQuery);
}

/**
 * Xabarni qayta ishlash
 */
function handleMessage($message) {
    global $telegramBot, $conn;
    
    $chatId = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $username = $message['from']['username'] ?? null;
    $telegramId = $message['from']['id'];
    
    // Telefon raqam yuborilgan bo'lsa
    if (preg_match('/^\+?\d{9,15}$/', $text)) {
        handlePhoneNumber($chatId, $telegramId, $username, $text);
        return;
    }
    
    // Command tekshirish
    if (strpos($text, '/') === 0) {
        $command = strtolower(explode(' ', $text)[0]);
        
        switch ($command) {
            case '/start':
                handleStartCommand($chatId, $telegramId, $username);
                break;
                
            case '/my_ads':
            case '/reklamalarim':
                handleMyAdsCommand($chatId, $telegramId);
                break;
                
            case '/help':
            case '/yordam':
                handleHelpCommand($chatId);
                break;
                
            default:
                $telegramBot->sendMessage(
                    $chatId,
                    "❓ Noma'lum buyruq.\n\n/help - Yordam"
                );
        }
    } else {
        // Oddiy xabar
        $telegramBot->sendMessage(
            $chatId,
            "Buyruqlar ro'yxatini ko'rish uchun /help yozing"
        );
    }
}

/**
 * Telefon raqam orqali mijozni bog'lash
 */
function handlePhoneNumber($chatId, $telegramId, $username, $phone) {
    global $telegramBot, $conn;
    
    // Telefon raqamni tozalash
    $cleanPhone = preg_replace('/\D/', '', $phone);
    
    // Mijozni telefon orqali topish
    $stmt = $conn->prepare("
        SELECT * FROM customers 
        WHERE REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', '') LIKE ?
        LIMIT 1
    ");
    $searchPhone = "%{$cleanPhone}%";
    $stmt->bind_param("s", $searchPhone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $customer = $result->fetch_assoc();
        
        // Telegram ma'lumotlarini yangilash
        $updateStmt = $conn->prepare("
            UPDATE customers 
            SET telegram_id = ?, telegram_username = ? 
            WHERE id = ?
        ");
        $updateStmt->bind_param("ssi", $telegramId, $username, $customer['id']);
        
        if ($updateStmt->execute()) {
            $text = "✅ <b>Muvaffaqiyatli!</b>\n\n";
            $text .= "Hurmatli {$customer['full_name']},\n";
            $text .= "Sizning hisobingiz muvaffaqiyatli bog'landi!\n\n";
            $text .= "Endi siz:\n";
            $text .= "✅ Reklamalaringizni kuzatishingiz\n";
            $text .= "✅ Eslatmalar olishingiz\n";
            $text .= "✅ Paket holatingizni bilishingiz mumkin.\n\n";
            $text .= "📊 Boshlash uchun: /my_ads";
            
            $keyboard = $telegramBot->createInlineKeyboard([
                [
                    ['text' => '📊 Mening reklamalarim', 'callback_data' => 'my_ads']
                ]
            ]);
            
            $telegramBot->sendMessage($chatId, $text, $keyboard);
        } else {
            $telegramBot->sendMessage(
                $chatId,
                "❌ Xatolik yuz berdi. Iltimos, qayta urinib ko'ring yoki administrator bilan bog'laning."
            );
        }
    } else {
        $text = "❌ <b>Telefon raqam topilmadi</b>\n\n";
        $text .= "Bu telefon raqam bizning tizimda ro'yxatdan o'tmagan.\n\n";
        $text .= "Iltimos:\n";
        $text .= "1️⃣ Raqamni to'g'ri kiriting\n";
        $text .= "2️⃣ Yoki administrator bilan bog'laning\n\n";
        $text .= "Qaytadan urinish uchun telefon raqamingizni yuboring.";
        
        $telegramBot->sendMessage($chatId, $text);
    }
}

/**
 * Callback query ni qayta ishlash
 */
function handleCallbackQuery($callbackQuery) {
    global $telegramBot;
    
    $chatId = $callbackQuery['message']['chat']['id'];
    $messageId = $callbackQuery['message']['message_id'];
    $data = $callbackQuery['data'];
    $callbackId = $callbackQuery['id'];
    
    // Answer callback query
    $telegramBot->request('answerCallbackQuery', [
        'callback_query_id' => $callbackId
    ]);
    
    // Data ni parse qilish
    $parts = explode(':', $data);
    $action = $parts[0] ?? '';
    $param = $parts[1] ?? '';
    
    switch ($action) {
        case 'my_ads':
            handleMyAdsCommand($chatId, $callbackQuery['from']['id']);
            break;
            
        case 'packages':
            showPackagesInfo($chatId, $callbackQuery['from']['id']);
            break;
            
        case 'upcoming':
            showUpcomingBookings($chatId, $callbackQuery['from']['id']);
            break;
            
        case 'history':
            showBookingHistory($chatId, $callbackQuery['from']['id']);
            break;
            
        default:
            $telegramBot->sendMessage($chatId, "❓ Noma'lum amal");
    }
}

/**
 * Paketlar ma'lumotini ko'rsatish
 */
function showPackagesInfo($chatId, $telegramId) {
    global $telegramBot;
    
    $customer = $telegramBot->findCustomerByTelegramId($telegramId);
    
    if (!$customer) {
        $telegramBot->sendMessage(
            $chatId,
            "❌ Sizning hisobingiz topilmadi.\n\nIltimos, administratorga murojaat qiling."
        );
        return;
    }
    
    $packages = $telegramBot->getCustomerPackages($customer['id']);
    
    if (empty($packages)) {
        $telegramBot->sendMessage(
            $chatId,
            "📦 Sizda hozircha aktiv paketlar yo'q.\n\nPaket sotib olish uchun administratorga murojaat qiling."
        );
        return;
    }
    
    $text = "📦 <b>Sizning paketlaringiz:</b>\n\n";
    
    foreach ($packages as $pkg) {
        $text .= "▪️ <b>{$pkg['package_name']}</b>\n";
        $text .= "   Jami: {$pkg['total_ads']} ta\n";
        $text .= "   Ishlatilgan: {$pkg['used_ads']} ta\n";
        $text .= "   Qolgan: <b>{$pkg['remaining_ads']} ta</b>\n";
        $text .= "   Holat: " . ($pkg['status'] === 'active' ? '✅ Aktiv' : '⏸ Tugagan') . "\n\n";
    }
    
    $keyboard = $telegramBot->createInlineKeyboard([
        [
            ['text' => '🏠 Asosiy menyu', 'callback_data' => 'my_ads']
        ]
    ]);
    
    $telegramBot->sendMessage($chatId, $text, $keyboard);
}

/**
 * Yaqin bookinglarni ko'rsatish
 */
function showUpcomingBookings($chatId, $telegramId) {
    global $telegramBot;
    
    $customer = $telegramBot->findCustomerByTelegramId($telegramId);
    
    if (!$customer) {
        $telegramBot->sendMessage($chatId, "❌ Sizning hisobingiz topilmadi.");
        return;
    }
    
    $bookings = $telegramBot->getCustomerBookings($customer['id'], 5);
    $upcomingBookings = array_filter($bookings, function($b) {
        return strtotime($b['slot_date'] . ' ' . $b['slot_time']) >= time();
    });
    
    if (empty($upcomingBookings)) {
        $telegramBot->sendMessage(
            $chatId,
            "📅 Yaqin vaqtda rejalashtirilgan reklamalar yo'q."
        );
        return;
    }
    
    $text = "📅 <b>Yaqin reklamalar:</b>\n\n";
    
    foreach ($upcomingBookings as $booking) {
        $date = $telegramBot->formatDate($booking['slot_date']);
        $time = $telegramBot->formatTime($booking['slot_time']);
        
        $text .= "📍 <b>{$date} - {$time}</b>\n";
        $text .= "   Paket: {$booking['package_name']}\n";
        $text .= "   Tavsif: {$booking['ad_description']}\n";
        $text .= "   Holat: ";
        
        if ($booking['status'] === 'scheduled') {
            $text .= "⏰ Rejalashtirilgan";
        } elseif ($booking['status'] === 'published') {
            $text .= "✅ Chop etilgan";
        }
        
        $text .= "\n\n";
    }
    
    $keyboard = $telegramBot->createInlineKeyboard([
        [
            ['text' => '🏠 Asosiy menyu', 'callback_data' => 'my_ads']
        ]
    ]);
    
    $telegramBot->sendMessage($chatId, $text, $keyboard);
}

/**
 * Booking tarixini ko'rsatish
 */
function showBookingHistory($chatId, $telegramId) {
    global $telegramBot;
    
    $customer = $telegramBot->findCustomerByTelegramId($telegramId);
    
    if (!$customer) {
        $telegramBot->sendMessage($chatId, "❌ Sizning hisobingiz topilmadi.");
        return;
    }
    
    $bookings = $telegramBot->getCustomerBookings($customer['id'], 10);
    
    if (empty($bookings)) {
        $telegramBot->sendMessage(
            $chatId,
            "📋 Reklamalar tarixi bo'sh."
        );
        return;
    }
    
    $text = "📋 <b>Oxirgi 10 ta reklama:</b>\n\n";
    
    foreach ($bookings as $booking) {
        $date = $telegramBot->formatDate($booking['slot_date']);
        $time = $telegramBot->formatTime($booking['slot_time']);
        
        $text .= "📍 {$date} - {$time}\n";
        $text .= "   " . substr($booking['ad_description'], 0, 30) . "...\n";
        $text .= "   Holat: ";
        
        if ($booking['status'] === 'scheduled') {
            $text .= "⏰ Kutilmoqda";
        } elseif ($booking['status'] === 'published') {
            $text .= "✅ Chiqdi";
        }
        
        $text .= "\n\n";
    }
    
    $keyboard = $telegramBot->createInlineKeyboard([
        [
            ['text' => '🏠 Asosiy menyu', 'callback_data' => 'my_ads']
        ]
    ]);
    
    $telegramBot->sendMessage($chatId, $text, $keyboard);
}
?>