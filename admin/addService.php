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

require_once "../Service.php";
$serviceObj = new Service();

$service = ["service_name" => "", "description" => "", "price" => "", "duration" => ""];
$error = ["service_name" => "", "description" => "", "price" => "", "duration" => ""];
$submit_error = "";
$submit_success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service["service_name"] = trim(htmlspecialchars($_POST["service_name"]));
    $service["description"] = trim(htmlspecialchars($_POST["description"]));
    $service["price"] = trim(htmlspecialchars($_POST["price"]));
    $service["duration"] = trim(htmlspecialchars($_POST["duration"]));

    // Validation
    if (empty($service["service_name"]))
        $error["service_name"] = "Service name is required";

    if (empty($service["description"]))
        $error["description"] = "Description is required";

    if (empty($service["price"]))
        $error["price"] = "Price is required";
    elseif (!is_numeric($service["price"]))
        $error["price"] = "Price must be a number";
    elseif ($service["price"] < 0)
        $error["price"] = "Price must be positive";

    if (empty($service["duration"]))
        $error["duration"] = "Duration is required";
    elseif (!is_numeric($service["duration"]))
        $error["duration"] = "Duration must be a number";
    elseif ($service["duration"] < 1)
        $error["duration"] = "Duration must be at least 1 minute";

    // If no errors, proceed with adding
    if (empty(array_filter($error))) {
        if ($serviceObj->serviceExists($service["service_name"])) {
            $submit_error = "Service name already exists";
        } else {
            $serviceObj->service_name = $service["service_name"];
            $serviceObj->description = $service["description"];
            $serviceObj->price = $service["price"];
            $serviceObj->duration = $service["duration"];

            if ($serviceObj->addService()) {
                $submit_success = "Service added successfully!";
                $service = ["service_name" => "", "description" => "", "price" => "", "duration" => ""];
            } else {
                $submit_error = "Failed to add service. Please try again.";
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
    <title>Add Service</title>
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

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 15px;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        input:focus, textarea:focus {
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
        <h1>Add New Service</h1>

        <form action="" method="post">
            <label for="service_name">Service Name <span>*</span></label>
            <input type="text" name="service_name" id="service_name" value="<?= $service["service_name"]; ?>">
            <p class="error"><?= $error["service_name"]; ?></p>

            <label for="description">Description <span>*</span></label>
            <textarea name="description" id="description"><?= $service["description"]; ?></textarea>
            <p class="error"><?= $error["description"]; ?></p>

            <label for="price">Price (₱) <span>*</span></label>
            <input type="number" name="price" id="price" step="0.01" value="<?= $service["price"]; ?>">
            <p class="error"><?= $error["price"]; ?></p>

            <label for="duration">Duration (minutes) <span>*</span></label>
            <input type="number" name="duration" id="duration" value="<?= $service["duration"]; ?>">
            <p class="error"><?= $error["duration"]; ?></p>

            <input type="submit" value="Add Service">

            <?php if($submit_error): ?>
                <p class="error" style="text-align: center; margin-top: 10px;"><?= $submit_error; ?></p>
            <?php endif; ?>

            <?php if($submit_success): ?>
                <p class="success"><?= $submit_success; ?></p>
            <?php endif; ?>
        </form>

        <a href="manageServices.php" class="back-link">Back to Services</a>
    </div>
</body>
</html>