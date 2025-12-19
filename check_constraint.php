<?php
require_once 'db_connect.php';

// Get table structure
$result = $conn->query("SHOW CREATE TABLE request");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<h3>Request Table Structure:</h3>";
    echo "<pre>" . htmlspecialchars($row['Create Table']) . "</pre>";
}

// Try to see current status values in use
echo "<h3>Current Status Values in Request Table:</h3>";
$status_result = $conn->query("SELECT DISTINCT Status FROM request ORDER BY Status");
if ($status_result) {
    while ($row = $status_result->fetch_assoc()) {
        echo "- " . htmlspecialchars($row['Status']) . "<br>";
    }
}

$conn->close();
?>
