<?php
    
    ini_set('session.save_path', getcwd() . '/sessions');
if (!is_dir('sessions')) {
    mkdir('sessions', 0700);
}
    
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "Appointment.php";
$appointmentObj = new Appointment();

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$appointments = $appointmentObj->getUserAppointments($_SESSION['user_id'], $filter);

// Handle cancellation
if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
    $appointment_id = trim(htmlspecialchars($_GET['id']));
    $appointmentObj->cancelAppointment($appointment_id);
    header("Location: myAppointments.php?filter=$filter");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments</title>
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
        }

        .btn-cancel {
            background: #dc3545;
            color: white;
        }

        .btn-reschedule {
            background: #ffc107;
            color: #000;
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

        .book-btn {
            display: inline-block;
            margin: 20px auto;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>My Appointments</h1>

        <div class="filter-tabs">
            <a href="?filter=all" class="<?= $filter == 'all' ? 'active' : ''; ?>">All Appointments</a>
            <a href="?filter=upcoming" class="<?= $filter == 'upcoming' ? 'active' : ''; ?>">Upcoming</a>
            <a href="?filter=past" class="<?= $filter == 'past' ? 'active' : ''; ?>">Past</a>
        </div>

        <?php if (count($appointments) > 0): ?>
            <table>
                <tr>
                    <th>No.</th>
                    <th>Service</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
                <?php 
                $no = 1;
                foreach ($appointments as $apt): 
                ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($apt['service_name']); ?></td>
                        <td><?= date('M d, Y', strtotime($apt['appointment_date'])); ?></td>
                        <td><?= date('h:i A', strtotime($apt['appointment_time'])); ?></td>
                        <td>₱<?= number_format($apt['price'], 2); ?></td>
                        <td>
                            <span class="status status-<?= $apt['status']; ?>">
                                <?= ucfirst($apt['status']); ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($apt['notes']); ?></td>
                        <td>
                            <?php if ($apt['status'] == 'pending' || $apt['status'] == 'approved'): ?>
                                <a href="rescheduleAppointment.php?id=<?= $apt['id']; ?>" class="action-btn btn-reschedule">Reschedule</a>
                                <a href="?action=cancel&id=<?= $apt['id']; ?>&filter=<?= $filter; ?>" 
                                   class="action-btn btn-cancel" 
                                   onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel</a>
                            <?php else: ?>
                                <span style="color: #999;">No actions</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <div class="no-appointments">
                <p>No appointments found.</p>
                <a href="index.php" class="book-btn">Book New Appointment</a>
            </div>
        <?php endif; ?>

        <a href="index.php" class="back-link">← Back to Services</a>
    </div>
</body>
</html>