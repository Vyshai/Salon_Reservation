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

$order_id = isset($_GET['id']) ? $_GET['id'] : '';
$order = $orderObj->getOrderDetails($order_id);

if (!$order || $order['user_id'] != $_SESSION['user_id']) {
    header("Location: myOrders.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; }
        .container { max-width: 800px; margin: 30px auto; background: white; padding: 40px; border-radius: 10px; }
        h1 { color: #333; margin-bottom: 20px; }
        .order-info { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .status { padding: 5px 15px; border-radius: 20px; font-weight: bold; display: inline-block; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        table th { background: #f8f9fa; font-weight: bold; }
        .back-link { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; }
        .print-btn { float: right; padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
        @media print { .back-link, .print-btn { display: none; } body { background: white; } }
    </style>
</head>
<body>
    <div class="container">
        <button onclick="window.print()" class="print-btn">🖨️ Print</button>
        <h1>Order #<?php echo $order['id']; ?></h1>
        
        <div class="order-info">
            <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
            <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($order['order_date'])); ?></p>
            <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($order['order_time'])); ?></p>
            <p><strong>Status:</strong> <span class="status status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></p>
            <?php if ($order['notes']): ?>
                <p><strong>Notes:</strong> <?php echo htmlspecialchars($order['notes']); ?></p>
            <?php endif; ?>
        </div>

        <h3>Order Items</h3>
        <table>
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Staff</th>
                    <th>Duration</th>
                    <th>Price</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['service_name']); ?></td>
                        <td><?php echo $item['staff_name'] ? htmlspecialchars($item['staff_name']) : 'Not assigned'; ?></td>
                        <td><?php echo $item['duration']; ?> min</td>
                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo ucfirst($item['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f8f9fa; font-weight: bold;">
                    <td colspan="3">TOTAL</td>
                    <td colspan="2">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <a href="myOrders.php" class="back-link">← Back to My Orders</a>
    </div>
</body>
</html>