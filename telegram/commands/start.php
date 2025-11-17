<?php
/**
 * /start Command
 * Botni boshlash va ro'yxatdan o'tish
 */

function handleStartCommand($chatId, $telegramId, $username = null) {
    global $telegramBot, $conn;
    
    // Telegram ID bo'yicha topish
    $customer = $telegramBot->findCustomerByTelegramId($telegramId);
    
    if ($customer) {
        // Allaqachon ro'yxatdan o'tgan
        $text = "👋 <b>Xush kelibsiz, {$customer['full_name']}!</b>\n\n";
        $text .= "Siz allaqachon ro'yxatdan o'tgansiz.\n\n";
        $text .= "Quyidagi buyruqlardan foydalanishingiz mumkin:\n";
        $text .= "/my_ads - Mening reklamalarim\n";
        $text .= "/help - Yordam\n";
        
        $keyboard = $telegramBot->createInlineKeyboard([
            [
                ['text' => '📊 Mening reklamalarim', 'callback_data' => 'my_ads']
            ],
            [
                ['text' => '📦 Paketlarim', 'callback_data' => 'packages']
            ],
            [
                ['text' => '❓ Yordam', 'callback_data' => 'help']
            ]
        ]);
        
        $telegramBot->sendMessage($chatId, $text, $keyboard);
        
    } else {
        // Yangi foydalanuvchi - telefon raqam orqali topish
        $text = "👋 <b>Reklama Booking Bot'ga xush kelibsiz!</b>\n\n";
        $text .= "📱 Sizning Telegram ID: <code>{$telegramId}</code>\n\n";
        
        // Telefon raqam so'rash
        $text .= "🔐 <b>Ro'yxatdan o'tish:</b>\n";
        $text .= "Iltimos, telefon raqamingizni yuboring (bizda ro'yxatdan o'tgan raqamingiz):\n\n";
        $text .= "<i>Masalan: +998901234567</i>\n\n";
        $text .= "⚠️ Agar siz hali bizda ro'yxatdan o'tmagan bo'lsangiz, avval administrator bilan bog'laning.";
        
        $telegramBot->sendMessage($chatId, $text);
        
        // Log qilish
        error_log("New Telegram user: ID=$telegramId, Username=$username, ChatId=$chatId");
    }
}
?>