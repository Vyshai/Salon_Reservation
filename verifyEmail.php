<?php
    
    ini_set('session.save_path', getcwd() . '/sessions');
if (!is_dir('sessions')) {
    mkdir('sessions', 0700);
}
    
session_start();

require_once "User.php";
$userObj = new User();

$message = "";
$message_type = "";

if (isset($_GET['token'])) {
    $token = trim(htmlspecialchars($_GET['token']));
    
    $result = $userObj->verifyEmail($token);
    
    if ($result === true) {
        $message = "Email verified successfully! You can now login to your account.";
        $message_type = "success";
    } elseif ($result === 'expired') {
        $message = "Verification link has expired. Please request a new verification email.";
        $message_type = "error";
    } elseif ($result === 'already_verified') {
        $message = "This email is already verified. You can login to your account.";
        $message_type = "info";
    } else {
        $message = "Invalid verification link. Please check your email or request a new verification email.";
        $message_type = "error";
    }
} else {
    $message = "No verification token provided.";
    $message_type = "error";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 500px;
            width: 100%;
            background: #fff;
            padding: 50px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .icon.success { color: #28a745; }
        .icon.error { color: #dc3545; }
        .icon.info { color: #17a2b8; }

        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 28px;
        }

        .message {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: transform 0.2s;
            margin: 5px;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon <?= $message_type; ?>">
            <?php if ($message_type == 'success'): ?>
                ✓
            <?php elseif ($message_type == 'error'): ?>
                ✗
            <?php else: ?>
                ℹ
            <?php endif; ?>
        </div>
        
        <h1>Email Verification</h1>
        <p class="message"><?= $message; ?></p>
        
        <?php if ($message_type == 'success' || $message_type == 'info'): ?>
            <a href="login.php" class="btn">Go to Login</a>
        <?php else: ?>
            <a href="resendVerification.php" class="btn">Resend Verification Email</a>
            <a href="register.php" class="btn btn-secondary">Back to Register</a>
        <?php endif; ?>
    </div>
</body>
</html>