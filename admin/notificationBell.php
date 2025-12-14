<?php
// This file should be included in the header of admin pages
require_once "../Notification.php";

$notificationObj = new Notification();
$unread_count = $notificationObj->getUnreadCount($_SESSION['user_id']);
$notifications = $notificationObj->getUserNotifications($_SESSION['user_id'], 5);
?>

<style>

.notification-bell {
    position: relative;
    display: inline-block;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 5px;
    transition: background 0.3s;
}

.notification-bell:hover {
    background: rgba(255, 255, 255, 0.2);
}

.bell-icon {
    font-size: 20px;
    color: white;
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 11px;
    font-weight: bold;
    min-width: 18px;
    text-align: center;
}

.notification-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    width: 350px;
    max-height: 400px;
    overflow-y: auto;
    z-index: 1000;
    margin-top: 10px;
}

.notification-dropdown.show {
    display: block;
}

.notification-header {
    padding: 15px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h3 {
    margin: 0;
    font-size: 16px;
    color: #333;
}

.mark-all-read {
    color: #667eea;
    font-size: 13px;
    cursor: pointer;
    text-decoration: none;
}

.mark-all-read:hover {
    text-decoration: underline;
}

.notification-item {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.3s;
    text-decoration: none;
    display: block;
    color: inherit;
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-item.unread {
    background: #e7f3ff;
}

.notification-item .title {
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.notification-item .message {
    font-size: 13px;
    color: #666;
    margin-bottom: 5px;
}

.notification-item .time {
    font-size: 11px;
    color: #999;
}

.notification-empty {
    padding: 40px 20px;
    text-align: center;
    color: #999;
}

.notification-footer {
    padding: 12px;
    text-align: center;
    border-top: 1px solid #ddd;
}

.notification-footer a {
    color: #667eea;
    text-decoration: none;
    font-size: 14px;
    font-weight: bold;
}

.notification-type-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    margin-bottom: 5px;
}

.type-order {
    background: #fff3cd;
    color: #856404;
}

.type-appointment {
    background: #d1ecf1;
    color: #0c5460;
}

.type-user {
    background: #d4edda;
    color: #155724;
}

.type-system {
    background: #e2e3e5;
    color: #383d41;
}
</style>

<div class="notification-bell" onclick="toggleNotifications()">
    <span class="bell-icon">Notifications</span>
    <?php if ($unread_count > 0): ?>
        <span class="notification-badge"><?= $unread_count > 9 ? '9+' : $unread_count; ?></span>
    <?php endif; ?>
    
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
            <h3>Notifications</h3>
            <?php if ($unread_count > 0): ?>
                <a href="javascript:void(0)" class="mark-all-read" onclick="markAllRead(event)">Mark all as read</a>
            <?php endif; ?>
        </div>
        
        <div class="notification-list">
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notification): ?>
                    <a href="<?= $notification['link'] ? $notification['link'] : 'javascript:void(0)'; ?>" 
                       class="notification-item <?= $notification['is_read'] == 0 ? 'unread' : ''; ?>"
                       onclick="markAsRead(<?= $notification['id']; ?>)">
                        <span class="notification-type-badge type-<?= $notification['type']; ?>">
                            <?= ucfirst($notification['type']); ?>
                        </span>
                        <div class="title"><?= htmlspecialchars($notification['title']); ?></div>
                        <div class="message"><?= htmlspecialchars($notification['message']); ?></div>
                        <div class="time"><?= timeAgo($notification['created_at']); ?></div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="notification-empty">
                    <p>No notifications</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (count($notifications) > 0): ?>
            <div class="notification-footer">
                <a href="notifications.php">View All Notifications</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('show');
}

function markAsRead(notificationId) {
    fetch('markNotificationRead.php?id=' + notificationId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
}

function markAllRead(event) {
    event.preventDefault();
    event.stopPropagation();
    
    fetch('markAllNotificationsRead.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const bell = document.querySelector('.notification-bell');
    const dropdown = document.getElementById('notificationDropdown');
    
    if (!bell.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

// Prevent dropdown from closing when clicking inside
document.getElementById('notificationDropdown').addEventListener('click', function(event) {
    event.stopPropagation();
});
</script>

<?php
// Helper function for time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $time);
    }
}
?>