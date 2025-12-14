<?php
    
$session_path = __DIR__ . '/sessions';
if (!is_dir($session_path)) {
    @mkdir($session_path, 0700, true);
}
ini_set('session.save_path', $session_path);
    
session_start();
session_destroy();
header("Location: login.php");
exit();
?>