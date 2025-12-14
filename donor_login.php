<?php
session_start();

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the submitted phone number and password
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    // Get password directly - don't process it yet (passwords may have spaces)
    $password = $_POST['password'] ?? '';
    
    // Debug: Check what we received
    if (empty($password)) {
        $errorMessage = 'Debug: Password field is empty. POST keys: ' . implode(', ', array_keys($_POST)) . ' | Password value: [' . var_export($password, true) . ']';
    } elseif (!empty($phoneNumber) && !empty($password)) {
        // Connect to DB (suppress debug echo from db_connect.php)
        ob_start();
        include 'db_connect.php';
        ob_end_clean();
        
        if ($conn instanceof mysqli && !$conn->connect_error) {
            // Query to get donor with matching phone number (including password)
            // Use SELECT * to get all columns and handle any column name case
            $stmt = $conn->prepare("SELECT * FROM Donor WHERE Phone_Number = ?");
            if ($stmt) {
                $stmt->bind_param("s", $phoneNumber);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Donor exists - get all donor data
                    $donor = $result->fetch_assoc();
                    
                    // Handle different possible column name cases (Password, password, PASSWORD)
                    // MySQL column names are case-insensitive, but array keys in PHP are case-sensitive
                    $storedPassword = null;
                    // Try common variations
                    foreach (['Password', 'password', 'PASSWORD', 'pass', 'Pass'] as $colName) {
                        if (isset($donor[$colName])) {
                            $storedPassword = $donor[$colName];
                            break;
                        }
                    }
                    
                    // If still not found, check all keys (case-insensitive search)
                    if ($storedPassword === null) {
                        foreach ($donor as $key => $value) {
                            if (strtolower($key) === 'password') {
                                $storedPassword = $value;
                                break;
                            }
                        }
                    }
                    
                    // Check if password is NULL or empty
                    if ($storedPassword === null || $storedPassword === '') {
                        $errorMessage = 'Password not set for this account. Please contact administrator.';
                        // Debug: Show available columns to help diagnose
                        $errorMessage .= ' Available columns: ' . implode(', ', array_keys($donor));
                    } else {
                        // Verify password - check if it's hashed (starts with $2y$, $2a$, $2b$) or plain text
                        $passwordMatch = false;
                        $storedPassword = trim($storedPassword);
                        // Get password directly from POST to ensure we have the actual submitted value
                        $inputPassword = $_POST['password'] ?? '';
                        
                        // Debug: Check what we have
                        if (empty($inputPassword)) {
                            $errorMessage = 'Password not received from form. POST keys: ' . implode(', ', array_keys($_POST));
                        } else {
                            if (preg_match('/^\$2[ayb]\$/', $storedPassword)) {
                                // Password is hashed, use password_verify
                                $passwordMatch = password_verify($inputPassword, $storedPassword);
                            } else {
                                // Password is stored as plain text, do direct comparison
                                $passwordMatch = ($inputPassword === $storedPassword);
                            }
                            
                            if ($passwordMatch) {
                                // Password matches - set session and redirect
                                $_SESSION['donor_id'] = $donor['Donor_ID'];
                                $stmt->close();
                                $conn->close();
                                
                                // Redirect to donor dashboard
                                header("Location: donor_dashboard.php");
                                exit();
                            } else {
                                // Password does not match
                                $errorMessage = 'Invalid Phone Number or Password.';
                                // Debug: Show comparison details to help diagnose
                                $errorMessage .= ' (Input length: ' . strlen($inputPassword) . ', Stored length: ' . strlen($storedPassword) . ', First 5 chars match: ' . (substr($inputPassword, 0, 5) === substr($storedPassword, 0, 5) ? 'yes' : 'no') . ')';
                            }
                        }
                    }
                } else {
                    // Donor does not exist
                    $errorMessage = 'Invalid Phone Number or Password.';
                }
                $stmt->close();
            } else {
                $errorMessage = 'Database query error: ' . $conn->error;
            }
            $conn->close();
        } else {
            $errorMessage = 'Database connection failed.';
        }
    } elseif (empty($phoneNumber)) {
        $errorMessage = 'Please enter your phone number.';
    } else {
        $errorMessage = 'Please enter your password.';
    }
}

include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <?php if (!empty($errorMessage)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($errorMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-4 text-center">Donor Login</h3>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="phone_number" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone_number" name="phone_number" required>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger">Login</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

