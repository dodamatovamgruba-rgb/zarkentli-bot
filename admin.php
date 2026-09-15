<?php
// Admin Authentication va Session Boshlash
session_start();

// Konfiguratsiya
require_once __DIR__ . '/config.php';

// Login tekshirish
function checkLogin() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: admin.php?action=login');
        exit;
    }
}

function checkRole($requiredRole = 'admin') {
    $admins = loadAdmins();
    $currentAdmin = $_SESSION['admin_username'] ?? '';
    
    if (!isset($admins[$currentAdmin]) || 
        ($requiredRole === 'superadmin' && $admins[$currentAdmin]['role'] !== 'superadmin')) {
        header('Location: admin.php?page=dashboard');
        exit;
    }
}

// Login sahifasi
if (isset($_GET['action']) && $_GET['action'] == 'login') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $admins = loadAdmins();
        
        if (isset($admins[$username]) && $admins[$username]
['password'] == $password) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_role'] = $admins[$username]['role'];
            
            $admins[$username]['last_login'] = time();
            saveAdmins($admins);
            logActivity(0, 'admin_login', "Admin $username panelga kirdi");
            
            header('Location: admin.php');
            exit;
        } else {
            $error = "Noto'g'ri login yoki parol!";
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="uz">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - Shpion iBot</title>
        <style>
            :root {
                --primary: #667eea;
                --primary-dark: #5a6fd8;
                --secondary: #764ba2;
                --success: #10b981;
                --danger: #ef4444;
                --warning: #f59e0b;
                --dark: #1f2937;
                --light: #f8fafc;
                --gray: #6b7280;
                --gray-light: #9ca3af;
                --white: #ffffff;
                --shadow: 0 10px 25px rgba(0,0,0,0.1);
                --radius: 12px;
            }
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
                background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 1rem;
                line-height: 1.6;
            }
            
            .login-container {
                background: var(--white);
                padding: 2.5rem;
                border-radius: var(--radius);
                box-shadow: var(--shadow);
                width: 100%;
                max-width: 400px;
                position: relative;
                overflow: hidden;
            }
            
            .login-container::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, var(--primary), var(--secondary));
            }
            
            .logo {
                text-align: center;
                margin-bottom: 2rem;
            }
            
            .logo-icon {
                width: 64px;
                height: 64px;
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1rem;
                color: var(--white);
                font-size: 1.5rem;
            }
            
            .logo h1 {
                color: var(--dark);
                font-size: 1.75rem;
                font-weight: 700;
                margin-bottom: 0.25rem;
            }
            
            .logo p {
                color: var(--gray);
                font-size: 0.875rem;
            }
            
            .form-group {
                margin-bottom: 1.5rem;
            }
            
            .form-label {
                display: block;
                margin-bottom: 0.5rem;
                color: var(--dark);
                font-weight: 600;
                font-size: 0.875rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .form-input {
                width: 100%;
                padding: 0.875rem 1rem;
                border: 2px solid #e5e7eb;
                border-radius: var(--radius);
                font-size: 1rem;
                transition: all 0.3s ease;
                background: var(--white);
            }
            
            .form-input:focus {
                outline: none;
                border-color: var(--primary);
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            }
            
            .btn {
                width: 100%;
                padding: 0.875rem 1rem;
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                color: var(--white);
                border: none;
                border-radius: var(--radius);
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            }
            
            .error {
                background: #fef2f2;
                color: var(--danger);
                padding: 0.875rem 1rem;
                border-radius: var(--radius);
                margin-bottom: 1.5rem;
                border: 1px solid #fecaca;
                font-size: 0.875rem;
                text-align: center;
            }
            
            @media (max-width: 480px) {
                .login-container {
                    padding: 2rem 1.5rem;
                }
                
                .logo h1 {
                    font-size: 1.5rem;
                }
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1>Admin Panel</h1>
                <p>Shpion iBot Boshqaruv Tizimi</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-input" required placeholder="Foydalanuvchi nomi">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" required placeholder="Parol">
                </div>
                
                <button type="submit" class="btn">Kirish</button>
            </form>
        </div>
        
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

// Logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    if (isset($_SESSION['admjn_username'])) {
        logActivity(0, 'admin_logout', "Admin {$_SESSION['admin_username']} paneldan chiqdi");
    }
    session_destroy();
    header('Location: admin.php?action=login');
    exit;
}

// Asosiy admin panel
checkLogin();

// Amallarni bajarish
$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Foydalanuvchi boshqaruvi
    if (isset($_POST['update_balance'])) {
        $chat_id = $_POST['chat_id'];
        $balance = $_POST['balance'];
        if (updateBalance($chat_id, $balance)) {
            $message = "Balans muvaffaqiyatli yangilandi!";
            logActivity(0, 'balance_update', "Foydalanuvchi $chat_id balansi $balance so'mga yangilandi");
        } else {
            $message = "Balans yangilashda xatolik!";
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['ban_user'])) {
        $chat_id = $_POST['chat_id'];
        if (banUser($chat_id)) {
            $message = "Foydalanuvchi bloklandi!";
            logActivity(0, 'user_ban', "Foydalanuvchi $chat_id bloklandi");
        } else {
            $message = "Bloklashda xatolik!";
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['unban_user'])) {
        $chat_id = $_POST['chat_id'];
        if (unbanUser($chat_id)) {
            $message = "Blok olindi!";
            logActivity(0, 'user_unban', "Foydalanuvchi $chat_id blokdan chiqarildi");
        } else {
            $message = "Blok ochishda xatolik!";
            $message_type = 'error';
        }
    }
    
    // Kanal qo'shish
    if (isset($_POST['add_channel'])) {
        $username = $_POST['channel_username'] ?? '';
        $name = $_POST['channel_name'] ?? '';
        $url = $_POST['channel_url'] ?? '';
        $required = isset($_POST['channel_required']);
        
        if (addChannel($username, $name, $url, $required)) {
            $message = "Kanal muvaffaqiyatli qo'shildi!";
            logActivity(0, 'channel_added', "Kanal $username qo'shildi");
        } else {
            $message = "Kanal qo'shishda xatolik!";
            $message_type = 'error';
        }
    }
    
    // Kanal o'chirish
    if (isset($_POST['remove_channel'])) {
        $username = $_POST['channel_username'] ?? '';
        if (removeChannel($username)) {
            $message = "Kanal muvaffaqiyatli o'chirildi!";
            logActivity(0, 'channel_removed', "Kanal $username o'chirildi");
        } else {
            $message = "Kanal o'chirishda xatolik!";
            $message_type = 'error';
        }
    }
    
    // Admin qo'shish
    if (isset($_POST['add_admin'])) {
        checkRole('superadmin');
        $username = $_POST['admin_username'] ?? '';
        $password = $_POST['admin_password'] ?? '';
        $role = $_POST['admin_role'] ?? 'admin';
        
        if (addAdmin($username, $password, $role)) {
            $message = "Yangi admin muvaffaqiyatli qo'shildi!";
            logActivity(0, 'admin_added', "Yangi admin $username qo'shildi");
        } else {
            $message = "Admin qo'shishda xatolik!";
            $message_type = 'error';
        }
    }
    
    // Admin o'chirish
    if (isset($_POST['remove_admin'])) {
        checkRole('superadmin');
        $username = $_POST['username'] ?? '';
        if (removeAdmin($username)) {
            $message = "Admin muvaffaqiyatli o'chirildi!";
            logActivity(0, 'admin_removed', "Admin $username o'chirildi");
        } else {
            $message = "Admin o'chirishda xatolik!";
            $message_type = 'error';
        }
    }
    
    // Xabar yuborish
    if (isset($_POST['send_message'])) {
        $message_text = $_POST['message_text'] ?? '';
        $message_type_msg = $_POST['message_type'] ?? 'all';
        
        if (empty($message_text)) {
            $message = "Xabar matni bo'sh bo'lishi mumkin emas!";
            $message_type = 'error';
        } else {
            try {
                $pdo = getDB();
                
                if ($message_type_msg === 'all') {
                    $stmt = $pdo->query("SELECT chat_id FROM users WHERE is_banned = 0");
                } elseif ($message_type_msg === 'active') {
                    $stmt = $pdo->prepare("SELECT chat_id FROM users WHERE is_banned = 0 AND last_activity >= ?");
                    $stmt->execute([strtotime('-7 days')]);
                } elseif ($message_type_msg === 'banned') {
                    $stmt = $pdo->query("SELECT chat_id FROM users WHERE is_banned = 1");
                } else {
                    $message = "Noto'g'ri foydalanuvchi turi!";
                    $message_type = 'error';
                    exit;
                }
                
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $chat_ids = array_column($users, 'chat_id');
                
                if (empty($chat_ids)) {
                    $message = "Xabar yuborish uchun foydalanuvchilar topilmadi!";
                    $message_type = 'error';
                } else {
                    // Xabarni darrov yuborish
                    $result = sendBulkMessage($chat_ids, $message_text, 1);
                    
                    $message = "Xabar yuborish yakunlandi! Jami: {$result['total']}, Muvaffaqiyatli: {$result['sent']}, Xatolik: {$result['failed']}";
                    logActivity(0, 'bulk_message', "{$result['total']} foydalanuvchiga xabar yuborildi. Muvaffaqiyatli: {$result['sent']}, Xatolik: {$result['failed']}");
                }
            } catch (PDOException $e) {
                $message = "Xatolik: " . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Sahifa parametrlari
$page = $_GET['page'] ?? 'dashboard';
$stats = getStats();
$users = getUsers();
$channels = loadChannels();
$admins = loadAdmins();

// Loglar sahifasi uchun
$log_page = isset($_GET['log_page']) ? max(1, intval($_GET['log_page'])) : 1;
$logs_per_page = 50;
$log_offset = ($log_page - 1) * $logs_per_page;
$activity_logs = getActivityLogs($logs_per_page, $log_offset);
$total_logs = count(getActivityLogs(1000, 0));
$total_log_pages = ceil($total_logs / $logs_per_page);

// Foydalanuvchilar sahifasi uchun
$user_page = isset($_GET['user_page']) ? max(1, intval($_GET['user_page'])) : 1;
$users_per_page = 20;
$user_offset = ($user_page - 1) * $users_per_page;
$user_search = $_GET['search'] ?? '';
$users = getUsers($users_per_page, $user_offset, $user_search);
$total_users = count(getUsers(1000, 0, $user_search));
$total_user_pages = ceil($total_users / $users_per_page);

// Grafika ma'lumotlari
$chart_data = getChartData(30);
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Shpion iBot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a6fd8;
            --secondary: #764ba2;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark: #1f2937;
            --dark-light: #374151;
            --light: #f8fafc;
            --gray: #6b7280;
            --gray-light: #9ca3af;
            --white: #ffffff;
            --sidebar-width: 280px;
            --header-height: 70px;
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
            --radius: 8px;
            --radius-lg: 12px;
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--light);
            color: var(--dark);
            line-height: 1.6;
            overflow-x: hidden;
            font-size: 14px;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            height: var(--header-height);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            box-shadow: var(--shadow);
        }
        
        .menu-toggle {
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.25rem;
            cursor: pointer;
            display: block;
        }
        
        .header h1 {
            font-size: 1.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .user-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.4rem 0.8rem;
            border-radius: var(--radius);
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: var(--white);
            text-decoration: none;
            padding: 0.4rem 0.8rem;
            border-radius: var(--radius);
            font-size: 0.8rem;
            transition: var(--transition);
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* Sidebar */
        .sidebar {
            background: var(--white);
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            position: fixed;
            top: var(--header-height);
            left: -100%;
            box-shadow: var(--shadow);
            overflow-y: auto;
            z-index: 999;
            transition: var(--transition);
        }
        
        .sidebar.active {
            left: 0;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-item {
            margin: 0.25rem 0.5rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--gray);
            text-decoration: none;
            border-radius: var(--radius);
            transition: var(--transition);
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .nav-link:hover {
            background: var(--light);
            color: var(--primary);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--white);
            box-shadow: var(--shadow);
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }
        
        /* Main Content */
        .content {
            padding: calc(var(--header-height) + 1rem) 1rem 1rem;
            min-height: 100vh;
            transition: var(--transition);
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: var(--white);
            padding: 1rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary);
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-card h3 {
            color: var(--gray);
            font-size: 0.75rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        /* Tables */
        .table-container {
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.85rem;
        }
        
        .table th {
            background: var(--light);
            font-weight: 600;
            color: var(--gray);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tr:hover {
            background: var(--light);
        }
        
        /* Forms */
        .form-container {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }
        
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: var(--radius);
            font-size: 0.9rem;
            transition: var(--transition);
        }
        
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: var(--radius);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }
        
        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
        }
        
        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }
        
        .btn-success {
            background: var(--success);
            color: var(--white);
        }
        
        .btn-warning {
            background: var(--warning);
            color: var(--white);
        }
        
        .btn-info {
            background: var(--info);
            color: var(--white);
        }
        
        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        /* Messages */
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius);
            font-weight: 500;
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        /* Charts */
        .chart-container {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            height: 300px;
        }
        
        /* Search */
        .search-box {
            background: var(--white);
            padding: 1rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }
        
        .search-form {
            display: flex;
            gap: 0.5rem;
        }
        
        .search-input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: var(--radius);
            font-size: 0.9rem;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        
        .page-link {
            padding: 0.5rem 0.75rem;
            background: var(--white);
            border: 1px solid #e5e7eb;
            border-radius: var(--radius);
            text-decoration: none;
            color: var(--dark);
            font-size: 0.85rem;
            transition: var(--transition);
        }
        
        .page-link:hover,
        .page-link.active {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }
        
        /* Mobile optimizations */
        @media (min-width: 768px) {
            body {
                font-size: 16px;
            }
            
            .header {
                padding: 0 2rem;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .content {
                padding: calc(var(--header-height) + 2rem) 2rem 2rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1.5rem;
            }
            
            .form-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
            
            .table {
                min-width: auto;
            }
            
            .table th,
            .table td {
                padding: 1rem;
                font-size: 0.9rem;
            }
        }
        
        @media (min-width: 1024px) {
            .sidebar {
                left: 0;
            }
            
            .content {
                margin-left: var(--sidebar-width);
            }
            
            .menu-toggle {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        
        <h1>
            <i class="fas fa-user-secret"></i>
            Shpion iBot Admin
        </h1>
        
        <div class="user-info">
            <span class="user-badge">
                <i class="fas fa-user-shield"></i>
                <?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?>
            </span>
            <a href="admin.php?action=logout" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="admin.php?page=dashboard" class="nav-link <?= $page == 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
            
            <div class="nav-item">
                <a href="admin.php?page=users" class="nav-link <?= $page == 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    Foydalanuvchilar
                </a>
            </div>
            
            <div class="nav-item">
                <a href="admin.php?page=messages" class="nav-link <?= $page == 'messages' ? 'active' : '' ?>">
                    <i class="fas fa-paper-plane"></i>
                    Xabar Yuborish
                </a>
            </div>
            
            <div class="nav-item">
                <a href="admin.php?page=channels" class="nav-link <?= $page == 'channels' ? 'active' : '' ?>">
                    <i class="fas fa-broadcast-tower"></i>
                    Kanallar
                </a>
            </div>
            
            <div class="nav-item">
                <a href="admin.php?page=keys" class="nav-link <?= $page == 'keys' ? 'active' : '' ?>">
                    <i class="fas fa-key"></i>
                    Kalitlar
                </a>
            </div>
            
            <div class="nav-item">
                <a href="admin.php?page=logs" class="nav-link <?= $page == 'logs' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i>
                    Loglar
                </a>
            </div>
            
            <?php if (($_SESSION['admin_role'] ?? '') === 'superadmin'): ?>
            <div class="nav-item">
                <a href="admin.php?page=admins" class="nav-link <?= $page == 'admins' ? 'active' : '' ?>">
                    <i class="fas fa-user-shield"></i>
                    Adminlar
                </a>
            </div>
            <?php endif; ?>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="content">
        <?php if (!empty($message)): ?>
            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php
        // Sahifa kontentlari
        switch($page) {
            case 'dashboard':
                ?>
                <div class="page-title">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><i class="fas fa-users"></i> Jami Foydalanuvchilar</h3>
                        <div class="stat-number"><?= number_format($stats['total_users']) ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3><i class="fas fa-user-check"></i> Bugun Faol</h3>
                        <div class="stat-number"><?= number_format($stats['today_active']) ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3><i class="fas fa-ban"></i> Bloklanganlar</h3>
                        <div class="stat-number"><?= number_format($stats['banned_users']) ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3><i class="fas fa-key"></i> Faol Kalitlar</h3>
                        <div class="stat-number"><?= number_format($stats['active_keys']) ?></div>
                    </div>
                </div>

                <!-- Xizmatlar Statistikasi -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><i class="fas fa-map-marker-alt"></i> Lokatsiyalar</h3>
                        <div class="stat-number"><?= number_format($stats['locations_sent']) ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3><i class="fas fa-camera"></i> Rasmlar</h3>
                        <div class="stat-number"><?= number_format($stats['photos_sent']) ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3><i class="fas fa-video"></i> Videolar</h3>
                        <div class="stat-number"><?= number_format($stats['videos_sent']) ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3><i class="fas fa-mobile-alt"></i> Orqa Kamera</h3>
                        <div class="stat-number"><?= number_format($stats['backcamera_videos']) ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3><i class="fas fa-phone"></i> Raqamlar</h3>
                        <div class="stat-number"><?= number_format($stats['phone_numbers']) ?></div>
                    </div>
                </div>

                <!-- Grafiklar -->
                <div class="form-grid">
                    <div class="chart-container">
                        <canvas id="usersChart"></canvas>
                    </div>
                    <div class="chart-container">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>

                <!-- So'nggi Faollik -->
                <div class="form-container">
                    <h3 style="margin-bottom: 1.5rem; color: var(--dark);">
                        <i class="fas fa-history"></i> So'nggi Faollik
                    </h3>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Foydalanuvchi</th>
                                    <th>Harakat</th>
                                    <th>Tafsilotlar</th>
                                    <th>Vaqt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recent_logs = getActivityLogs(10);
                                if (empty($recent_logs)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 2rem; color: var(--gray);">
                                            <i class="fas fa-history" style="font-size: 2rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                            Hozircha faollik loglari mavjud emas
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_logs as $log): ?>
                                    <tr>
                                        <td>
                                            <?php if ($log['chat_id'] == 0): ?>
                                                <strong>Admin</strong>
                                            <?php else: ?>
                                                <?= htmlspecialchars($log['first_name'] ?? 'Noma\'lum') ?>
                                                <br>
                                                <small style="color: var(--gray);">@<?= htmlspecialchars($log['username'] ?? 'yoq') ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?= htmlspecialchars($log['action_type']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($log['action_details'] ?? '') ?></td>
                                        <td><?= date('d.m.Y H:i', $log['created_at']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php
                break;
                
            case 'users':
                ?>
                <div class="page-title">
                    <i class="fas fa-users"></i>
                    Foydalanuvchilar Boshqaruvi
                </div>

                <!-- Qidiruv -->
                <div class="search-box">
                    <form method="GET" class="search-form">
                        <input type="hidden" name="page" value="users">
                        <input type="text" name="search" value="<?= htmlspecialchars($user_search) ?>" 
                               class="search-input" placeholder="ID, Ism yoki Username bo'yicha qidirish...">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Qidirish
                        </button>
                        <?php if ($user_search): ?>
                            <a href="admin.php?page=users" class="btn btn-danger">
                                <i class="fas fa-times"></i> Tozalash
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Foydalanuvchilar Jadvali -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Foydalanuvchi</th>
                                <th>Balans</th>
                                <th>Lokatsiya</th>
                                <th>Rasm</th>
                                <th>Video</th>
                                <th>Holat</th>
                                <th>Amallar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--gray);">
                                        <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                        <?= $user_search ? 'Qidiruv bo\'yicha foydalanuvchilar topilmadi' : 'Hozircha foydalanuvchilar mavjud emas' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: var(--white); display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                                <?= substr($user['first_name'] ?? 'U', 0, 1) ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600;"><?= htmlspecialchars($user['first_name'] ?? 'Noma\'lum') ?></div>
                                                <div style="font-size: 0.875rem; color: var(--gray);">
                                                    <?= htmlspecialchars($user['username'] ? '@' . $user['username'] : 'Yo\'q') ?>
                                                </div>
                                                <div style="font-size: 0.75rem; color: var(--gray-light);">ID: <?= $user['chat_id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <form method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
                                            <input type="hidden" name="chat_id" value="<?= $user['chat_id'] ?>">
                                            <input type="number" name="balance" value="<?= $user['balance'] ?>" 
                                                   style="width: 80px; padding: 0.5rem; border: 1px solid #ddd; border-radius: var(--radius); font-size: 0.8rem;">
                                            <button type="submit" name="update_balance" class="btn btn-primary btn-sm" title="Saqlash">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </form>
                                    </td>
                                    
                                    <td><?= number_format($user['locations_sent']) ?></td>
                                    <td><?= number_format($user['photos_sent']) ?></td>
                                    <td><?= number_format($user['videos_sent']) ?></td>
                                    <td><?= number_format($user['phone_numbers']) ?></td>
                                    
                                    <td>
                                        <?php if ($user['is_banned']): ?>
                                            <span class="badge badge-danger">
                                                <i class="fas fa-ban"></i> Bloklangan
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> Faol
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                            <?php if ($user['is_banned']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="chat_id" value="<?= $user['chat_id'] ?>">
                                                    <button type="submit" name="unban_user" class="btn btn-success btn-sm" title="Blok Ochish">
                                                        <i class="fas fa-lock-open"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="chat_id" value="<?= $user['chat_id'] ?>">
                                                    <button type="submit" name="ban_user" class="btn btn-danger btn-sm" title="Bloklash">
                                                        <i class="fas fa-lock"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_user_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_user_pages; $i++): ?>
                        <a href="admin.php?page=users&user_page=<?= $i ?>&search=<?= urlencode($user_search) ?>" 
                           class="page-link <?= $i == $user_page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                <?php
                break;
                
            case 'messages':
                ?>
                <div class="page-title">
                    <i class="fas fa-paper-plane"></i>
                    Xabar Yuborish
                </div>

                <!-- Xabar yuborish formasi -->
                <div class="form-container">
                    <h3 style="margin-bottom: 1.5rem; color: var(--dark);">
                        <i class="fas fa-envelope"></i> Yangi Xabar Yuborish
                    </h3>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Xabar Matni</label>
                            <textarea name="message_text" class="form-textarea" required 
                                      placeholder="Xabar matnini kiriting..." rows="6"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Qaysi Foydalanuvchilarga</label>
                            <select name="message_type" class="form-select" required>
                                <option value="all">Barcha Faol Foydalanuvchilar</option>
                                <option value="active">Faol Foydalanuvchilar (oxirgi 7 kun)</option>
                                <option value="banned">Bloklangan Foydalanuvchilar</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="send_message" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Xabarni Yuborish
                            </button>
                        </div>
                    </form>
                </div>
                <?php
                break;
                
            case 'channels':
                ?>
                <div class="page-title">
                    <i class="fas fa-broadcast-tower"></i>
                    Kanallar Boshqaruvi
                </div>

                <!-- Yangi Kanal Qo'shish -->
                <div class="form-container">
                    <h3 style="margin-bottom: 1.5rem; color: var(--dark);">
                        <i class="fas fa-plus-circle"></i> Yangi Kanal Qo'shish
                    </h3>
                    
                    <form method="POST" class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Kanal Username</label>
                            <input type="text" name="channel_username" class="form-input" required 
                                   placeholder="@Shpion_Pro" pattern="^@[a-zA-Z0-9_]+$">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Kanal Nomi</label>
                            <input type="text" name="channel_name" class="form-input" required 
                                   placeholder="Asosiy Kanal">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Kanal URL</label>
                            <input type="url" name="channel_url" class="form-input" required 
                                   placeholder="https://t.me/Shpion_Pro">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="channel_required" value="1">
                                Majburiy obuna
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="add_channel" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Kanal Qo'shish
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Mavjud Kanallar -->
                <div class="form-container">
                    <h3 style="margin-bottom: 1.5rem; color: var(--dark);">
                        <i class="fas fa-list"></i> Mavjud Kanallar
                    </h3>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Nomi</th>
                                    <th>URL</th>
                                    <th>Holat</th>
                                    <th>Qo'shilgan</th>
                                    <th>Amallar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($channels)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 2rem; color: var(--gray);">
                                            <i class="fas fa-broadcast-tower" style="font-size: 2rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                            Hozircha kanallar mavjud emas
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($channels as $username => $channel): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($username) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($channel['name']) ?></td>
                                        <td>
                                            <a href="<?= htmlspecialchars($channel['url']) ?>" target="_blank" style="color: var(--primary); text-decoration: none;">
                                                <i class="fas fa-external-link-alt"></i> Ochish
                                            </a>
                                        </td>
                                        <td>
                                            <?php if ($channel['required']): ?>
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-star"></i> Majburiy
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-info">
                                                    <i class="fas fa-star-half-alt"></i> Ixtiyoriy
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d.m.Y H:i', $channel['created_at']) ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="channel_username" value="<?= htmlspecialchars($username) ?>">
                                                <button type="submit" name="remove_channel" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Haqiqatan ham <?= htmlspecialchars($username) ?> kanalini o\'chirmoqchimisiz?')">
                                                    <i class="fas fa-trash"></i> O'chirish
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php
                break;
                
            case 'keys':
                ?>
                <div class="page-title">
                    <i class="fas fa-key"></i>
                    Faol Kalitlar
                </div>

                <?php
                $keys = loadKeys();
                $activeKeys = [];
                $now = time();

                foreach ($keys as $key => $data) {
                    if (isset($data['expires']) && $data['expires'] > $now) {
                        $activeKeys[$key] = $data;
                    }
                }
                ?>

                <div class="form-container">
                    <h3 style="margin-bottom: 1.5rem; color: var(--dark);">
                        <i class="fas fa-key"></i> Faol Kalitlar Ro'yxati
                    </h3>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kalit</th>
                                    <th>Foydalanuvchi ID</th>
                                    <th>Turi</th>
                                    <th>Yaratilgan</th>
                                    <th>Muddati</th>
                                    <th>Qolgan Vaqt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($activeKeys)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 2rem; color: var(--gray);">
                                            <i class="fas fa-key" style="font-size: 2rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                            Hozircha faol kalitlar mavjud emas
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($activeKeys as $key => $data): ?>
                                    <tr>
                                        <td>
                                            <code style="background: var(--light); padding: 0.5rem 0.75rem; border-radius: var(--radius); font-family: monospace;">
                                                <?= htmlspecialchars($key) ?>
                                            </code>
                                        </td>
                                        <td><?= $data['chat_id'] ?? 'Noma\'lum' ?></td>
                                        <td>
                                            <span class="badge" style="background: var(--info); color: var(--white);">
                                                <?= htmlspecialchars($data['type'] ?? 'Noma\'lum') ?>
                                            </span>
                                        </td>
                                        <td><?= date('Y-m-d H:i', $data['created'] ?? time()) ?></td>
                                        <td><?= date('Y-m-d H:i', $data['expires'] ?? time()) ?></td>
                                        <td>
                                            <span class="badge badge-warning">
                                                <?= isset($data['expires']) ? ceil(($data['expires'] - $now) / 3600) : 0 ?> soat
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Kalitlar Statistikasi -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><i class="fas fa-key"></i> Jami Faol Kalitlar</h3>
                        <div class="stat-number"><?= number_format(count($activeKeys)) ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3><i class="fas fa-clock"></i> O'rtacha Muddati</h3>
                        <div class="stat-number">24 soat</div>
                    </div>
                </div>
                <?php
                break;
                
            case 'logs':
                ?>
                <div class="page-title">
                    <i class="fas fa-history"></i>
                    Faollik Loglari
                </div>

                <div class="form-container">
                    <h3 style="margin-bottom: 1.5rem; color: var(--dark);">
                        <i class="fas fa-list"></i> So'nggi 1000 ta Harakat
                    </h3>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Foydalanuvchi</th>
                                    <th>Harakat</th>
                                    <th>Tafsilotlar</th>
                                    <th>Vaqt</th>
                                    <th>IP Manzil</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($activity_logs)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 2rem; color: var(--gray);">
                                            <i class="fas fa-history" style="font-size: 2rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                            Hozircha faollik loglari mavjud emas
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($activity_logs as $log): ?>
                                    <tr>
                                        <td>
                                            <?php if ($log['chat_id'] == 0): ?>
                                                <strong>Admin</strong>
                                            <?php else: ?>
                                                <?= htmlspecialchars($log['first_name'] ?? 'Noma\'lum') ?>
                                                <br>
                                                <small style="color: var(--gray);">@<?= htmlspecialchars($log['username'] ?? 'yoq') ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?= htmlspecialchars($log['action_type']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($log['action_details'] ?? '') ?></td>
                                        <td><?= date('d.m.Y H:i', $log['created_at']) ?></td>
                                        <td><code><?= htmlspecialchars($log['ip_address']) ?></code></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_log_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_log_pages; $i++): ?>
                        <a href="admin.php?page=logs&log_page=<?= $i ?>" 
                           class="page-link <?= $i == $log_page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                <?php
                break;
                
            case 'admins':
                if (($_SESSION['admin_role'] ?? '') === 'superadmin') {
                    ?>
                    <div class="page-title">
                        <i class="fas fa-user-shield"></i>
                        Adminlar Boshqaruvi
                    </div>

                    <!-- Yangi Admin Qo'shish -->
                    <div class="form-container">
                        <h3 style="margin-bottom: 1.5rem; color: var(--dark);">
                            <i class="fas fa-user-plus"></i> Yangi Admin Qo'shish
                        </h3>
                        
                        <form method="POST" class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <input type="text" name="admin_username" class="form-input" required 
                                       placeholder="admin_nomi">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Parol</label>
                                <input type="password" name="admin_password" class="form-input" required 
                                       placeholder="Parol">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Role</label>
                                <select name="admin_role" class="form-select" required>
                                    <option value="admin">Admin</option>
                                    <option value="superadmin">Super Admin</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="add_admin" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Admin Qo'shish
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Mavjud Adminlar -->
                    <div class="form-container">
                        <h3 style="margin-bottom: 1.5rem; color: var(--dark);">
                            <i class="fas fa-users-cog"></i> Mavjud Adminlar
                        </h3>
                        
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Yaratilgan</th>
                                        <th>Oxirgi Kirish</th>
                                        <th>Amallar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($admins)): ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center; padding: 2rem; color: var(--gray);">
                                                <i class="fas fa-user-shield" style="font-size: 2rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                                Hozircha adminlar mavjud emas
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($admins as $username => $admin): ?>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: var(--white); display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                                        <?= substr($username, 0, 1) ?>
                                                    </div>
                                                    <div>
                                                        <div style="font-weight: 600;"><?= htmlspecialchars($username) ?></div>
                                                        <?php if ($username === ($_SESSION['admin_username'] ?? '')): ?>
                                                            <small style="color: var(--primary);">(Joriy)</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($admin['role'] === 'superadmin'): ?>
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-crown"></i> Super Admin
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-user-tie"></i> Admin
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d.m.Y H:i', $admin['created_at']) ?></td>
                                            <td>
                                                <?php if ($admin['last_login']): ?>
                                                    <?= date('d.m.Y H:i', $admin['last_login']) ?>
                                                <?php else: ?>
                                                    <span style="color: var(--gray);">Hali kirgani yo'q</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($admin['role'] !== 'superadmin' && $username !== ($_SESSION['admin_username'] ?? '')): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                                                        <button type="submit" name="remove_admin" class="btn btn-danger btn-sm" 
                                                                onclick="return confirm('Haqiqatan ham <?= htmlspecialchars($username) ?> adminni o\'chirmoqchimisiz?')">
                                                            <i class="fas fa-trash"></i> O'chirish
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="color: var(--gray); font-size: 0.875rem;">O'chirib bo'lmaydi</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php
                }
                break;
                
            default:
                include 'pages/dashboard.php';
        }
        ?>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }
        
        // Auto-hide message after 5 seconds
        setTimeout(() => {
            const message = document.querySelector('.message');
            if (message) {
                message.style.display = 'none';
            }
        }, 5000);
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            if (window.innerWidth < 1024 && sidebar.classList.contains('active') && 
                !sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        });
        
        // Chart initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Users chart
            const usersCtx = document.getElementById('usersChart');
            if (usersCtx) {
                new Chart(usersCtx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode(array_column($chart_data['users'], 'date')) ?>,
                        datasets: [{
                            label: 'Yangi Foydalanuvchilar',
                            data: <?= json_encode(array_column($chart_data['users'], 'count')) ?>,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
            
            // Activity chart
            const activityCtx = document.getElementById('activityChart');
            if (activityCtx) {
                new Chart(activityCtx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode(array_column($chart_data['activity'], 'date')) ?>,
                        datasets: [{
                            label: 'Kunlik Faollik',
                            data: <?= json_encode(array_column($chart_data['activity'], 'count')) ?>,
                            backgroundColor: '#764ba2',
                            borderColor: '#764ba2',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>