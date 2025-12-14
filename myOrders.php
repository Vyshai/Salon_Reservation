<?php
    
    ini_set('session.save_path', getcwd() . '/sessions');
if (!is_dir('sessions')) {
    mkdir('sessions', 0700);
}
    
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "Order.php";
$orderObj = new Order();

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$orders = $orderObj->getUserOrders($_SESSION['user_id'], $filter);

// Handle cancellation
if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
    $order_id = trim(htmlspecialchars($_GET['id']));
    $orderObj->updateOrderStatus($order_id, 'cancelled');
    header("Location: myOrders.php?filter=$filter");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f9;
        }

        .header {
            background: linear-gradient(135deg, #764ba2 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            justify-content: center;
            flex-wrap: wrap;
            background: white;
            padding: 15px;
            border-radius: 10px;
        }

        .filter-tabs a {
            padding: 10px 20px;
            background: #f8f9fa;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .filter-tabs a.active {
            background: linear-gradient(135deg, #764ba2 0%, #764ba2 100%);
            color: white;
        }

        .filter-tabs a:hover {
            background: #e9ecef;
        }

        .filter-tabs a.active:hover {
            background: linear-gradient(135deg, #764ba2 0%, #764ba2 100%);
        }

        .orders-grid {
            display: grid;
            gap: 20px;
        }

        .order-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }

        .order-id {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }

        .status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-in_progress {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            color: #666;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .info-value {
            color: #333;
            font-weight: bold;
        }

        .order-items {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }

        .order-items h4 {
            margin-bottom: 10px;
            color: #333;
        }

        .item {
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .item:last-child {
            border-bottom: none;
        }

        .order-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view {
            background: #764ba2;
            color: white;
        }

        .btn-cancel {
            background: #dc3545;
            color: white;
        }

        .no-orders {
            background: white;
            padding: 50px;
            border-radius: 10px;
            text-align: center;
            color: #999;
        }

        .btn-new-order {
            display: inline-block;
            margin-top: 20px;
            padding: 15px 30px;
            background: linear-gradient(135deg, #764ba2 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <h1>My Orders</h1>
            <div class="nav-links">
                <span>Welcome, <?php echo $_SESSION['full_name']; ?>!</span>
                <a href="index.php">Home</a>
                <a href="createOrder.php">New Order</a>
                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
                    <a href="admin/dashboard.php">Dashboard</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="filter-tabs">
            <a href="?filter=all" class="<?php echo $filter == 'all' ? 'active' : ''; ?>">All Orders</a>
            <a href="?filter=pending" class="<?php echo $filter == 'pending' ? 'active' : ''; ?>">Pending/In Progress</a>
            <a href="?filter=completed" class="<?php echo $filter == 'completed' ? 'active' : ''; ?>">Completed</a>
        </div>

        <?php if (count($orders) > 0): ?>
            <div class="orders-grid">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id">Order #<?php echo $order['id']; ?></div>
                            <span class="status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                            </span>
                        </div>

                        <div class="order-info">
                            <div class="info-item">
                                <span class="info-label">Date</span>
                                <span class="info-value"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Time</span>
                                <span class="info-value"><?php echo date('h:i A', strtotime($order['order_time'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Services</span>
                                <span class="info-value"><?php echo $order['item_count']; ?> service(s)</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Total</span>
                                <span class="info-value" style="color: #667eea; font-size: 20px;">
                                    ₱<?php echo number_format($order['total_amount'], 2); ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($order['notes']): ?>
                            <div style="background: #fff9e6; padding: 10px; border-radius: 5px; margin-top: 10px;">
                                <strong>Notes:</strong> <?php echo htmlspecialchars($order['notes']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="order-actions">
                            <a href="viewOrder.php?id=<?php echo $order['id']; ?>" class="btn btn-view">View Details</a>
                            <?php if ($order['status'] == 'pending'): ?>
                                <a href="?action=cancel&id=<?php echo $order['id']; ?>&filter=<?php echo $filter; ?>" 
                                   class="btn btn-cancel"
                                   onclick="return confirm('Are you sure you want to cancel this order?')">
                                    Cancel Order
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-orders">
                <h2>No orders found</h2>
                <p>You haven't placed any orders yet.</p>
                <a href="createOrder.php" class="btn-new-order">Create Your First Order</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>