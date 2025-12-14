<?php
    
    ini_set('session.save_path', getcwd() . '/sessions');
if (!is_dir('sessions')) {
    mkdir('sessions', 0700);
}
    
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once "../User.php";
$userObj = new User();

$user = ["full_name" => "", "email" => "", "phone" => "", "password" => "", "confirm_password" => "", "role" => "customer"];
$error = ["full_name" => "", "email" => "", "phone" => "", "password" => "", "confirm_password" => "", "role" => ""];
$submit_error = "";
$submit_success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user["full_name"] = trim(htmlspecialchars($_POST["full_name"]));
    $user["email"] = trim(htmlspecialchars($_POST["email"]));
    $user["phone"] = trim(htmlspecialchars($_POST["phone"]));
    $user["password"] = $_POST["password"];
    $user["confirm_password"] = $_POST["confirm_password"];
    $user["role"] = trim(htmlspecialchars($_POST["role"]));

    // Validation
    if (empty($user["full_name"]))
        $error["full_name"] = "Full name is required";

    if (empty($user["email"]))
        $error["email"] = "Email is required";
    elseif (!filter_var($user["email"], FILTER_VALIDATE_EMAIL))
        $error["email"] = "Invalid email format";

    if (empty($user["phone"]))
        $error["phone"] = "Phone number is required";

    if (empty($user["password"]))
        $error["password"] = "Password is required";
    elseif (strlen($user["password"]) < 6)
        $error["password"] = "Password must be at least 6 characters";

    if (empty($user["confirm_password"]))
        $error["confirm_password"] = "Please confirm password";
    elseif ($user["password"] != $user["confirm_password"])
        $error["confirm_password"] = "Passwords do not match";

    if (empty($user["role"]))
        $error["role"] = "Please select a role";

    // If no errors, proceed with adding user
    if (empty(array_filter($error))) {
        if ($userObj->emailExists($user["email"])) {
            $submit_error = "Email already exists";
        } else {
            $userObj->full_name = $user["full_name"];
            $userObj->email = $user["email"];
            $userObj->phone = $user["phone"];
            $userObj->password = $user["password"];
            $userObj->role = $user["role"];

            if ($userObj->register()) {
                $submit_success = "User added successfully! Role: " . strtoupper($user["role"]);
                $user = ["full_name" => "", "email" => "", "phone" => "", "password" => "", "confirm_password" => "", "role" => "customer"];
            } else {
                $submit_error = "Failed to add user. Please try again.";
            }
        }
    } else {
        $submit_error = "Please fill out all required fields correctly";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User/Staff</title>
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
            max-width: 600px;
            margin: 30px auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
            font-size: 14px;
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

        label {
            display: block;
            font-weight: bold;
            margin-top: 15px;
            color: #333;
        }

        label span {
            color: red;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 15px;
        }

        input:focus, select:focus {
            border-color: #667eea;
            outline: none;
        }

        .error {
            color: red;
            font-size: 14px;
            margin-top: 4px;
        }

        .role-description {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 13px;
            color: #666;
        }

        .role-description h4 {
            color: #333;
            margin-bottom: 10px;
        }

        .role-description ul {
            margin-left: 20px;
        }

        .role-description li {
            margin-bottom: 5px;
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

        .back-link {
            display: block;
            margin-top: 15px;
            padding: 12px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            text-align: center;
            border-radius: 5px;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .info-box h4 {
            color: #0d47a1;
            margin-bottom: 8px;
        }

        .info-box p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>➕ Add New User/Staff</h1>
        <p class="subtitle">Create a new account for customer, staff, or admin</p>

        <?php if ($submit_error): ?>
            <div class="message error"><?php echo $submit_error; ?></div>
        <?php endif; ?>

        <?php if ($submit_success): ?>
            <div class="message success">
                <?php echo $submit_success; ?>
                <br><br>
                <a href="manageUsers.php" style="color: #155724; font-weight: bold;">View All Users</a>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h4>ℹ️ Quick Guide</h4>
            <p>
                <strong>Customer:</strong> Regular users who can book services<br>
                <strong>Staff:</strong> Service providers who can be assigned to orders<br>
                <strong>Admin:</strong> Full access to manage system, users, and reports
            </p>
        </div>

        <form action="" method="post">
            <label for="full_name">Full Name <span>*</span></label>
            <input type="text" name="full_name" id="full_name" value="<?php echo $user["full_name"]; ?>" placeholder="Enter full name">
            <p class="error"><?php echo $error["full_name"]; ?></p>

            <label for="email">Email Address <span>*</span></label>
            <input type="email" name="email" id="email" value="<?php echo $user["email"]; ?>" placeholder="email@example.com">
            <p class="error"><?php echo $error["email"]; ?></p>

            <label for="phone">Phone Number <span>*</span></label>
            <input type="text" name="phone" id="phone" value="<?php echo $user["phone"]; ?>" placeholder="09XXXXXXXXX">
            <p class="error"><?php echo $error["phone"]; ?></p>

            <label for="role">User Role <span>*</span></label>
            <select name="role" id="role" onchange="showRoleDescription()">
                <option value="">-- Select Role --</option>
                <option value="customer" <?php echo $user["role"] == "customer" ? "selected" : ""; ?>>Customer</option>
                <option value="staff" <?php echo $user["role"] == "staff" ? "selected" : ""; ?>>Staff</option>
                <option value="admin" <?php echo $user["role"] == "admin" ? "selected" : ""; ?>>Admin</option>
            </select>
            <p class="error"><?php echo $error["role"]; ?></p>

            <div id="roleDescription" class="role-description" style="display: none;">
                <h4>Selected Role: <span id="roleTitle"></span></h4>
                <ul id="rolePermissions"></ul>
            </div>

            <label for="password">Password <span>*</span></label>
            <input type="password" name="password" id="password" placeholder="Minimum 6 characters">
            <p class="error"><?php echo $error["password"]; ?></p>

            <label for="confirm_password">Confirm Password <span>*</span></label>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter password">
            <p class="error"><?php echo $error["confirm_password"]; ?></p>

            <input type="submit" value="Add User">
        </form>

        <a href="manageUsers.php" class="back-link">← Back to User Management</a>
    </div>

    <script>
        function showRoleDescription() {
            const role = document.getElementById('role').value;
            const descBox = document.getElementById('roleDescription');
            const roleTitle = document.getElementById('roleTitle');
            const rolePerms = document.getElementById('rolePermissions');

            if (!role) {
                descBox.style.display = 'none';
                return;
            }

            descBox.style.display = 'block';

            const descriptions = {
                'customer': {
                    title: 'Customer',
                    permissions: [
                        'Can browse and view services',
                        'Can create orders with multiple services',
                        'Can view their order history',
                        'Can cancel pending orders',
                        'Cannot access admin dashboard'
                    ]
                },
                'staff': {
                    title: 'Staff Member',
                    permissions: [
                        'All customer permissions',
                        'Can access admin dashboard',
                        'Can view assigned orders',
                        'Can mark services as completed',
                        'Can view personal sales reports',
                        'Automatically becomes available after completing services'
                    ]
                },
                'admin': {
                    title: 'Administrator',
                    permissions: [
                        'Full system access',
                        'Can manage all users (add, edit, delete, change roles)',
                        'Can manage services (add, edit, delete)',
                        'Can approve/reject orders',
                        'Can assign staff to services',
                        'Can view all reports and analytics',
                        'Can manage staff availability',
                        'Can access KPIs and dashboard'
                    ]
                }
            };

            const desc = descriptions[role];
            roleTitle.textContent = desc.title;
            rolePerms.innerHTML = desc.permissions.map(p => `<li>${p}</li>`).join('');
        }

        // Show description if role is already selected (on page reload with errors)
        window.onload = function() {
            showRoleDescription();
        };
    </script>
</body>
</html>