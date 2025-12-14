<?php
// Session configuration
$session_path = __DIR__ . '/sessions';
if (!is_dir($session_path)) {
    @mkdir($session_path, 0700, true);
}
ini_set('session.save_path', $session_path);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Session Debug Tool</h1>";
echo "<hr>";

// Test 1: Session Status
echo "<h2>Test 1: Session Status</h2>";
echo "Session Status: ";
switch (session_status()) {
    case PHP_SESSION_DISABLED:
        echo "<span style='color: red;'>❌ DISABLED</span><br>";
        break;
    case PHP_SESSION_NONE:
        echo "<span style='color: orange;'>⚠ NOT STARTED</span><br>";
        break;
    case PHP_SESSION_ACTIVE:
        echo "<span style='color: green;'>✅ ACTIVE</span><br>";
        break;
}

echo "<hr>";

// Test 2: Session ID
echo "<h2>Test 2: Session ID</h2>";
echo "Session ID: " . session_id() . "<br>";

echo "<hr>";

// Test 3: Session Data
echo "<h2>Test 3: Session Data</h2>";
if (empty($_SESSION)) {
    echo "<span style='color: orange;'>⚠ No session data found</span><br>";
    echo "<p>This is normal if you haven't logged in yet.</p>";
} else {
    echo "<span style='color: green;'>✅ Session data exists:</span><br>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}

echo "<hr>";

// Test 4: Session Save Path
echo "<h2>Test 4: Session Save Path</h2>";
echo "Session Save Path: " . session_save_path() . "<br>";
echo "Directory Exists: " . (is_dir(session_save_path()) ? "✅ Yes" : "❌ No") . "<br>";
echo "Is Writable: " . (is_writable(session_save_path()) ? "✅ Yes" : "❌ No") . "<br>";

echo "<hr>";

// Test 5: Session Files
echo "<h2>Test 5: Session Files</h2>";
if (is_dir(session_save_path())) {
    $files = scandir(session_save_path());
    $session_files = array_filter($files, function($file) {
        return strpos($file, 'sess_') === 0;
    });
    
    if (count($session_files) > 0) {
        echo "Session files found: " . count($session_files) . "<br>";
        echo "<ul>";
        foreach ($session_files as $file) {
            echo "<li>" . $file . "</li>";
        }
        echo "</ul>";
    } else {
        echo "No session files found<br>";
    }
}

echo "<hr>";

// Test 6: Login Test
echo "<h2>Test 6: Manual Login Test</h2>";
if (isset($_GET['test_login'])) {
    // Simulate login
    $_SESSION['user_id'] = 999;
    $_SESSION['full_name'] = 'Test User';
    $_SESSION['email'] = 'test@test.com';
    $_SESSION['role'] = 'admin';
    
    echo "<span style='color: green;'>✅ Test session data set!</span><br>";
    echo "<a href='debug_session.php'>Refresh to see if session persists</a><br>";
} else {
    echo "<a href='?test_login=1'>Click here to test session setting</a><br>";
}

echo "<hr>";

// Test 7: Header Information
echo "<h2>Test 7: Server Information</h2>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Current URL: " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "<br>";

echo "<hr>";

// Test 8: Clear Session
echo "<h2>Test 8: Clear Session</h2>";
if (isset($_GET['clear'])) {
    session_destroy();
    echo "<span style='color: green;'>✅ Session cleared!</span><br>";
    echo "<a href='debug_session.php'>Reload page</a><br>";
} else {
    echo "<a href='?clear=1'>Click here to clear session</a><br>";
}

echo "<hr>";

// Test 9: Redirect Test
echo "<h2>Test 9: Redirect Loop Test</h2>";
echo "<p>If you're experiencing redirect loops, check:</p>";
echo "<ol>";
echo "<li>Make sure login.php has exit() after header() redirects ✓</li>";
echo "<li>Make sure dashboard.php checks session properly ✓</li>";
echo "<li>Clear all cookies and cache ⚠</li>";
echo "<li>Session data persists across page loads ⚠</li>";
echo "</ol>";

if (isset($_SESSION['user_id'])) {
    echo "<p style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "✅ You are currently logged in as: <strong>" . htmlspecialchars($_SESSION['full_name']) . "</strong><br>";
    echo "Role: <strong>" . htmlspecialchars($_SESSION['role']) . "</strong><br>";
    echo "<a href='admin/dashboard.php'>Try accessing dashboard</a>";
    echo "</p>";
} else {
    echo "<p style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
    echo "⚠ You are not logged in<br>";
    echo "<a href='login.php'>Go to login page</a>";
    echo "</p>";
}
?>