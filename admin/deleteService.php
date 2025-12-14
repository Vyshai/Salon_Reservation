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

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET["id"])) {
        $service_id = trim(htmlspecialchars($_GET["id"]));
        $service = $serviceObj->getServiceById($service_id);
        
        if (!$service) {
            header("Location: manageServices.php");
            exit();
        } else {
            $serviceObj->deleteService($service_id);
            header("Location: manageServices.php");
            exit();
        }
    } else {
        header("Location: manageServices.php");
        exit();
    }
}
?>