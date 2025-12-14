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

$message = "";
$message_type = "";

// Handle role change
if (isset($_POST['change_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    $sql = "UPDATE users SET role = :role WHERE id = :id";
    $query = $userObj->connect()->prepare($sql);
    $query->bindParam(':role', $new_role);
    $query->bindParam(':id', $user_id);
    
    if ($query->execute()) {
        $message = "User role updated successfully!";
        $message_type = "success";
    } else {
        $message = "Failed to update user role.";
        $message_type = "error";
    }
}

// Handle staff availability toggle
if (isset($_POST['toggle_availability'])) {
    $user_id = $_POST['user_id'];
    $is_available = $_POST['is_available'] == '1' ? 0 : 1;
    
    $sql = "UPDATE users SET is_available = :available WHERE id = :id";
    $query = $userObj->connect()->prepare($sql);
    $query->bindParam(':available', $is_available);
    $query->bindParam(':id', $user_id);
    
    if ($query->execute()) {
        $message = "Staff availability updated!";
        $message_type = "success";
    }
}

// Handle delete user
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // Don't allow deleting yourself
    if ($user_id != $_SESSION['user_id']) {
        $sql = "DELETE FROM users WHERE id = :id";
        $query = $userObj->connect()->prepare($sql);
        $query->bindParam(':id', $user_id);
        
        if ($query->execute()) {
            $message = "User deleted successfully!";
            $message_type = "success";
        }
    } else {
        $message = "You cannot delete your own account!";
        $message_type = "error";
    }
}

$users = $userObj->getAllUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users & Staff</title>
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
            max-width: 1400px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
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

        .role-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .role-customer {
            background: #e3f2fd;
            color: #1976d2;
        }

        .role-staff {
            background: #fff9e6;
            color: #f57c00;
        }

        .role-admin {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-busy {
            background: #f8d7da;
            color: #721c24;
        }

        select {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 5px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            margin: 2px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #000;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
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

        .add-user-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 12px 24px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #667eea;
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>👥 Manage Users & Staff</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="number"><?php echo count($users); ?></div>
            </div>
            <div class="stat-card">
                <h3>Staff Members</h3>
                <div class="number">
                    <?php echo count(array_filter($users, function($u) { return $u['role'] == 'staff'; })); ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>Customers</h3>
                <div class="number">
                    <?php echo count(array_filter($users, function($u) { return $u['role'] == 'customer'; })); ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>Admins</h3>
                <div class="number">
                    <?php echo count(array_filter($users, function($u) { return $u['role'] == 'admin'; })); ?>
                </div>
            </div>
        </div>

        <a href="addUser.php" class="add-user-link">+ Add New User/Staff</a>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td>
                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                <?php echo strtoupper($user['role']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['role'] == 'staff'): ?>
                                <?php 
                                // Safely get is_available value with default
                                $is_available = isset($user['is_available']) ? $user['is_available'] : 1;
                                ?>
                                <span class="status-badge status-<?php echo $is_available ? 'available' : 'busy'; ?>">
                                    <?php echo $is_available ? 'Available' : 'Busy'; ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #999;">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <!-- Change Role -->
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <select name="new_role" required>
                                    <option value="">Change Role</option>
                                    <option value="customer" <?php echo $user['role'] == 'customer' ? 'selected' : ''; ?>>Customer</option>
                                    <option value="staff" <?php echo $user['role'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                                <button type="submit" name="change_role" class="btn btn-primary">Update</button>
                            </form>

                            <!-- Toggle Availability (for staff only) -->
                            <?php if ($user['role'] == 'staff'): ?>
                                <?php $is_available = isset($user['is_available']) ? $user['is_available'] : 1; ?>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="is_available" value="<?php echo $is_available; ?>">
                                    <button type="submit" name="toggle_availability" class="btn btn-warning">
                                        <?php echo $is_available ? 'Set Busy' : 'Set Available'; ?>
                                    </button>
                                </form>
                            <?php endif; ?>

                            <!-- Delete User -->
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="?delete=1&id=<?php echo $user['id']; ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this user? This will also delete all their data.');">
                                    Delete
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>