<?php
require_once 'bot.php';

$webhookUrl = 'https://reklama.kosonsoyliklar.uz/telegram/webhook.php';
$result = $telegramBot->setWebhook($webhookUrl);
var_dump($result);
?>