<?php
// Session configuration for InfinityFree
$session_path = __DIR__ . '/sessions';
if (!is_dir($session_path)) {
    @mkdir($session_path, 0700, true);
}
ini_set('session.save_path', $session_path);
session_start();

require_once "Service.php";
$serviceObj = new Service();

$search = "";
if (isset($_GET["search"])) {
    $search = trim(htmlspecialchars($_GET["search"]));
}

$services = $serviceObj->viewServices($search);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salon Services - Booking System</title>
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

        .nav-links .btn-primary {
            background: white;
            color: #667eea;
            font-weight: bold;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .welcome-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .welcome-section h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .welcome-section p {
            color: #666;
            font-size: 16px;
        }

        .search-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .search-form {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .search-form input[type="search"] {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 15px;
            min-width: 300px;
        }

        .search-form input[type="submit"] {
            padding: 12px 24px;
            background: linear-gradient(135deg, #764ba2 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            font-weight: bold;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .service-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .service-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 22px;
        }

        .service-card p {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .service-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .price {
            color: #667eea;
            font-size: 24px;
            font-weight: bold;
        }

        .duration {
            color: #999;
            align-self: flex-end;
        }

        .book-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #764ba2 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: transform 0.2s;
        }

        .book-btn:hover {
            transform: translateY(-2px);
        }

        .no-services {
            text-align: center;
            padding: 50px;
            color: #999;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <h1>Beauty Salon</h1>
            <div class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span>Welcome, <?= htmlspecialchars($_SESSION['full_name']); ?>!</span>
                    <a href="myOrders.php">My Orders</a>
                    <a href="createOrder.php">New Order</a>
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
                        <a href="admin/dashboard.php">Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php" class="btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="welcome-section">
            <h2>Welcome to Our Salon</h2>
            <p>Discover our premium beauty services and book your appointment today!</p>
        </div>

        <div class="search-section">
            <form class="search-form" method="get">
                <input type="search" name="search" placeholder="Search services..." value="<?= htmlspecialchars($search); ?>">
                <input type="submit" value="Search">
            </form>
        </div>

        <?php if (count($services) > 0): ?>
            <div class="services-grid">
                <?php foreach ($services as $service): ?>
                    <div class="service-card">
                        <h3><?= htmlspecialchars($service['service_name']); ?></h3>
                        <p><?= htmlspecialchars($service['description']); ?></p>
                        <div class="service-info">
                            <span class="price">₱<?= number_format($service['price'], 2); ?></span>
                            <span class="duration"><?= $service['duration']; ?> mins</span>
                        </div>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="createOrder.php" class="book-btn">Order Now</a>
                        <?php else: ?>
                            <a href="login.php" class="book-btn">Login to Book</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-services">
                <p>No services found.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>