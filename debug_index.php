<?php
// Temporary debug file - upload this as debug_index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h2>Starting Debug...</h2>";

echo "<p>1. PHP is working ✓</p>";

echo "<p>2. Testing Service.php require...</p>";
try {
    require_once "Service.php";
    echo "<p>Service.php loaded ✓</p>";
} catch (Exception $e) {
    die("<p style='color:red'>Service.php ERROR: " . $e->getMessage() . "</p>");
}

echo "<p>3. Testing Service instantiation...</p>";
try {
    $serviceObj = new Service();
    echo "<p>Service object created ✓</p>";
} catch (Exception $e) {
    die("<p style='color:red'>Service instantiation ERROR: " . $e->getMessage() . "</p>");
}

echo "<p>4. Testing database connection...</p>";
try {
    $conn = $serviceObj->connect();
    echo "<p>Database connected ✓</p>";
} catch (Exception $e) {
    die("<p style='color:red'>DATABASE CONNECTION ERROR: " . $e->getMessage() . "</p>");
}

echo "<p>5. Testing viewServices()...</p>";
try {
    $services = $serviceObj->viewServices();
    echo "<p>Services retrieved: " . count($services) . " services ✓</p>";
} catch (Exception $e) {
    die("<p style='color:red'>viewServices ERROR: " . $e->getMessage() . "</p>");
}

echo "<h2>All tests passed! ✓</h2>";
echo "<p>Your index.php should work. If it doesn't, check:</p>";
echo "<ul>";
echo "<li>Session errors</li>";
echo "<li>File permissions</li>";
echo "<li>Missing User.php file</li>";
echo "</ul>";
?>