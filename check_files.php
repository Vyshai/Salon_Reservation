<?php
// Upload this as check_files.php and visit it

echo "<h2>Checking Required Files...</h2>";

$required_files = [
    'database.php',
    'Service.php',
    'User.php',
    'Order.php',
    'Notification.php',
    'EmailService.php',
    'index.php',
    'login.php',
    'register.php',
    'createOrder.php'
];

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>File</th><th>Status</th><th>Location</th></tr>";

foreach ($required_files as $file) {
    $exists = file_exists($file);
    $status = $exists ? "✅ EXISTS" : "❌ MISSING";
    $color = $exists ? "green" : "red";
    $path = $exists ? realpath($file) : "Not found";
    
    echo "<tr>";
    echo "<td><strong>$file</strong></td>";
    echo "<td style='color: $color; font-weight: bold;'>$status</td>";
    echo "<td style='font-size: 11px;'>$path</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Current Directory:</h3>";
echo "<p>" . getcwd() . "</p>";

echo "<h3>All Files in Current Directory:</h3>";
echo "<pre>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo $file . "\n";
    }
}
echo "</pre>";
?>