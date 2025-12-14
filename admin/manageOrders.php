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

require_once "../Order.php";
$orderObj = new Order();

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$orders = $orderObj->getAllOrders($status_filter);

// Handle order approval
if (isset($_POST['approve_order'])) {
    $order_id = $_POST['order_id'];
    $orderObj->updateOrderStatus($order_id, 'approved');

    // ADD NOTIFICATION
    require_once "../Notification.php";
    require_once "../EmailService.php";
    $notificationObj = new Notification();
    $emailService = new EmailService();
    
    $order = $orderObj->getOrderDetails($order_id);
    
    // Notify customer
    $notificationObj->notifyUser(
        $order['user_id'],
        'order',
        'Order Approved',
        'Your order #' . $order_id . ' has been approved!',
        '../viewOrder.php?id=' . $order_id
    );
    
    // Send email
    $emailService->sendOrderStatusUpdate(
        $order['email'],
        $order['full_name'],
        $order_id,
        'approved'
    );

    header("Location: manageOrders.php");
    exit();
}

// Handle staff assignment
if (isset($_POST['assign_staff'])) {
    $item_id = $_POST['item_id'];
    $staff_id = $_POST['staff_id'];
    $orderObj->assignStaffToItem($item_id, $staff_id);
    header("Location: manageOrders.php");
    exit();
}

// Handle complete service
if (isset($_POST['complete_service'])) {
    $item_id = $_POST['item_id'];
    $orderObj->completeOrderItem($item_id);
    header("Location: manageOrders.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; }
        .container { max-width: 1400px; margin: 30px auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { text-align: center; margin-bottom: 30px; color: #333; }
        .filter-tabs { display: flex; gap: 10px; margin-bottom: 25px; justify-content: center; flex-wrap: wrap; }
        .filter-tabs a { padding: 10px 20px; background: #f8f9fa; color: #333; text-decoration: none; border-radius: 5px; }
        .filter-tabs a.active { background: #667eea; color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th, table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        table th { background: #f8f9fa; font-weight: bold; }
        .btn { padding: 6px 12px; margin: 2px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; color: white; }
        .btn-approve { background: #28a745; }
        .btn-assign { background: #667eea; }
        .btn-complete { background: #17a2b8; }
        .status { padding: 5px 10px; border-radius: 4px; font-size: 13px; font-weight: bold; display: inline-block; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-in_progress { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .back-link { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Orders</h1>

        <div class="filter-tabs">
            <a href="?status=" class="<?php echo $status_filter == '' ? 'active' : ''; ?>">All</a>
            <a href="?status=pending" class="<?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="?status=approved" class="<?php echo $status_filter == 'approved' ? 'active' : ''; ?>">Approved</a>
            <a href="?status=in_progress" class="<?php echo $status_filter == 'in_progress' ? 'active' : ''; ?>">In Progress</a>
            <a href="?status=completed" class="<?php echo $status_filter == 'completed' ? 'active' : ''; ?>">Completed</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                        <td><?php echo date('h:i A', strtotime($order['order_time'])); ?></td>
                        <td><?php echo $order['item_count']; ?></td>
                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td><span class="status status-<?php echo $order['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></span></td>
                        <td>
                            <?php if ($order['status'] == 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="submit" name="approve_order" class="btn btn-approve">Approve</button>
                                </form>
                            <?php endif; ?>
                            <a href="viewOrderAdmin.php?id=<?php echo $order['id']; ?>" class="btn" style="background: #667eea;">View Details</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>