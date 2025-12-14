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
require_once "../User.php";

$orderObj = new Order();
$userObj = new User();

// Get all staff members
$all_users = $userObj->getAllUsers();
$staff_list = array_filter($all_users, function($user) {
    return $user['role'] == 'staff';
});

// Get selected staff and period
$selected_staff = isset($_GET['staff_id']) ? $_GET['staff_id'] : '';
$period = isset($_GET['period']) ? $_GET['period'] : 'daily';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$sales_data = [];
$totals = ['total_services' => 0, 'total_amount' => 0];

if ($selected_staff) {
    $sales_data = $orderObj->getStaffSales($selected_staff, $period, $date);
    $totals = $orderObj->getStaffTotalSales($selected_staff, $period, $date);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Sales Report</title>
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
            max-width: 1200px;
            margin: 30px auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 10px;
            color: #333;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }

        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .btn-filter {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            align-self: flex-end;
        }

        .btn-print {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            float: right;
            margin-bottom: 20px;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
        }

        .summary-card h3 {
            font-size: 14px;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .summary-card .number {
            font-size: 36px;
            font-weight: bold;
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

        .no-data {
            text-align: center;
            padding: 50px;
            color: #999;
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

        @media print {
            .filters, .btn-print, .back-link {
                display: none;
            }
            body {
                background: white;
            }
        }

        .period-label {
            background: #fff3cd;
            padding: 10px 20px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💰 Staff Sales Report</h1>
        <p class="subtitle">View individual staff performance and earnings</p>

        <div class="filters">
            <form method="GET" style="display: contents;">
                <div class="filter-group">
                    <label>Select Staff:</label>
                    <select name="staff_id" required>
                        <option value="">-- Choose Staff --</option>
                        <?php foreach ($staff_list as $staff): ?>
                            <option value="<?php echo $staff['id']; ?>" 
                                <?php echo $selected_staff == $staff['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($staff['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Period:</label>
                    <select name="period">
                        <option value="daily" <?php echo $period == 'daily' ? 'selected' : ''; ?>>Daily</option>
                        <option value="weekly" <?php echo $period == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                        <option value="monthly" <?php echo $period == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Date:</label>
                    <input type="date" name="date" value="<?php echo $date; ?>">
                </div>

                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-filter">Generate Report</button>
                </div>
            </form>
        </div>

        <?php if ($selected_staff): ?>
            <?php 
            $staff_info = array_filter($all_users, function($u) use ($selected_staff) {
                return $u['id'] == $selected_staff;
            });
            $staff_info = reset($staff_info);
            ?>

            <div class="period-label">
                Showing <?php echo ucfirst($period); ?> Report for 
                <strong><?php echo htmlspecialchars($staff_info['full_name']); ?></strong>
                <?php if ($period == 'daily'): ?>
                    on <?php echo date('F d, Y', strtotime($date)); ?>
                <?php elseif ($period == 'weekly'): ?>
                    for week of <?php echo date('F d, Y', strtotime($date)); ?>
                <?php else: ?>
                    for <?php echo date('F Y', strtotime($date)); ?>
                <?php endif; ?>
            </div>

            <button onclick="window.print()" class="btn-print">🖨️ Print Report</button>

            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Total Services</h3>
                    <div class="number"><?php echo $totals['total_services']; ?></div>
                </div>
                <div class="summary-card">
                    <h3>Total Sales</h3>
                    <div class="number">₱<?php echo number_format($totals['total_amount'], 2); ?></div>
                </div>
                <div class="summary-card">
                    <h3>Average per Service</h3>
                    <div class="number">
                        ₱<?php echo $totals['total_services'] > 0 ? number_format($totals['total_amount'] / $totals['total_services'], 2) : '0.00'; ?>
                    </div>
                </div>
            </div>

            <?php if (count($sales_data) > 0): ?>
                <h3 style="margin-top: 30px; color: #333;">Service Breakdown</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Service Name</th>
                            <th>Number of Services</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales_data as $sale): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sale['service_name']); ?></td>
                                <td><?php echo $sale['service_count']; ?></td>
                                <td>₱<?php echo number_format($sale['service_count'] * $sale['total_sales'] / $sale['total_services'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background: #f8f9fa; font-weight: bold;">
                            <td>TOTAL</td>
                            <td><?php echo $totals['total_services']; ?></td>
                            <td>₱<?php echo number_format($totals['total_amount'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <p>No sales data found for this period.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-data">
                <p>Please select a staff member to view their sales report.</p>
            </div>
        <?php endif; ?>

        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>