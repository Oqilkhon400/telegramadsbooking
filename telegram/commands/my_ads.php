<?php
/**
 * /start Command
 * Botni boshlash va ro'yxatdan o'tish
 */

function handleStartCommand($chatId, $telegramId, $username = null) {
    global $telegramBot;
    
    // Mijozni topish
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
        // Yangi foydalanuvchi
        $text = "👋 <b>Reklama Booking Bot'ga xush kelibsiz!</b>\n\n";
        $text .= "📱 Sizning Telegram ID: <code>{$telegramId}</code>\n\n";
        $text .= "🔔 <b>Diqqat!</b>\n";
        $text .= "Botdan foydalanish uchun avval administratorga murojaat qiling va bu ID ni ularga bering.\n\n";
        $text .= "Administrator sizni tizimga qo'shgandan keyin, siz:\n";
        $text .= "✅ O'z reklamalaringizni kuzatishingiz\n";
        $text .= "✅ Eslatmalar olishingiz\n";
        $text .= "✅ Paket holatingizni bilishingiz mumkin.\n\n";
        $text .= "📞 <b>Aloqa:</b>\n";
        $text .= "Administrator bilan bog'laning va yuqoridagi ID ni yuboring.\n\n";
        $text .= "Buyruqlar: /help";
        
        $telegramBot->sendMessage($chatId, $text);
        
        // Log qilish
        error_log("New Telegram user: ID=$telegramId, Username=$username, ChatId=$chatId");
    }
}
?>