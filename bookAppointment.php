<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "Service.php";
require_once "Appointment.php";

$serviceObj = new Service();
$appointmentObj = new Appointment();

$service_id = isset($_GET['service_id']) ? trim(htmlspecialchars($_GET['service_id'])) : "";
$service = null;

if ($service_id) {
    $service = $serviceObj->getServiceById($service_id);
}

$appointment = ["service_id" => $service_id, "appointment_date" => "", "appointment_time" => "", "notes" => ""];
$error = ["service_id" => "", "appointment_date" => "", "appointment_time" => ""];
$submit_error = "";
$submit_success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $appointment["service_id"] = trim(htmlspecialchars($_POST["service_id"]));
    $appointment["appointment_date"] = trim(htmlspecialchars($_POST["appointment_date"]));
    $appointment["appointment_time"] = trim(htmlspecialchars($_POST["appointment_time"]));
    $appointment["notes"] = trim(htmlspecialchars($_POST["notes"]));

    // Validation
    if (empty($appointment["service_id"]))
        $error["service_id"] = "Please select a service";

    if (empty($appointment["appointment_date"]))
        $error["appointment_date"] = "Date is required";
    elseif ($appointment["appointment_date"] < date("Y-m-d"))
        $error["appointment_date"] = "Date cannot be in the past";

    if (empty($appointment["appointment_time"]))
        $error["appointment_time"] = "Time is required";

    // If no errors, proceed with booking
    if (empty(array_filter($error))) {
        // Check available staff capacity
        $available_staff_count = $appointmentObj->getAvailableStaffCount($appointment["appointment_date"], $appointment["appointment_time"]);
        
        if ($available_staff_count <= 0) {
            $submit_error = "Sorry! All staff members are fully booked for this time slot. Please choose another time.";
        } else {
            $appointmentObj->user_id = $_SESSION['user_id'];
            $appointmentObj->service_id = $appointment["service_id"];
            $appointmentObj->appointment_date = $appointment["appointment_date"];
            $appointmentObj->appointment_time = $appointment["appointment_time"];
            $appointmentObj->notes = $appointment["notes"];
            $appointmentObj->status = "pending";
            $appointmentObj->staff_id = null; // Staff will be assigned when approved

            if ($appointmentObj->bookAppointment()) {
                $submit_success = "Appointment booked successfully! Available staff: $available_staff_count. Waiting for admin approval and staff assignment.";
                $appointment = ["service_id" => "", "appointment_date" => "", "appointment_time" => "", "notes" => ""];
                $service = null;
            } else {
                $submit_error = "Booking failed. Please try again.";
            }
        }
    } else {
        $submit_error = "Please fill out all required fields";
    }
}

$services = $serviceObj->viewServices();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
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

        label {
            display: block;
            font-weight: bold;
            margin-top: 15px;
            color: #333;
        }

        label span {
            color: red;
        }

        select, input[type="date"], input[type="time"], textarea {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 15px;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        input:focus, select:focus, textarea:focus {
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

        .service-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .service-info p {
            margin: 5px 0;
            color: #666;
        }

        .service-info strong {
            color: #333;
        }

        .availability-info {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .availability-info.full {
            background: #ffe7e7;
            border-left-color: #f44336;
        }

        .availability-info.warning {
            background: #fff9e7;
            border-left-color: #ff9800;
        }

        input[type="submit"] {
            margin-top: 25px;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #764ba2 0%, #764ba2 100%);
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

        input[type="submit"]:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            display: block;
            margin: 15px auto 0;
            padding: 12px 24px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Book Appointment</h1>

        <form action="" method="post">
            <label for="service_id">Select Service <span>*</span></label>
            <select name="service_id" id="service_id" onchange="this.form.submit()">
                <option value="">-- Select Service --</option>
                <?php foreach ($services as $s): ?>
                    <option value="<?php echo $s['id']; ?>" <?php echo ($appointment['service_id'] == $s['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s['service_name']); ?> - ₱<?php echo number_format($s['price'], 2); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="error"><?php echo $error["service_id"]; ?></p>

            <?php if ($service): ?>
                <div class="service-info">
                    <p><strong>Service:</strong> <?php echo htmlspecialchars($service['service_name']); ?></p>
                    <p><strong>Price:</strong> ₱<?php echo number_format($service['price'], 2); ?></p>
                    <p><strong>Duration:</strong> <?php echo $service['duration']; ?> minutes</p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($service['description']); ?></p>
                </div>
            <?php endif; ?>

            <label for="appointment_date">Appointment Date <span>*</span></label>
            <input type="date" name="appointment_date" id="appointment_date" 
                   value="<?php echo $appointment['appointment_date']; ?>" 
                   min="<?php echo date('Y-m-d'); ?>">
            <p class="error"><?php echo $error["appointment_date"]; ?></p>

            <label for="appointment_time">Appointment Time <span>*</span></label>
            <input type="time" name="appointment_time" id="appointment_time" 
                   value="<?php echo $appointment['appointment_time']; ?>" 
                   min="09:00" max="18:00">
            <p class="error"><?php echo $error["appointment_time"]; ?></p>
            <small style="color: #666;">Business hours: 9:00 AM - 6:00 PM</small>

            <label for="notes">Additional Notes</label>
            <textarea name="notes" id="notes" placeholder="Any special requests or notes..."><?php echo $appointment['notes']; ?></textarea>

            <input type="submit" value="Book Appointment">

            <?php if($submit_error): ?>
                <p class="error" style="text-align: center; margin-top: 10px;"><?php echo $submit_error; ?></p>
            <?php endif; ?>

            <?php if($submit_success): ?>
                <p class="success"><?php echo $submit_success; ?></p>
            <?php endif; ?>
        </form>

        <a href="index.php" class="btn-secondary">Back to Services</a>
        <a href="myAppointments.php" class="btn-secondary">View My Appointments</a>
    </div>
</body>
</html>