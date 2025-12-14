<?php
    
    ini_set('session.save_path', getcwd() . '/sessions');
if (!is_dir('sessions')) {
    mkdir('sessions', 0700);
}
    
session_start();

require_once "User.php";
require_once "EmailService.php";

$userObj = new User();
$emailService = new EmailService();

$email = "";
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim(htmlspecialchars($_POST["email"]));
    
    if (empty($email)) {
        $error = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        $result = $userObj->resendVerification($email);
        
        if ($result === 'not_found') {
            $error = "No account found with this email";
        } elseif ($result === 'already_verified') {
            $success = "This account is already verified! You can now login.";
        } elseif ($result) {
            // Get user details
            $user = $userObj->getUserByEmail($email);
            
            // Send new verification email
            if ($emailService->sendVerificationEmail($email, $user['full_name'], $result)) {
                $success = "Verification email sent! Please check your inbox.";
            } else {
                $error = "Failed to send email. Please try again.";
            }
        } else {
            $error = "Failed to resend verification. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification Email</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 450px;
            width: 100%;
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
            color: #333;
        }
        label {
            display: block;
            font-weight: bold;
            margin-top: 15px;
            color: #333;
        }
        input[type="email"] {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 15px;
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
        }
        .error {
            color: red;
            background: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .success {
            color: green;
            background: #d4edda;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .links {
            text-align: center;
            margin-top: 20px;
        }
        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Resend Verification Email</h1>
        
        <?php if ($success): ?>
            <div class="success"><?= $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <label>Email Address</label>
            <input type="email" name="email" value="<?= $email; ?>" placeholder="your-email@example.com" required>
            <input type="submit" value="Send Verification Email">
        </form>
        
        <div class="links">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>