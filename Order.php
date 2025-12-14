<?php

require_once "database.php";

class Order extends Database
{
    public $user_id = "";
    public $order_date = "";
    public $order_time = "";
    public $total_amount = 0;
    public $status = "pending";
    public $notes = "";

// Create new order
public function createOrder()
{
    try {
        $conn = $this->connect();
        
        $sql = "INSERT INTO orders(user_id, order_date, order_time, total_amount, status, notes, created_at) 
                VALUES(:user_id, :order_date, :order_time, :total_amount, :status, :notes, NOW())";
        
        $query = $conn->prepare($sql);
        $query->bindParam(":user_id", $this->user_id);
        $query->bindParam(":order_date", $this->order_date);
        $query->bindParam(":order_time", $this->order_time);
        $query->bindParam(":total_amount", $this->total_amount);
        $query->bindParam(":status", $this->status);
        $query->bindParam(":notes", $this->notes);

        if ($query->execute()) {
            // Get the last inserted ID
            $order_id = $conn->lastInsertId();
            
            if ($order_id && $order_id > 0) {
                error_log("Order created successfully: ID = " . $order_id);
                return $order_id;
            } else {
                error_log("Order inserted but lastInsertId returned: " . $order_id);
                // Try to get the order ID manually
                $getIdSql = "SELECT id FROM orders WHERE user_id = :user_id ORDER BY id DESC LIMIT 1";
                $getIdQuery = $conn->prepare($getIdSql);
                $getIdQuery->bindParam(":user_id", $this->user_id);
                $getIdQuery->execute();
                $result = $getIdQuery->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    error_log("Retrieved order ID manually: " . $result['id']);
                    return $result['id'];
                }
                
                return false;
            }
        } else {
            error_log("Order creation failed: " . print_r($query->errorInfo(), true));
            return false;
        }
    } catch (Exception $e) {
        error_log("Order creation exception: " . $e->getMessage());
        return false;
    }
}

    // Add item to order
public function addOrderItem($order_id, $service_id, $price, $quantity = 1)
{
    try {
        // Validate order_id
        if (!$order_id || $order_id <= 0) {
            error_log("Invalid order_id provided: " . $order_id);
            return false;
        }
        
        $conn = $this->connect();
        
        $sql = "INSERT INTO order_items(order_id, service_id, quantity, price, status) 
                VALUES(:order_id, :service_id, :quantity, :price, 'pending')";
        
        $query = $conn->prepare($sql);
        $query->bindParam(":order_id", $order_id, PDO::PARAM_INT);
        $query->bindParam(":service_id", $service_id, PDO::PARAM_INT);
        $query->bindParam(":quantity", $quantity, PDO::PARAM_INT);
        $query->bindParam(":price", $price);

        if ($query->execute()) {
            error_log("Order item added successfully: Order ID = $order_id, Service ID = $service_id, Price = $price");
            return true;
        } else {
            error_log("Failed to add order item: " . print_r($query->errorInfo(), true));
            return false;
        }
    } catch (Exception $e) {
        error_log("Add order item exception: " . $e->getMessage());
        return false;
    }
}

    // Assign staff to order item
    public function assignStaffToItem($item_id, $staff_id)
    {
        $sql = "UPDATE order_items SET staff_id=:staff_id, status='in_progress', start_time=NOW() 
                WHERE id=:item_id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":staff_id", $staff_id);
        $query->bindParam(":item_id", $item_id);

        return $query->execute();
    }

    // Complete order item and free staff
    public function completeOrderItem($item_id)
    {
        $sql = "UPDATE order_items SET status='completed', end_time=NOW() WHERE id=:item_id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":item_id", $item_id);

        if ($query->execute()) {
            // Check if all items in the order are completed
            $item = $this->getOrderItem($item_id);
            if ($item) {
                $this->checkAndCompleteOrder($item['order_id']);
                
                // Record sale
                $this->recordSale($item_id);
            }
            return true;
        }
        return false;
    }

    // Record sale when service is completed
    private function recordSale($item_id)
    {
        $sql = "INSERT INTO sales(order_id, staff_id, service_id, amount, sale_date, sale_time)
                SELECT order_id, staff_id, service_id, price, CURDATE(), CURTIME()
                FROM order_items WHERE id = :item_id";
        
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":item_id", $item_id);
        return $query->execute();
    }

    // Check if all order items are completed
    private function checkAndCompleteOrder($order_id)
    {
        $sql = "SELECT COUNT(*) as total, 
                       SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed
                FROM order_items WHERE order_id = :order_id";
        
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":order_id", $order_id);
        $query->execute();
        $result = $query->fetch();

        if ($result['total'] == $result['completed']) {
            $update = "UPDATE orders SET status='completed', completed_at=NOW() WHERE id=:order_id";
            $updateQuery = $this->connect()->prepare($update);
            $updateQuery->bindParam(":order_id", $order_id);
            $updateQuery->execute();
        }
    }

    // Get order item details
    public function getOrderItem($item_id)
    {
        $sql = "SELECT * FROM order_items WHERE id = :item_id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":item_id", $item_id);
        
        if ($query->execute()) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    // Get user orders
    public function getUserOrders($user_id, $filter = "all")
    {
        $sql = "SELECT o.*, 
                       (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                FROM orders o 
                WHERE o.user_id = :user_id";
        
        if ($filter == "pending") {
            $sql .= " AND o.status IN ('pending', 'approved', 'in_progress')";
        } elseif ($filter == "completed") {
            $sql .= " AND o.status = 'completed'";
        }
        
        $sql .= " ORDER BY o.created_at DESC, o.order_date DESC, o.order_time DESC";
        
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":user_id", $user_id);

        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    // Get order details with items
    public function getOrderDetails($order_id)
    {
        $sql = "SELECT o.*, u.full_name, u.email, u.phone
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.id = :order_id";
        
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":order_id", $order_id);
        
        if ($query->execute()) {
            $order = $query->fetch(PDO::FETCH_ASSOC);
            if ($order) {
                $order['items'] = $this->getOrderItems($order_id);
            }
            return $order;
        }
        return null;
    }

    // Get order items
    public function getOrderItems($order_id)
    {
        $sql = "SELECT oi.*, s.service_name, s.duration,
                       staff.full_name as staff_name
                FROM order_items oi
                JOIN services s ON oi.service_id = s.id
                LEFT JOIN users staff ON oi.staff_id = staff.id
                WHERE oi.order_id = :order_id";
        
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":order_id", $order_id);
        
        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    // Get all orders (for admin)
    public function getAllOrders($status = "")
    {
        $sql = "SELECT o.*, u.full_name, u.email, u.phone,
                       (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                FROM orders o
                JOIN users u ON o.user_id = u.id";
        
        if (!empty($status)) {
            $sql .= " WHERE o.status = :status";
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        $query = $this->connect()->prepare($sql);
        
        if (!empty($status)) {
            $query->bindParam(":status", $status);
        }

        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    // Update order status
    public function updateOrderStatus($order_id, $status)
    {
        $sql = "UPDATE orders SET status=:status WHERE id=:order_id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":status", $status);
        $query->bindParam(":order_id", $order_id);

        return $query->execute();
    }

    // Get available staff (not currently working)
    public function getAvailableStaff()
    {
        $sql = "SELECT u.id, u.full_name, u.email
                FROM users u
                WHERE u.role = 'staff' 
                AND u.is_available = 1
                AND u.id NOT IN (
                    SELECT DISTINCT staff_id 
                    FROM order_items 
                    WHERE status = 'in_progress' 
                    AND staff_id IS NOT NULL
                )
                ORDER BY u.full_name";
        
        $query = $this->connect()->prepare($sql);
        
        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    // Get staff sales report
    public function getStaffSales($staff_id, $period = 'daily', $date = null)
    {
        if (!$date) $date = date('Y-m-d');
        
        $sql = "SELECT 
                    DATE(sale_date) as sale_date,
                    COUNT(*) as total_services,
                    SUM(amount) as total_sales,
                    s.service_name,
                    COUNT(s.id) as service_count
                FROM sales
                JOIN services s ON sales.service_id = s.id
                WHERE staff_id = :staff_id";
        
        if ($period == 'daily') {
            $sql .= " AND sale_date = :date";
        } elseif ($period == 'weekly') {
            $sql .= " AND YEARWEEK(sale_date) = YEARWEEK(:date)";
        } elseif ($period == 'monthly') {
            $sql .= " AND YEAR(sale_date) = YEAR(:date) AND MONTH(sale_date) = MONTH(:date)";
        }
        
        $sql .= " GROUP BY service_name ORDER BY service_count DESC";
        
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":staff_id", $staff_id);
        $query->bindParam(":date", $date);
        
        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    // Get staff total sales
    public function getStaffTotalSales($staff_id, $period = 'daily', $date = null)
    {
        if (!$date) $date = date('Y-m-d');
        
        $sql = "SELECT 
                    COUNT(*) as total_services,
                    SUM(amount) as total_amount
                FROM sales
                WHERE staff_id = :staff_id";
        
        if ($period == 'daily') {
            $sql .= " AND sale_date = :date";
        } elseif ($period == 'weekly') {
            $sql .= " AND YEARWEEK(sale_date) = YEARWEEK(:date)";
        } elseif ($period == 'monthly') {
            $sql .= " AND YEAR(sale_date) = YEAR(:date) AND MONTH(sale_date) = MONTH(:date)";
        }
        
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":staff_id", $staff_id);
        $query->bindParam(":date", $date);
        
        if ($query->execute()) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return ['total_services' => 0, 'total_amount' => 0];
    }

    // Get dashboard KPIs
    public function getDashboardKPIs()
    {
        $conn = $this->connect();
        
        // Today's sales
        $today = "SELECT COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue 
                  FROM orders WHERE DATE(order_date) = CURDATE() AND status = 'completed'";
        $todayResult = $conn->query($today)->fetch();
        
        // This week's sales
        $week = "SELECT COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue 
                 FROM orders WHERE YEARWEEK(order_date) = YEARWEEK(CURDATE()) AND status = 'completed'";
        $weekResult = $conn->query($week)->fetch();
        
        // This month's sales
        $month = "SELECT COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue 
                  FROM orders WHERE YEAR(order_date) = YEAR(CURDATE()) 
                  AND MONTH(order_date) = MONTH(CURDATE()) AND status = 'completed'";
        $monthResult = $conn->query($month)->fetch();
        
        // Pending orders
        $pending = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
        $pendingResult = $conn->query($pending)->fetch();
        
        // Active staff
        $staff = "SELECT COUNT(*) as count FROM users WHERE role = 'staff' AND is_available = 1";
        $staffResult = $conn->query($staff)->fetch();
        
        return [
            'today' => $todayResult,
            'week' => $weekResult,
            'month' => $monthResult,
            'pending' => $pendingResult['count'],
            'active_staff' => $staffResult['count']
        ];
    }
}