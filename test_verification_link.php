<?php
// Test what URL will be generated
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$test_token = "test123";
$verification_link = $protocol . $domain . "/verifyEmail.php?token=" . $test_token;

echo "<h2>Testing Verification Link Generation</h2>";
echo "<p><strong>Protocol:</strong> " . $protocol . "</p>";
echo "<p><strong>Domain:</strong> " . $domain . "</p>";
echo "<p><strong>Full Link:</strong> <a href='" . $verification_link . "'>" . $verification_link . "</a></p>";
echo "<hr>";
echo "<p>Expected: https://salonreservation.page.gd/verifyEmail.php?token=test123</p>";
?>