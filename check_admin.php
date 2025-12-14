<?php
require_once "database.php";
require_once "User.php";

$userObj = new User();

echo "<h1>Admin Account Checker</h1>";
echo "<hr>";

// Check if admin exists
echo "<h2>Test 1: Check Admin Account</h2>";

$sql = "SELECT id, full_name, email, role, is_verified FROM users WHERE email = 'admin@salon.com'";
$conn = $userObj->connect();
$query = $conn->prepare($sql);

if ($query->execute()) {
    $admin = $query->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<span style='color: green;'>✅ Admin account found!</span><br>";
        echo "<table border='1' cellpadding='10' style='margin-top: 20px;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>" . $admin['id'] . "</td></tr>";
        echo "<tr><td>Name</td><td>" . htmlspecialchars($admin['full_name']) . "</td></tr>";
        echo "<tr><td>Email</td><td>" . htmlspecialchars($admin['email']) . "</td></tr>";
        echo "<tr><td>Role</td><td>" . htmlspecialchars($admin['role']) . "</td></tr>";
        echo "<tr><td>Is Verified</td><td>" . ($admin['is_verified'] ? 'Yes' : 'No') . "</td></tr>";
        echo "</table>";
    } else {
        echo "<span style='color: red;'>❌ Admin account NOT found!</span><br>";
        echo "<p>You need to create an admin account first.</p>";
    }
} else {
    echo "<span style='color: red;'>❌ Database query failed</span><br>";
}

echo "<hr>";

// Test login
echo "<h2>Test 2: Test Login Credentials</h2>";

if (isset($_POST['test_login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    echo "<h3>Testing login for: " . htmlspecialchars($email) . "</h3>";
    
    $result = $userObj->login($email, $password);
    
    if ($result) {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='color: green;'>✅ LOGIN SUCCESSFUL!</h3>";
        echo "<p><strong>User Details:</strong></p>";
        echo "<ul>";
        echo "<li>ID: " . $result['id'] . "</li>";
        echo "<li>Name: " . htmlspecialchars($result['full_name']) . "</li>";
        echo "<li>Email: " . htmlspecialchars($result['email']) . "</li>";
        echo "<li>Role: " . htmlspecialchars($result['role']) . "</li>";
        echo "<li>Is Verified: " . ($result['is_verified'] ? 'Yes' : 'No') . "</li>";
        echo "</ul>";
        echo "<p style='margin-top: 15px;'>";
        echo "<a href='login.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";
        echo "</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='color: red;'>❌ LOGIN FAILED</h3>";
        echo "<p>Possible reasons:</p>";
        echo "<ul>";
        echo "<li>Wrong password</li>";
        echo "<li>Account doesn't exist</li>";
        echo "<li>Account is not verified (for customers)</li>";
        echo "</ul>";
        echo "</div>";
    }
}

echo '<form method="POST" style="background: #f8f9fa; padding: 20px; border-radius: 5px; max-width: 400px;">';
echo '<h3>Test Login Credentials</h3>';
echo '<label style="display: block; margin-top: 10px;">Email:</label>';
echo '<input type="email" name="email" value="admin@salon.com" style="width: 100%; padding: 8px; margin-top: 5px;" required>';
echo '<label style="display: block; margin-top: 10px;">Password:</label>';
echo '<input type="password" name="password" placeholder="Enter password" style="width: 100%; padding: 8px; margin-top: 5px;" required>';
echo '<button type="submit" name="test_login" style="margin-top: 15px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">Test Login</button>';
echo '</form>';

echo "<hr>";

// Show all admin/staff accounts
echo "<h2>Test 3: All Admin/Staff Accounts</h2>";

$sql = "SELECT id, full_name, email, role, is_verified FROM users WHERE role IN ('admin', 'staff') ORDER BY role, full_name";
$query = $conn->prepare($sql);

if ($query->execute()) {
    $accounts = $query->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($accounts) > 0) {
        echo "<table border='1' cellpadding='10' style='width: 100%; margin-top: 20px;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Verified</th></tr>";
        foreach ($accounts as $acc) {
            echo "<tr>";
            echo "<td>" . $acc['id'] . "</td>";
            echo "<td>" . htmlspecialchars($acc['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($acc['email']) . "</td>";
            echo "<td><strong>" . strtoupper($acc['role']) . "</strong></td>";
            echo "<td>" . ($acc['is_verified'] ? '✅ Yes' : '❌ No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<span style='color: orange;'>⚠ No admin/staff accounts found</span><br>";
    }
}

echo "<hr>";

// Password reset helper
echo "<h2>Test 4: Reset Admin Password</h2>";
echo "<p>If you forgot the admin password, you can reset it using phpMyAdmin or this tool:</p>";

if (isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    
    $sql = "UPDATE users SET password = :password WHERE email = 'admin@salon.com'";
    $query = $conn->prepare($sql);
    $query->bindParam(':password', $hashed);
    
    if ($query->execute()) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<strong style='color: green;'>✅ Password reset successful!</strong><br>";
        echo "New password: <strong>" . htmlspecialchars($new_password) . "</strong><br>";
        echo "You can now login with this password.";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<strong style='color: red;'>❌ Failed to reset password</strong>";
        echo "</div>";
    }
}

echo '<form method="POST" style="background: #fff9e6; padding: 20px; border-radius: 5px; max-width: 400px; margin-top: 15px;">';
echo '<h3>⚠️ Reset Admin Password</h3>';
echo '<p style="color: #856404; font-size: 14px;">This will reset the password for admin@salon.com</p>';
echo '<label style="display: block; margin-top: 10px;">New Password:</label>';
echo '<input type="text" name="new_password" value="admin123" style="width: 100%; padding: 8px; margin-top: 5px;" required>';
echo '<button type="submit" name="reset_password" style="margin-top: 15px; padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer;">Reset Password</button>';
echo '</form>';
?>