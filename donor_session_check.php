<?php
session_start();

if (!isset($_SESSION['donor_id'])) {
    header("Location: donor_login.php");
    exit();
}
?>