<?php
/**
 * /help Command
 * Yordam va buyruqlar ro'yxati
 */

function handleHelpCommand($chatId) {
    global $telegramBot;
    
    $text = "❓ <b>Yordam - Buyruqlar ro'yxati</b>\n\n";
    
    $text .= "📋 <b>Mavjud buyruqlar:</b>\n\n";
    
    $text .= "/start - Botni ishga tushirish\n";
    $text .= "/my_ads - Mening reklamalarim va paketlarim\n";
    $text .= "/help - Ushbu yordam xabari\n\n";
    
    $text .= "━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $text .= "🔔 <b>Bot nima qiladi?</b>\n\n";
    
    $text .= "✅ Sizning paketlaringizni ko'rsatadi\n";
    $text .= "✅ Yaqin reklamalar haqida eslatma yuboradi\n";
    $text .= "✅ Reklama chiqishi haqida xabar beradi\n";
    $text .= "✅ Qolgan reklamalar sonini ko'rsatadi\n\n";
    
    $text .= "━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $text .= "📱 <b>Qanday foydalanish?</b>\n\n";
    
    $text .= "1️⃣ /start buyrug'ini yuboring\n";
    $text .= "2️⃣ Sizning Telegram ID ni ko'rsatamiz\n";
    $text .= "3️⃣ Bu ID ni administratorga yuboring\n";
    $text .= "4️⃣ Administrator sizni tizimga qo'shadi\n";
    $text .= "5️⃣ Keyin /my_ads orqali ma'lumotlarni ko'ring\n\n";
    
    $text .= "━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $text .= "🔔 <b>Eslatmalar:</b>\n\n";
    
    $text .= "▪️ Reklamangiz chiqishidan 1 soat oldin eslatma\n";
    $text .= "▪️ Reklama chiqgandan keyin tasdiqlash\n";
    $text .= "▪️ Paket qoldig'i kamayganida ogohlantirish\n\n";
    
    $text .= "━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $text .= "💬 <b>Savol-javob:</b>\n\n";
    
    $text .= "<b>S:</b> Bot ma'lumotlarni ko'rsatmayapti?\n";
    $text .= "<b>J:</b> Avval /start ni bosing va Telegram ID ni administratorga yuboring.\n\n";
    
    $text .= "<b>S:</b> Eslatma kelmayapti?\n";
    $text .= "<b>J:</b> Telegram ID administratorda to'g'ri kiritilganligini tekshiring.\n\n";
    
    $text .= "<b>S:</b> Paket qanday sotib olaman?\n";
    $text .= "<b>J:</b> Administrator bilan bog'laning, u sizga paket biriktirib beradi.\n\n";
    
    $text .= "━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $text .= "📞 <b>Murojaat uchun:</b>\n";
    $text .= "Administrator bilan to'g'ridan-to'g'ri bog'laning.\n\n";
    
    $text .= "🤖 <i>Reklama Booking Bot v1.0</i>";
    
    $keyboard = $telegramBot->createInlineKeyboard([
        [
            ['text' => '🏠 Asosiy menyu', 'callback_data' => 'my_ads']
        ]
    ]);
    
    $telegramBot->sendMessage($chatId, $text, $keyboard);
}
?>