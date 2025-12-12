<?php
session_start();
include 'session_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';
    
    if (empty($field) || empty($value)) {
        $_SESSION['error'] = 'Invalid data provided.';
        header("Location: donor_dashboard.php");
        exit();
    }
    
    // Connect to DB
    ob_start();
    include 'db_connect.php';
    ob_end_clean();
    
    if ($conn instanceof mysqli && !$conn->connect_error) {
        $donor_id = $_SESSION['donor_id'];
        
        if ($field === 'phone') {
            // Update phone number
            $stmt = $conn->prepare("UPDATE Donor SET Phone_Number = ? WHERE Donor_ID = ?");
            if ($stmt) {
                $stmt->bind_param("si", $value, $donor_id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Phone number updated successfully.';
                } else {
                    $_SESSION['error'] = 'Failed to update phone number: ' . $stmt->error;
                }
                $stmt->close();
            }
        } elseif ($field === 'password') {
            // Hash the password before storing
            $hashedPassword = password_hash($value, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE Donor SET Password = ? WHERE Donor_ID = ?");
            if ($stmt) {
                $stmt->bind_param("si", $hashedPassword, $donor_id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Password updated successfully.';
                } else {
                    $_SESSION['error'] = 'Failed to update password: ' . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $_SESSION['error'] = 'Invalid field specified.';
        }
        
        $conn->close();
    } else {
        $_SESSION['error'] = 'Database connection failed.';
    }
    
    header("Location: donor_dashboard.php");
    exit();
} else {
    header("Location: donor_dashboard.php");
    exit();
}
?>

