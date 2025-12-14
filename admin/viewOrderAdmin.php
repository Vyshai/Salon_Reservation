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

$order_id = isset($_GET['id']) ? $_GET['id'] : '';
$order = $orderObj->getOrderDetails($order_id);

if (!$order) {
    header("Location: manageOrders.php");
    exit();
}

$message = "";
$message_type = "";

// Handle staff assignment
if (isset($_POST['assign_staff'])) {
    $item_id = $_POST['item_id'];
    $staff_id = $_POST['staff_id'];
    
    if ($orderObj->assignStaffToItem($item_id, $staff_id)) {
        $message = "Staff assigned successfully!";
        $message_type = "success";
        // Reload order details
        $order = $orderObj->getOrderDetails($order_id);
    } else {
        $message = "Failed to assign staff.";
        $message_type = "error";
    }
}

// Handle complete service
if (isset($_POST['complete_service'])) {
    $item_id = $_POST['item_id'];
    
    if ($orderObj->completeOrderItem($item_id)) {
        // ADD NOTIFICATION
        require_once "../Notification.php";
        $notificationObj = new Notification();
        
        $order = $orderObj->getOrderDetails($order_id);
        
        $notificationObj->notifyUser(
            $order['user_id'],
            'order',
            'Service Completed',
            'A service in your order #' . $order_id . ' has been completed.',
            '../viewOrder.php?id=' . $order_id
        );
        $message = "Service marked as completed! Sale recorded.";
        $message_type = "success";
        // Reload order details
        $order = $orderObj->getOrderDetails($order_id);
    } else {
        $message = "Failed to complete service.";
        $message_type = "error";
    }
}

// Handle order approval
if (isset($_POST['approve_order'])) {
    if ($orderObj->updateOrderStatus($order_id, 'approved')) {
        $message = "Order approved successfully!";
        $message_type = "success";
        $order = $orderObj->getOrderDetails($order_id);
    }
}

// Handle order rejection
if (isset($_POST['reject_order'])) {
    if ($orderObj->updateOrderStatus($order_id, 'cancelled')) {
        $message = "Order rejected.";
        $message_type = "error";
        $order = $orderObj->getOrderDetails($order_id);
    }
}

// Get available staff
$available_staff = $orderObj->getAvailableStaff();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f9;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f8f9fa;
        }

        .status {
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
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
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
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
            font-size: 16px;
        }

        .section-title {
            margin: 30px 0 15px;
            color: #333;
            font-size: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        .item-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .item-pending {
            background: #fff3cd;
            color: #856404;
        }

        .item-in_progress {
            background: #d1ecf1;
            color: #0c5460;
        }

        .item-completed {
            background: #d4edda;
            color: #155724;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            margin: 2px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            text-decoration: none;
            display: inline-block;
        }

        select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 5px;
        }

        .actions-bar {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f8f9fa;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .print-btn {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            float: right;
        }

        @media print {
            .actions-bar, .back-link, .print-btn, .message {
                display: none;
            }
            body {
                background: white;
            }
        }

        .staff-badge {
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .total-row {
            background: #f8f9fa;
            font-weight: bold;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <button onclick="window.print()" class="print-btn">🖨️ Print</button>
        
        <div class="order-header">
            <div>
                <h1>Order #<?php echo $order['id']; ?></h1>
                <p style="color: #666; margin-top: 5px;">Created: <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
            </div>
            <span class="status status-<?php echo $order['status']; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
            </span>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <h3 class="section-title">📋 Customer Information</h3>
        <div class="order-info">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Customer Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['full_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Phone</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['phone']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Order Date</span>
                    <span class="info-value"><?php echo date('F d, Y', strtotime($order['order_date'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Order Time</span>
                    <span class="info-value"><?php echo date('h:i A', strtotime($order['order_time'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Amount</span>
                    <span class="info-value" style="color: #667eea; font-size: 20px;">
                        ₱<?php echo number_format($order['total_amount'], 2); ?>
                    </span>
                </div>
            </div>

            <?php if ($order['notes']): ?>
                <div style="margin-top: 15px; padding: 15px; background: #fff9e6; border-radius: 5px;">
                    <strong>Customer Notes:</strong><br>
                    <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                </div>
            <?php endif; ?>
        </div>

        <h3 class="section-title">🛍️ Order Items</h3>
        <table>
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Duration</th>
                    <th>Price</th>
                    <th>Assigned Staff</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['service_name']); ?></td>
                        <td><?php echo $item['duration']; ?> minutes</td>
                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                        <td>
                            <?php if ($item['staff_name']): ?>
                                <span class="staff-badge"><?php echo htmlspecialchars($item['staff_name']); ?></span>
                            <?php else: ?>
                                <span style="color: #999;">Not assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="item-status item-<?php echo $item['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($item['status'] == 'pending' && !$item['staff_id']): ?>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <select name="staff_id" required>
                                        <option value="">Select Staff</option>
                                        <?php foreach ($available_staff as $staff): ?>
                                            <option value="<?php echo $staff['id']; ?>">
                                                <?php echo htmlspecialchars($staff['full_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="assign_staff" class="btn btn-primary">Assign</button>
                                </form>
                            <?php elseif ($item['status'] == 'in_progress'): ?>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="complete_service" class="btn btn-success"
                                            onclick="return confirm('Mark this service as completed?')">
                                        Complete
                                    </button>
                                </form>
                            <?php elseif ($item['status'] == 'completed'): ?>
                                <span style="color: green;">✓ Done</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2">TOTAL</td>
                    <td colspan="4">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <?php if ($order['status'] == 'pending'): ?>
            <div class="actions-bar">
                <form method="POST" style="display: inline-block;">
                    <button type="submit" name="approve_order" class="btn btn-success"
                            onclick="return confirm('Approve this order?')">
                        ✓ Approve Order
                    </button>
                </form>
                <form method="POST" style="display: inline-block;">
                    <button type="submit" name="reject_order" class="btn btn-danger"
                            onclick="return confirm('Reject this order?')">
                        ✗ Reject Order
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <a href="manageOrders.php" class="back-link">← Back to Manage Orders</a>
    </div>
</body>
</html>