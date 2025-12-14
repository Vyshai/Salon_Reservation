<?php
// Session configuration
ini_set('session.save_path', getcwd() . '/sessions');
if (!is_dir('sessions')) {
    mkdir('sessions', 0700);
}

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if user has admin or staff role
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'staff') {
    // Not authorized - redirect to home
    header("Location: ../index.php");
    exit();
}

require_once "../Order.php";
require_once "../Service.php";
require_once "../User.php";

$orderObj = new Order();
$serviceObj = new Service();
$userObj = new User();

// Get KPIs
$kpis = $orderObj->getDashboardKPIs();
$services = $serviceObj->viewServices();
$users = $userObj->getAllUsers();
$recent_orders = $orderObj->getAllOrders();
$recent_orders = array_slice($recent_orders, 0, 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-container {
            max-width: 1400px;
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

        .nav-links a, .nav-links span {
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
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #667eea;
            transition: transform 0.3s;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
        }

        .kpi-card.revenue {
            border-left-color: #28a745;
        }

        .kpi-card.orders {
            border-left-color: #ffc107;
        }

        .kpi-card.pending {
            border-left-color: #dc3545;
        }

        .kpi-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .kpi-card .number {
            color: #333;
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .kpi-card .label {
            color: #999;
            font-size: 12px;
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .quick-link {
            background: white;
            padding: 30px 20px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .quick-link:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .quick-link h3 {
            margin-top: 10px;
            color: #667eea;
        }

        .icon {
            font-size: 40px;
        }

        .section-title {
            margin: 40px 0 20px;
            color: #333;
            font-size: 24px;
        }

        .recent-orders {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        table th {
            background: #f8f9fa;
            font-weight: bold;
        }

        .status {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: bold;
            display: inline-block;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .print-btn {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            float: right;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <h1>📊 Admin Dashboard</h1>
            <div class="nav-links">
                <?php include 'notificationBell.php'; ?>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</span>
                <a href="../index.php">View Site</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h2 class="section-title">📊 Key Performance Indicators</h2>
        
        <!-- Today's KPIs -->
        <h3 style="margin: 20px 0 10px; color: #666;">Today's Performance</h3>
        <div class="kpi-grid">
            <div class="kpi-card revenue">
                <h3>Today's Revenue</h3>
                <div class="number">₱<?php echo number_format($kpis['today']['revenue'], 2); ?></div>
                <div class="label"><?php echo $kpis['today']['orders']; ?> completed orders</div>
            </div>
            <div class="kpi-card orders">
                <h3>Pending Orders</h3>
                <div class="number"><?php echo $kpis['pending']; ?></div>
                <div class="label">Awaiting approval</div>
            </div>
            <div class="kpi-card">
                <h3>Active Staff</h3>
                <div class="number"><?php echo $kpis['active_staff']; ?></div>
                <div class="label">Currently available</div>
            </div>
        </div>

        <!-- Weekly KPIs -->
        <h3 style="margin: 20px 0 10px; color: #666;">This Week's Performance</h3>
        <div class="kpi-grid">
            <div class="kpi-card revenue">
                <h3>Weekly Revenue</h3>
                <div class="number">₱<?php echo number_format($kpis['week']['revenue'], 2); ?></div>
                <div class="label"><?php echo $kpis['week']['orders']; ?> completed orders</div>
            </div>
        </div>

        <!-- Monthly KPIs -->
        <h3 style="margin: 20px 0 10px; color: #666;">This Month's Performance</h3>
        <div class="kpi-grid">
            <div class="kpi-card revenue">
                <h3>Monthly Revenue</h3>
                <div class="number">₱<?php echo number_format($kpis['month']['revenue'], 2); ?></div>
                <div class="label"><?php echo $kpis['month']['orders']; ?> completed orders</div>
            </div>
        </div>

        <h2 class="section-title">Quick Actions</h2>
        <div class="quick-links">
            <a href="manageOrders.php" class="quick-link">
                <div class="icon">📦</div>
                <h3>Manage Orders</h3>
            </a>
            <a href="manageServices.php" class="quick-link">
                <div class="icon">💅</div>
                <h3>Manage Services</h3>
            </a>
            <a href="manageUsers.php" class="quick-link">
                <div class="icon">👥</div>
                <h3>Manage Users & Staff</h3>
            </a>
            <a href="salesReport.php" class="quick-link">
                <div class="icon">📈</div>
                <h3>Sales Reports</h3>
            </a>
            <a href="staffSales.php" class="quick-link">
                <div class="icon">💰</div>
                <h3>Staff Sales</h3>
            </a>
            <a href="addService.php" class="quick-link">
                <div class="icon">➕</div>
                <h3>Add New Service</h3>
            </a>
        </div>

        <h2 class="section-title">Recent Orders</h2>
        <div class="recent-orders">
            <button onclick="window.print()" class="print-btn">🖨️ Print</button>
            <table>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                        <td><?php echo date('h:i A', strtotime($order['order_time'])); ?></td>
                        <td><?php echo $order['item_count']; ?> item(s)</td>
                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <span class="status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>