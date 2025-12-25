<?php
session_start();

if (!isset($_SESSION['hospital_id'])) {
    header("Location: hospital_login.php");
    exit();
}
?>

