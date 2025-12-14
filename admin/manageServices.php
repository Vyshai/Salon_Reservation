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

$search = isset($_GET['search']) ? trim(htmlspecialchars($_GET['search'])) : "";
$services = $serviceObj->viewServices($search);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services</title>
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

        .search-section {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .search-form {
            display: flex;
            gap: 10px;
        }

        .search-form input[type="search"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 15px;
            min-width: 250px;
        }

        .search-form input[type="submit"] {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            font-weight: bold;
        }

        .add-btn {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
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

        .btn-edit {
            background: #ffc107;
            color: #000;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .no-services {
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Services</h1>

        <div class="search-section">
            <form class="search-form" method="get">
                <input type="search" name="search" placeholder="Search services..." value="<?= $search; ?>">
                <input type="submit" value="Search">
            </form>
            <a href="addService.php" class="add-btn">+ Add New Service</a>
        </div>

        <?php if (count($services) > 0): ?>
            <table>
                <tr>
                    <th>No.</th>
                    <th>Service Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Duration (mins)</th>
                    <th>Actions</th>
                </tr>
                <?php 
                $no = 1;
                foreach ($services as $service): 
                ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($service['service_name']); ?></td>
                        <td><?= htmlspecialchars($service['description']); ?></td>
                        <td>₱<?= number_format($service['price'], 2); ?></td>
                        <td><?= $service['duration']; ?></td>
                        <td>
                            <a href="editService.php?id=<?= $service['id']; ?>" class="action-btn btn-edit">Edit</a>
                            <a href="deleteService.php?id=<?= $service['id']; ?>" 
                               class="action-btn btn-delete"
                               onclick="return confirm('Are you sure you want to delete this service?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <div class="no-services">
                <p>No services found.</p>
            </div>
        <?php endif; ?>

        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>