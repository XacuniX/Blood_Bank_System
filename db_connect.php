<?php
// 1. Database Credentials
// Since you are on XAMPP, these are the default settings:
$servername = "localhost"; // Your computer acts as the server
$username = "root";        // Default XAMPP user
$password = "";            // Default XAMPP password is empty
$dbname = "bloodbank"; // The name of the database you created in Phase 1

// 2. Create Connection
// We use "mysqli" (MySQL Improved), which is the standard way to connect in PHP.
$conn = new mysqli($servername, $username, $password, $dbname);

// 3. Check Connection
// If there is an error (like a wrong password or database name), stop everything and show the error.
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Uncomment the line below to test if it works. 
echo "Connected successfully"; 
?>