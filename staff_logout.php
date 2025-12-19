<?php
session_start();

// Destroy all session data
$_SESSION = array();
session_destroy();

// Redirect to staff login page
header("Location: staff_login.php");
exit();
?>