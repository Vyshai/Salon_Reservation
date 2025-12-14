<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$session_path = __DIR__ . '/sessions';
if (!is_dir($session_path)) {
    @mkdir($session_path, 0700, true);
}
ini_set('session.save_path', $session_path);

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in - redirecting to login");
    header("Location: login.php");
    exit();
}

// Check if user is verified (for customers only)
require_once "User.php";
$userObj = new User();
$currentUser = $userObj->getUserById($_SESSION['user_id']);

if (!$currentUser) {
    error_log("User not found in database - ID: " . $_SESSION['user_id']);
    session_destroy();
    header("Location: login.php");
    exit();
}

// If customer and not verified, redirect
if ($currentUser['role'] == 'customer' && $currentUser['is_verified'] == 0) {
    error_log("User not verified - Email: " . $currentUser['email']);
    session_destroy();
    header("Location: login.php?error=not_verified");
    exit();
}

// User is good to go!
error_log("User verified and can place orders - ID: " . $_SESSION['user_id']);

require_once "Service.php";
require_once "Order.php"; 
require_once "Notification.php";
require_once "EmailService.php";

$serviceObj = new Service();
$orderObj = new Order();
$notificationObj = new Notification();
$emailService = new EmailService();

$services = $serviceObj->viewServices();
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// Add to cart
if (isset($_POST['add_to_cart'])) {
    $service_id = $_POST['service_id'];
    $service = $serviceObj->getServiceById($service_id);
    
    if ($service) {
        $_SESSION['cart'][] = [
            'service_id' => $service['id'],
            'service_name' => $service['service_name'],
            'price' => $service['price'],
            'duration' => $service['duration']
        ];
        $cart = $_SESSION['cart'];
    }
    header("Location: createOrder.php");
    exit();
}

// Remove from cart
if (isset($_GET['remove'])) {
    $index = $_GET['remove'];
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        $cart = $_SESSION['cart'];
    }
    header("Location: createOrder.php");
    exit();
}

// Clear cart
if (isset($_GET['clear_cart'])) {
    $_SESSION['cart'] = [];
    $cart = [];
    header("Location: createOrder.php");
    exit();
}

// Place order
$order_success = false;
$order_error = "";
$new_order_id = null;

if (isset($_POST['place_order'])) {
    $order_date = trim(htmlspecialchars($_POST['order_date']));
    $order_time = trim(htmlspecialchars($_POST['order_time']));
    $notes = trim(htmlspecialchars($_POST['notes']));
    
    // Validation
    if (empty($order_date)) {
        $order_error = "Please select an order date";
    } elseif (empty($order_time)) {
        $order_error = "Please select a preferred time";
    } elseif (count($cart) == 0) {
        $order_error = "Your cart is empty. Please add services first.";
    } else {
        // Calculate total
        $total = array_sum(array_column($cart, 'price'));
        
        // Create order
        $orderObj->user_id = $_SESSION['user_id'];
        $orderObj->order_date = $order_date;
        $orderObj->order_time = $order_time;
        $orderObj->total_amount = $total;
        $orderObj->status = 'pending';
        $orderObj->notes = $notes;
        
        $order_id = $orderObj->createOrder();
        
        if ($order_id) {
            // Add order items - THIS IS THE CRITICAL FIX
            $items_added = 0;
            foreach ($cart as $item) {
                if ($orderObj->addOrderItem($order_id, $item['service_id'], $item['price'], 1)) {
                    $items_added++;
                }
            }
            
            // Check if items were actually added
            if ($items_added == count($cart)) {
                // SUCCESS - All items added
                
                // Send notification to admins
                try {
                    $notificationObj->notifyAdmins(
                        'order',
                        'New Order Request #' . $order_id,
                        $_SESSION['full_name'] . ' has placed a new order with ' . count($cart) . ' service(s).',
                        'viewOrderAdmin.php?id=' . $order_id
                    );
                } catch (Exception $e) {
                    error_log("Notification error: " . $e->getMessage());
                }
                
                // Send email to customer
                try {
                    $order_details = $orderObj->getOrderDetails($order_id);
                    $emailService->sendOrderConfirmation(
                        $_SESSION['email'],
                        $_SESSION['full_name'],
                        $order_id,
                        $order_details
                    );
                } catch (Exception $e) {
                    error_log("Email error: " . $e->getMessage());
                }
                
                // Send notification to customer
                try {
                    $notificationObj->notifyUser(
                        $_SESSION['user_id'],
                        'order',
                        'Order Placed Successfully',
                        'Your order #' . $order_id . ' has been placed and is pending approval.',
                        'viewOrder.php?id=' . $order_id
                    );
                } catch (Exception $e) {
                    error_log("User notification error: " . $e->getMessage());
                }
                
                // Clear cart
                $_SESSION['cart'] = [];
                $cart = [];
                
                // Set success message
                $order_success = true;
                $new_order_id = $order_id;
            } else {
                // Items failed to add
                $order_error = "Order created but failed to add services. Please contact support.";
                error_log("Order #$order_id: Only $items_added of " . count($cart) . " items were added");
            }
        } else {
            $order_error = "Failed to create order. Please try again.";
            error_log("Order creation failed for user: " . $_SESSION['user_id']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Order</title>
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
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .services-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .cart-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .service-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .service-card h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .service-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .service-price {
            color: #764ba2;
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }

        .service-duration {
            color: #999;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .btn-add {
            width: 100%;
            padding: 10px;
            background: #764ba2;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-add:hover {
            background: #5568d3;
        }

        .cart-item {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item-info {
            flex: 1;
        }

        .cart-item-info h4 {
            color: #333;
            margin-bottom: 5px;
        }

        .cart-item-info p {
            color: #666;
            font-size: 13px;
        }

        .btn-remove {
            padding: 5px 10px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
        }

        .cart-total {
            background: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }

        .order-form {
            margin-top: 20px;
        }

        .order-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .order-form input,
        .order-form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .btn-order {
            width: 100%;
            padding: 15px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
        }

        .btn-order:hover {
            background: #218838;
        }

        .btn-order:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .btn-clear {
            width: 100%;
            padding: 10px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 15px;
        }

        .empty-cart {
            text-align: center;
            padding: 30px;
            color: #999;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #c3e6cb;
        }

        .success-message h3 {
            margin-bottom: 10px;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .cart-section {
                position: static;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <h1>🛒 Create Order</h1>
            <div class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</span>
                <a href="index.php">Home</a>
                <a href="myOrders.php">My Orders</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="services-section">
            <h2>Available Services</h2>
            <div class="service-grid">
                <?php foreach ($services as $service): ?>
                    <div class="service-card">
                        <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                        <p><?php echo htmlspecialchars($service['description']); ?></p>
                        <div class="service-price">₱<?php echo number_format($service['price'], 2); ?></div>
                        <div class="service-duration"><?php echo $service['duration']; ?> minutes</div>
                        <form method="POST">
                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                            <button type="submit" name="add_to_cart" class="btn-add">+ Add to Cart</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="cart-section">
            <h2>Your Cart (<?php echo count($cart); ?>)</h2>

            <?php if ($order_success): ?>
                <div class="success-message">
                    <h3>✓ Order Placed Successfully!</h3>
                    <p>Order #<?php echo $new_order_id; ?></p>
                    <p>You will receive an email confirmation shortly.</p>
                    <br>
                    <a href="myOrders.php" style="color: #155724; font-weight: bold; text-decoration: underline;">View My Orders</a>
                </div>
            <?php endif; ?>

            <?php if ($order_error): ?>
                <div class="error-message">
                    <strong>⚠ Error:</strong> <?php echo $order_error; ?>
                </div>
            <?php endif; ?>

            <?php if (count($cart) > 0): ?>
                <a href="?clear_cart=1" class="btn-clear" onclick="return confirm('Clear all items from cart?')">Clear Cart</a>

                <?php foreach ($cart as $index => $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <h4><?php echo htmlspecialchars($item['service_name']); ?></h4>
                            <p>₱<?php echo number_format($item['price'], 2); ?> • <?php echo $item['duration']; ?> min</p>
                        </div>
                        <a href="?remove=<?php echo $index; ?>" class="btn-remove" onclick="return confirm('Remove this service?')">Remove</a>
                    </div>
                <?php endforeach; ?>

                <div class="cart-total">
                    Total: ₱<?php echo number_format(array_sum(array_column($cart, 'price')), 2); ?>
                </div>

                <form method="POST" class="order-form" onsubmit="return validateForm()">
                    <label>Order Date: <span style="color: red;">*</span></label>
                    <input type="date" name="order_date" id="order_date" min="<?php echo date('Y-m-d'); ?>" required>

                    <label>Preferred Time: <span style="color: red;">*</span></label>
                    <input type="time" name="order_time" id="order_time" min="09:00" max="18:00" required>
                    <small style="color: #666; display: block; margin-top: -10px; margin-bottom: 15px;">Business hours: 9:00 AM - 6:00 PM</small>

                    <label>Notes (Optional):</label>
                    <textarea name="notes" rows="3" placeholder="Any special requests..."></textarea>

                    <button type="submit" name="place_order" class="btn-order">Place Order</button>
                </form>
            <?php else: ?>
                <div class="empty-cart">
                    <p>Your cart is empty</p>
                    <p style="font-size: 12px; margin-top: 10px;">Add services from the left to get started!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function validateForm() {
            const date = document.getElementById('order_date').value;
            const time = document.getElementById('order_time').value;
            
            if (!date) {
                alert('Please select an order date');
                return false;
            }
            
            if (!time) {
                alert('Please select a preferred time');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>