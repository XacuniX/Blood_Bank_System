<?php
session_start();

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the submitted phone number and password
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    // Get password directly - don't process it yet (passwords may have spaces)
    $password = $_POST['password'] ?? '';
    
    if (!empty($phoneNumber) && !empty($password)) {
        // Connect to DB (suppress debug echo from db_connect.php)
        ob_start();
        include 'db_connect.php';
        ob_end_clean();
        
        if ($conn instanceof mysqli && !$conn->connect_error) {
            // Query to get hospital with matching phone number
            // Use SELECT * to get all columns and handle any column name case
            $stmt = $conn->prepare("SELECT * FROM Hospital WHERE Phone = ?");
            if ($stmt) {
                $stmt->bind_param("s", $phoneNumber);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Hospital exists - get all hospital data
                    $hospital = $result->fetch_assoc();
                    
                    // Handle different possible column name cases (Password, password, PASSWORD)
                    // MySQL column names are case-insensitive, but array keys in PHP are case-sensitive
                    $storedPassword = null;
                    // Try common variations
                    foreach (['Password', 'password', 'PASSWORD', 'pass', 'Pass'] as $colName) {
                        if (isset($hospital[$colName])) {
                            $storedPassword = $hospital[$colName];
                            break;
                        }
                    }
                    
                    // If still not found, check all keys (case-insensitive search)
                    if ($storedPassword === null) {
                        foreach ($hospital as $key => $value) {
                            if (strtolower($key) === 'password') {
                                $storedPassword = $value;
                                break;
                            }
                        }
                    }
                    
                    // Check if password is NULL or empty
                    if ($storedPassword === null || $storedPassword === '') {
                        $errorMessage = 'Password not set for this account. Please contact administrator.';
                    } else {
                        // Verify password - check if it's hashed (starts with $2y$, $2a$, $2b$) or plain text
                        $passwordMatch = false;
                        $storedPassword = trim($storedPassword);
                        // Get password directly from POST to ensure we have the actual submitted value
                        $inputPassword = $_POST['password'] ?? '';
                        
                        if (!empty($inputPassword)) {
                            if (preg_match('/^\$2[ayb]\$/', $storedPassword)) {
                                // Password is hashed, use password_verify
                                $passwordMatch = password_verify($inputPassword, $storedPassword);
                            } else {
                                // Password is stored as plain text, do direct comparison
                                $passwordMatch = ($inputPassword === $storedPassword);
                            }
                            
                            if ($passwordMatch) {
                                // Password matches - set session and redirect
                                $_SESSION['hospital_id'] = $hospital['Hospital_ID'];
                                
                                // Get hospital name (try different possible column names)
                                $hospitalName = null;
                                foreach (['Hospital_Name', 'hospital_name', 'Name', 'name', 'HOSPITAL_NAME'] as $colName) {
                                    if (isset($hospital[$colName])) {
                                        $hospitalName = $hospital[$colName];
                                        break;
                                    }
                                }
                                
                                // If still not found, check all keys (case-insensitive search)
                                if ($hospitalName === null) {
                                    foreach ($hospital as $key => $value) {
                                        if (strtolower($key) === 'hospital_name' || strtolower($key) === 'name') {
                                            $hospitalName = $value;
                                            break;
                                        }
                                    }
                                }
                                
                                if ($hospitalName !== null) {
                                    $_SESSION['hospital_name'] = $hospitalName;
                                }
                                
                                $stmt->close();
                                $conn->close();
                                
                                // Redirect to hospital dashboard
                                header("Location: hospital_dashboard.php");
                                exit();
                            } else {
                                // Password does not match
                                $errorMessage = 'Invalid Phone Number or Password.';
                            }
                        } else {
                            $errorMessage = 'Password not received from form.';
                        }
                    }
                } else {
                    // Hospital does not exist
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
                    <h3 class="card-title mb-4 text-center">Hospital Login</h3>
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


