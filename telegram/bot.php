<?php
/**
 * Telegram Bot Main File
 * Asosiy bot logikasi
 */

require_once '../config/database.php';
require_once '../config/settings.php';

class TelegramBot {
    private $botToken;
    private $apiUrl;
    private $conn;
    
    public function __construct($token, $dbConnection) {
        $this->botToken = $token;
        $this->apiUrl = "https://api.telegram.org/bot{$token}/";
        $this->conn = $dbConnection;
    }
    
    /**
     * Xabar yuborish
     */
    public function sendMessage($chatId, $text, $replyMarkup = null, $parseMode = 'HTML') {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode
        ];
        
        if ($replyMarkup) {
            $data['reply_markup'] = json_encode($replyMarkup);
        }
        
        return $this->request('sendMessage', $data);
    }
    
    /**
     * Inline keyboard yaratish
     */
    public function createInlineKeyboard($buttons) {
        return [
            'inline_keyboard' => $buttons
        ];
    }
    
    /**
     * Reply keyboard yaratish
     */
    public function createReplyKeyboard($buttons, $resize = true, $oneTime = false) {
        return [
            'keyboard' => $buttons,
            'resize_keyboard' => $resize,
            'one_time_keyboard' => $oneTime
        ];
    }
    
    /**
     * API request
     */
    private function request($method, $data = []) {
        $url = $this->apiUrl . $method;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($result, true);
    }
    
    /**
     * Webhook o'rnatish
     */
    public function setWebhook($url) {
        return $this->request('setWebhook', ['url' => $url]);
    }
    
    /**
     * Webhook o'chirish
     */
    public function deleteWebhook() {
        return $this->request('deleteWebhook');
    }
    
    /**
     * Mijozni topish telegram_id bo'yicha
     */
    public function findCustomerByTelegramId($telegramId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM customers 
            WHERE telegram_id = ? 
            LIMIT 1
        ");
        $stmt->bind_param("s", $telegramId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }
    
    /**
     * Mijoz telegram_id ni yangilash
     */
    public function updateCustomerTelegramId($customerId, $telegramId, $username = null) {
        $stmt = $this->conn->prepare("
            UPDATE customers 
            SET telegram_id = ?, telegram_username = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ssi", $telegramId, $username, $customerId);
        return $stmt->execute();
    }
    
    /**
     * Mijoz paketlarini olish
     */
    public function getCustomerPackages($customerId) {
        $stmt = $this->conn->prepare("
            SELECT 
                cp.*,
                p.name as package_name
            FROM customer_packages cp
            JOIN packages p ON cp.package_id = p.id
            WHERE cp.customer_id = ? AND cp.status = 'active'
            ORDER BY cp.purchase_date DESC
        ");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $packages = [];
        while ($row = $result->fetch_assoc()) {
            $packages[] = $row;
        }
        return $packages;
    }
    
    /**
     * Mijoz bookinglarini olish
     */
    public function getCustomerBookings($customerId, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT 
                b.*,
                ts.slot_date,
                ts.slot_time,
                p.name as package_name
            FROM bookings b
            JOIN time_slots ts ON b.time_slot_id = ts.id
            JOIN customer_packages cp ON b.customer_package_id = cp.id
            JOIN packages p ON cp.package_id = p.id
            WHERE b.customer_id = ? AND b.status != 'cancelled'
            ORDER BY ts.slot_date DESC, ts.slot_time DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $customerId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        return $bookings;
    }
    
    /**
     * Yaqin bookinglarni olish (eslatma uchun)
     */
    public function getUpcomingBookings($hoursAhead = 1) {
        $targetTime = date('Y-m-d H:i:s', strtotime("+{$hoursAhead} hours"));
        
        $stmt = $this->conn->prepare("
            SELECT 
                b.*,
                c.telegram_id,
                c.full_name as customer_name,
                ts.slot_date,
                ts.slot_time,
                p.name as package_name
            FROM bookings b
            JOIN customers c ON b.customer_id = c.id
            JOIN time_slots ts ON b.time_slot_id = ts.id
            JOIN customer_packages cp ON b.customer_package_id = cp.id
            JOIN packages p ON cp.package_id = p.id
            WHERE b.status = 'scheduled'
            AND c.telegram_id IS NOT NULL
            AND b.notification_sent = 0
            AND CONCAT(ts.slot_date, ' ', ts.slot_time) <= ?
            AND CONCAT(ts.slot_date, ' ', ts.slot_time) > NOW()
        ");
        $stmt->bind_param("s", $targetTime);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        return $bookings;
    }
    
    /**
     * Booking notification yuborilganligini belgilash
     */
    public function markNotificationSent($bookingId) {
        $stmt = $this->conn->prepare("
            UPDATE bookings 
            SET notification_sent = 1 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $bookingId);
        return $stmt->execute();
    }
    
    /**
     * Formatters
     */
    public function formatDate($date) {
        return date('d.m.Y', strtotime($date));
    }
    
    public function formatTime($time) {
        return substr($time, 0, 5);
    }
    
    public function formatMoney($amount) {
        return number_format($amount, 0, ',', ' ') . ' so\'m';
    }
}

// Global bot instance
$telegramBot = new TelegramBot(TELEGRAM_BOT_TOKEN, $conn);
?>