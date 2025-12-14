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

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get sales data
$conn = $orderObj->connect();

$sql = "SELECT 
            DATE(sale_date) as date,
            COUNT(*) as total_services,
            SUM(amount) as total_amount
        FROM sales
        WHERE sale_date BETWEEN :start_date AND :end_date
        GROUP BY DATE(sale_date)
        ORDER BY sale_date DESC";

$query = $conn->prepare($sql);
$query->bindParam(':start_date', $start_date);
$query->bindParam(':end_date', $end_date);
$query->execute();
$daily_sales = $query->fetchAll(PDO::FETCH_ASSOC);

// Get summary
$summary_sql = "SELECT 
                    COUNT(*) as total_services,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_amount
                FROM sales
                WHERE sale_date BETWEEN :start_date AND :end_date";

$summary_query = $conn->prepare($summary_sql);
$summary_query->bindParam(':start_date', $start_date);
$summary_query->bindParam(':end_date', $end_date);
$summary_query->execute();
$summary = $summary_query->fetch(PDO::FETCH_ASSOC);

// Get top services
$top_services_sql = "SELECT 
                        s.service_name,
                        COUNT(*) as count,
                        SUM(sa.amount) as total
                     FROM sales sa
                     JOIN services s ON sa.service_id = s.id
                     WHERE sa.sale_date BETWEEN :start_date AND :end_date
                     GROUP BY s.id, s.service_name
                     ORDER BY count DESC
                     LIMIT 5";

$top_query = $conn->prepare($top_services_sql);
$top_query->bindParam(':start_date', $start_date);
$top_query->bindParam(':end_date', $end_date);
$top_query->execute();
$top_services = $top_query->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report</title>
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
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .filter-group input {
            width: 100%;
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

        .section-title {
            margin: 30px 0 15px;
            color: #333;
            font-size: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
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

        .date-range {
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
        <h1>📊 Sales Report</h1>
        <p class="subtitle">Comprehensive sales analytics and performance overview</p>

        <div class="filters">
            <form method="GET" style="display: contents;">
                <div class="filter-group">
                    <label>Start Date:</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" required>
                </div>

                <div class="filter-group">
                    <label>End Date:</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" required>
                </div>

                <button type="submit" class="btn-filter">Generate Report</button>
            </form>
        </div>

        <div class="date-range">
            Showing sales from <?php echo date('F d, Y', strtotime($start_date)); ?> 
            to <?php echo date('F d, Y', strtotime($end_date)); ?>
        </div>

        <button onclick="window.print()" class="btn-print">🖨️ Print Report</button>

        <div class="summary-cards">
            <div class="summary-card">
                <h3>Total Revenue</h3>
                <div class="number">₱<?php echo number_format($summary['total_amount'] ?? 0, 2); ?></div>
            </div>
            <div class="summary-card">
                <h3>Total Services</h3>
                <div class="number"><?php echo $summary['total_services'] ?? 0; ?></div>
            </div>
            <div class="summary-card">
                <h3>Average Sale</h3>
                <div class="number">₱<?php echo number_format($summary['avg_amount'] ?? 0, 2); ?></div>
            </div>
        </div>

        <h3 class="section-title">📈 Daily Sales Breakdown</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Total Services</th>
                    <th>Total Sales</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($daily_sales) > 0): ?>
                    <?php foreach ($daily_sales as $day): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($day['date'])); ?></td>
                            <td><?php echo date('l', strtotime($day['date'])); ?></td>
                            <td><?php echo $day['total_services']; ?></td>
                            <td>₱<?php echo number_format($day['total_amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #999;">No sales data for this period</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f8f9fa; font-weight: bold;">
                    <td colspan="2">TOTAL</td>
                    <td><?php echo $summary['total_services'] ?? 0; ?></td>
                    <td>₱<?php echo number_format($summary['total_amount'] ?? 0, 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <h3 class="section-title">🏆 Top 5 Services</h3>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Service Name</th>
                    <th>Times Booked</th>
                    <th>Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($top_services) > 0): ?>
                    <?php 
                    $rank = 1;
                    foreach ($top_services as $service): 
                    ?>
                        <tr>
                            <td><?php echo $rank++; ?></td>
                            <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                            <td><?php echo $service['count']; ?></td>
                            <td>₱<?php echo number_format($service['total'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #999;">No data available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>