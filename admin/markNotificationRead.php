<?php
    
    ini_set('session.save_path', getcwd() . '/sessions');
if (!is_dir('sessions')) {
    mkdir('sessions', 0700);
}
    
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

require_once "../Notification.php";

$notificationObj = new Notification();

if (isset($_GET['id'])) {
    $notification_id = trim(htmlspecialchars($_GET['id']));
    
    if ($notificationObj->markAsRead($notification_id)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark as read']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No notification ID provided']);
}