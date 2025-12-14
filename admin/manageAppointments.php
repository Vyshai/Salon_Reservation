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

require_once "../Appointment.php";
$appointmentObj = new Appointment();

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$appointments = $appointmentObj->getAllAppointments($status_filter);

// Handle status updates and staff assignment
if (isset($_GET['action']) && isset($_GET['id'])) {
    $appointment_id = trim(htmlspecialchars($_GET['id']));
    $action = $_GET['action'];
    
    if ($action == 'approve' && isset($_GET['staff_id'])) {
        $staff_id = trim(htmlspecialchars($_GET['staff_id']));
        $appointmentObj->assignStaff($appointment_id, $staff_id);
        $appointmentObj->updateStatus($appointment_id, 'approved');
    } elseif ($action == 'reject') {
        $appointmentObj->updateStatus($appointment_id, 'rejected');
    } elseif ($action == 'complete') {
        $appointmentObj->updateStatus($appointment_id, 'completed');
    }
    
    header("Location: manageAppointments.php?status=$status_filter");
    exit();
}

$all_staff = $appointmentObj->getAllStaff();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments</title>
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .filter-tabs a {
            padding: 10px 20px;
            background: #f8f9fa;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .filter-tabs a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .filter-tabs a:hover {
            background: #e9ecef;
        }

        .filter-tabs a.active:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            overflow-x: auto;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            font-size: 14px;
        }

        table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        .status {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: bold;
            display: inline-block;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background: #e2e3e5;
            color: #383d41;
        }

        .action-btn {
            padding: 6px 12px;
            margin: 2px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            display: inline-block;
            color: white;
        }

        .btn-approve {
            background: #28a745;
        }

        .btn-reject {
            background: #dc3545;
        }

        .btn-complete {
            background: #17a2b8;
        }

        .btn-reschedule {
            background: #ffc107;
            color: #000;
        }

        .staff-select {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            margin-right: 5px;
        }

        .staff-badge {
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .no-appointments {
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

        .staff-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Appointments</h1>

        <div class="filter-tabs">
            <a href="?status=" class="<?php echo $status_filter == '' ? 'active' : ''; ?>">All</a>
            <a href="?status=pending" class="<?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="?status=approved" class="<?php echo $status_filter == 'approved' ? 'active' : ''; ?>">Approved</a>
            <a href="?status=rejected" class="<?php echo $status_filter == 'rejected' ? 'active' : ''; ?>">Rejected</a>
            <a href="?status=completed" class="<?php echo $status_filter == 'completed' ? 'active' : ''; ?>">Completed</a>
            <a href="?status=cancelled" class="<?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
        </div>

        <?php if (count($appointments) > 0): ?>
            <div style="overflow-x: auto;">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Price</th>
                        <th>Assigned Staff</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($appointments as $apt): ?>
                        <tr>
                            <td><?php echo $apt['id']; ?></td>
                            <td><?php echo htmlspecialchars($apt['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($apt['email']); ?></td>
                            <td><?php echo htmlspecialchars($apt['phone']); ?></td>
                            <td><?php echo htmlspecialchars($apt['service_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></td>
                            <td>₱<?php echo number_format($apt['price'], 2); ?></td>
                            <td>
                                <?php if ($apt['staff_name']): ?>
                                    <span class="staff-badge"><?php echo htmlspecialchars($apt['staff_name']); ?></span>
                                <?php else: ?>
                                    <span style="color: #999;">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status status-<?php echo $apt['status']; ?>">
                                    <?php echo ucfirst($apt['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($apt['notes']); ?></td>
                            <td>
                                <?php if ($apt['status'] == 'pending'): ?>
                                    <form method="get" style="display: inline-block;">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?php echo $apt['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                                        <select name="staff_id" class="staff-select" required>
                                            <option value="">Select Staff</option>
                                            <?php 
                                            $available_staff = $appointmentObj->getAvailableStaff($apt['appointment_date'], $apt['appointment_time']);
                                            foreach ($available_staff as $staff): 
                                            ?>
                                                <option value="<?php echo $staff['id']; ?>">
                                                    <?php echo htmlspecialchars($staff['full_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="action-btn btn-approve"
                                           onclick="return confirm('Approve this appointment?')">Approve</button>
                                    </form>
                                    <a href="?action=reject&id=<?php echo $apt['id']; ?>&status=<?php echo $status_filter; ?>" 
                                       class="action-btn btn-reject"
                                       onclick="return confirm('Reject this appointment?')">Reject</a>
                                    <div class="staff-info">
                                        <?php 
                                        $avail_count = $appointmentObj->getAvailableStaffCount($apt['appointment_date'], $apt['appointment_time']);
                                        echo "$avail_count of 5 staff available";
                                        ?>
                                    </div>
                                <?php elseif ($apt['status'] == 'approved'): ?>
                                    <a href="adminReschedule.php?id=<?php echo $apt['id']; ?>" class="action-btn btn-reschedule">Reschedule</a>
                                    <a href="?action=complete&id=<?php echo $apt['id']; ?>&status=<?php echo $status_filter; ?>" 
                                       class="action-btn btn-complete"
                                       onclick="return confirm('Mark as completed?')">Complete</a>
                                <?php else: ?>
                                    <span style="color: #999;">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php else: ?>
            <div class="no-appointments">
                <p>No appointments found.</p>
            </div>
        <?php endif; ?>

        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>