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

$appointment_id = isset($_GET['id']) ? trim(htmlspecialchars($_GET['id'])) : "";
$appointment = $appointmentObj->getAppointmentById($appointment_id);

if (!$appointment || $appointment['user_id'] != $_SESSION['user_id']) {
    header("Location: myAppointments.php");
    exit();
}

$error = ["date" => "", "time" => ""];
$submit_error = "";
$submit_success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_date = trim(htmlspecialchars($_POST["appointment_date"]));
    $new_time = trim(htmlspecialchars($_POST["appointment_time"]));

    if (empty($new_date))
        $error["date"] = "Date is required";
    elseif ($new_date < date("Y-m-d"))
        $error["date"] = "Date cannot be in the past";

    if (empty($new_time))
        $error["time"] = "Time is required";

    if (empty(array_filter($error))) {
        if (!$appointmentObj->isTimeSlotAvailable($new_date, $new_time, $appointment_id)) {
            $submit_error = "This time slot is already booked. Please choose another time.";
        } else {
            if ($appointmentObj->rescheduleAppointment($appointment_id, $new_date, $new_time)) {
                $submit_success = "Appointment rescheduled successfully!";
                $appointment = $appointmentObj->getAppointmentById($appointment_id);
            } else {
                $submit_error = "Failed to reschedule. Please try again.";
            }
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
    <title>Reschedule Appointment</title>
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
            margin-bottom: 30px;
            color: #333;
        }

        .appointment-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 25px;
        }

        .appointment-info h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .appointment-info p {
            margin: 8px 0;
            color: #666;
        }

        .appointment-info strong {
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

        input[type="date"], input[type="time"] {
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

        .success {
            color: green;
            font-size: 14px;
            margin-top: 10px;
            text-align: center;
            padding: 10px;
            background: #d4edda;
            border-radius: 5px;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Reschedule Appointment</h1>

        <div class="appointment-info">
            <h3>Current Appointment Details</h3>
            <p><strong>Service:</strong> <?= htmlspecialchars($appointment['service_name']); ?></p>
            <p><strong>Current Date:</strong> <?= date('M d, Y', strtotime($appointment['appointment_date'])); ?></p>
            <p><strong>Current Time:</strong> <?= date('h:i A', strtotime($appointment['appointment_time'])); ?></p>
            <p><strong>Price:</strong> ₱<?= number_format($appointment['price'], 2); ?></p>
        </div>

        <form action="" method="post">
            <label for="appointment_date">New Appointment Date <span>*</span></label>
            <input type="date" name="appointment_date" id="appointment_date" 
                   value="<?= $appointment['appointment_date']; ?>" min="<?= date('Y-m-d'); ?>">
            <p class="error"><?= $error["date"]; ?></p>

            <label for="appointment_time">New Appointment Time <span>*</span></label>
            <input type="time" name="appointment_time" id="appointment_time" 
                   value="<?= $appointment['appointment_time']; ?>" min="09:00" max="18:00">
            <p class="error"><?= $error["time"]; ?></p>
            <small style="color: #666;">Business hours: 9:00 AM - 6:00 PM</small>

            <input type="submit" value="Reschedule Appointment">

            <?php if($submit_error): ?>
                <p class="error" style="text-align: center; margin-top: 10px;"><?= $submit_error; ?></p>
            <?php endif; ?>

            <?php if($submit_success): ?>
                <p class="success"><?= $submit_success; ?></p>
            <?php endif; ?>
        </form>

        <a href="myAppointments.php" class="back-link">Back to My Appointments</a>
    </div>
</body>
</html>