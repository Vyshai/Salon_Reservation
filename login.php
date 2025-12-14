<?php
// Session configuration
$session_path = __DIR__ . '/sessions';
if (!is_dir($session_path)) {
    @mkdir($session_path, 0700, true);
}
ini_set('session.save_path', $session_path);

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in - BUT DO IT CORRECTLY
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // Don't redirect if we just came from a redirect (prevent loop)
    if (!isset($_GET['redirect_check'])) {
        if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff') {
            header("Location: admin/dashboard.php");
            exit();
        } else {
            header("Location: index.php");
            exit();
        }
    }
}

require_once "User.php";
$userObj = new User();

$email = "";
$error = ["email" => "", "password" => ""];
$submit_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim(htmlspecialchars($_POST["email"]));
    $password = $_POST["password"];

    // Validation
    if (empty($email))
        $error["email"] = "Email is required";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $error["email"] = "Invalid email format";

    if (empty($password))
        $error["password"] = "Password is required";

    // If no errors, attempt login
    if (empty(array_filter($error))) {
        $user = $userObj->login($email, $password);
    
        if ($user) {
            // Check if verified (for customers only)
            if ($user['role'] == 'customer' && $user['is_verified'] == 0) {
                $submit_error = "Please verify your email before logging in. <a href='resendVerification.php?email=" . urlencode($email) . "'>Resend verification email</a>";
            } else {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                // Redirect based on role - ADD exit() immediately after header()
                if ($user['role'] == 'admin' || $user['role'] == 'staff') {
                    header("Location: admin/dashboard.php");
                    exit(); // CRITICAL: Must exit after redirect
                } else {
                    header("Location: index.php");
                    exit(); // CRITICAL: Must exit after redirect
                }
            }
        } else {
            $submit_error = "Invalid email or password";
        }
    } else {
        $submit_error = "Please fill out all required fields";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Salon Booking System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg, #764ba2 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 400px;
            width: 100%;
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .container h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            color: #333;
        }

        label {
            display: block;
            font-weight: bold;
            margin-top: 15px;
            color: #333;
        }

        label span {
            color: red;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 15px;
        }

        input:focus {
            border-color: #667eea;
            outline: none;
        }

        .error {
            color: red;
            font-size: 14px;
            margin-top: 4px;
        }

        input[type="submit"] {
            margin-top: 25px;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }

        input[type="submit"]:hover {
            transform: translateY(-2px);
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .home-link {
            text-align: center;
            margin-top: 15px;
        }

        .home-link a {
            color: #999;
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php if (isset($_GET['error']) && $_GET['error'] == 'not_verified'): ?>
    <div style="background: #fff3cd; color: #856404; padding: 15px; text-align: center; margin: 20px; position: fixed; top: 0; left: 0; right: 0; z-index: 1000;">
        <strong>⚠ Email Not Verified</strong><br>
        Please check your email and click the verification link before placing orders.<br>
        <a href="resendVerification.php" style="color: #856404; font-weight: bold;">Resend Verification Email</a>
    </div>
    <?php endif; ?>

    <div class="container">
        <h1>Login</h1>

        <form action="" method="post">
            <label for="email">Email <span>*</span></label>
            <input type="email" name="email" id="email" value="<?= $email; ?>">
            <p class="error"><?= $error["email"]; ?></p>

            <label for="password">Password <span>*</span></label>
            <input type="password" name="password" id="password">
            <p class="error"><?= $error["password"]; ?></p>

            <input type="submit" value="Login">

            <?php if($submit_error): ?>
                <p class="error" style="text-align: center; margin-top: 10px;"><?= $submit_error; ?></p>
            <?php endif; ?>
        </form>

        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>

        <div class="home-link">
            <a href="index.php">← Back to Home</a>
        </div>
    </div>
</body>
</html>