<?php
    
    ini_set('session.save_path', getcwd() . '/sessions');
if (!is_dir('sessions')) {
    mkdir('sessions', 0700);
}
    
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'staff')) {
    header("Location: ../login.php");
    exit();
}

require_once "../Notification.php";
$notificationObj = new Notification();

$notifications = $notificationObj->getUserNotifications($_SESSION['user_id'], 50);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Notifications</title>
    <style>
        /* Add styling similar to other admin pages */
        .notification-list {
            background: white;
            padding: 20px;
            border-radius: 10px;
        }
        .notification-item {
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }
        .notification-item.unread {
            background: #e7f3ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>All Notifications</h1>
        
        <div class="notification-list">
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-item <?= $notif['is_read'] == 0 ? 'unread' : ''; ?>">
                    <h3><?= htmlspecialchars($notif['title']); ?></h3>
                    <p><?= htmlspecialchars($notif['message']); ?></p>
                    <small><?= date('M d, Y h:i A', strtotime($notif['created_at'])); ?></small>
                </div>
            <?php endforeach; ?>
        </div>
        
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>