<?php
// This MUST be the first line of every page you want to protect
session_start();

// Check if the user is NOT logged in (if the session variable is empty)
if (!isset($_SESSION['donor_id'])) {
    // If they aren't logged in, redirect them back to the login page
    header("Location: login.php");
    exit(); // Stop the script immediately
}
// If the session variable IS set, the script continues and the user sees the page.
?>