<?php

require_once "database.php";

class Notification extends Database
{
    public $user_id = "";
    public $type = ""; // 'order', 'appointment', 'user', 'system'
    public $title = "";
    public $message = "";
    public $link = "";
    public $is_read = 0;

    // Create new notification
    public function createNotification()
    {
        $sql = "INSERT INTO notifications(user_id, type, title, message, link, is_read, created_at) 
                VALUES(:user_id, :type, :title, :message, :link, :is_read, NOW())";
        
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":user_id", $this->user_id);
        $query->bindParam(":type", $this->type);
        $query->bindParam(":title", $this->title);
        $query->bindParam(":message", $this->message);
        $query->bindParam(":link", $this->link);
        $query->bindParam(":is_read", $this->is_read);

        return $query->execute();
    }

    // Send notification to all admins
    public function notifyAdmins($type, $title, $message, $link = "")
    {
        $sql = "SELECT id FROM users WHERE role IN ('admin', 'staff')";
        $query = $this->connect()->prepare($sql);
        
        if ($query->execute()) {
            $admins = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach ($admins as $admin) {
                $this->user_id = $admin['id'];
                $this->type = $type;
                $this->title = $title;
                $this->message = $message;
                $this->link = $link;
                $this->is_read = 0;
                $this->createNotification();
            }
            return true;
        }
        return false;
    }

    // Send notification to specific user
    public function notifyUser($user_id, $type, $title, $message, $link = "")
    {
        $this->user_id = $user_id;
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->link = $link;
        $this->is_read = 0;
        return $this->createNotification();
    }

    // Get user notifications
    public function getUserNotifications($user_id, $limit = 10)
    {
        $sql = "SELECT * FROM notifications 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":user_id", $user_id);
        $query->bindParam(":limit", $limit, PDO::PARAM_INT);
        
        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    // Get unread notification count
    public function getUnreadCount($user_id)
    {
        $sql = "SELECT COUNT(*) as count FROM notifications 
                WHERE user_id = :user_id AND is_read = 0";
        
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":user_id", $user_id);
        
        if ($query->execute()) {
            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        }
        return 0;
    }

    // Mark notification as read
    public function markAsRead($notification_id)
    {
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $notification_id);
        return $query->execute();
    }

    // Mark all user notifications as read
    public function markAllAsRead($user_id)
    {
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = :user_id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":user_id", $user_id);
        return $query->execute();
    }

    // Delete notification
    public function deleteNotification($notification_id)
    {
        $sql = "DELETE FROM notifications WHERE id = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $notification_id);
        return $query->execute();
    }

    // Delete old notifications (older than 30 days)
    public function deleteOldNotifications()
    {
        $sql = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $query = $this->connect()->prepare($sql);
        return $query->execute();
    }
}