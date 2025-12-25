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

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <?php if (!empty($errorMessage)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($errorMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#0d6efd" class="bi bi-hospital" viewBox="0 0 16 16">
                                <path d="M8.5 5.034v1.1l.953-.55.5.867L9 7l.953.55-.5.866-.953-.55v1.1h-1v-1.1l-.953.55-.5-.866L7 7l-.953-.55.5-.866.953.55v-1.1h1ZM13.25 9a.25.25 0 0 0-.25.25v.5c0 .138.112.25.25.25h.5a.25.25 0 0 0 .25-.25v-.5a.25.25 0 0 0-.25-.25h-.5ZM13 11.25a.25.25 0 0 1 .25-.25h.5a.25.25 0 0 1 .25.25v.5a.25.25 0 0 1-.25.25h-.5a.25.25 0 0 1-.25-.25v-.5Zm.25 1.75a.25.25 0 0 0-.25.25v.5c0 .138.112.25.25.25h.5a.25.25 0 0 0 .25-.25v-.5a.25.25 0 0 0-.25-.25h-.5Zm-11-4a.25.25 0 0 0-.25.25v.5c0 .138.112.25.25.25h.5A.25.25 0 0 0 3 9.75v-.5A.25.25 0 0 0 2.75 9h-.5Zm0 2a.25.25 0 0 0-.25.25v.5c0 .138.112.25.25.25h.5a.25.25 0 0 0 .25-.25v-.5a.25.25 0 0 0-.25-.25h-.5ZM2 13.25a.25.25 0 0 1 .25-.25h.5a.25.25 0 0 1 .25.25v.5a.25.25 0 0 1-.25.25h-.5a.25.25 0 0 1-.25-.25v-.5Z"/>
                                <path d="M5 1a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1a1 1 0 0 1 1 1v4h3a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1h3V3a1 1 0 0 1 1-1V1Zm2 14h2v-3H7v3Zm3 0h1V3H5v12h1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3Zm0-14H6v1h4V1Zm2 7v7h3V8h-3Zm-8 7V8H1v7h3Z"/>
                            </svg>
                        </div>
                    </div>
                    <h3 class="card-title mb-2 text-center fw-bold">Hospital Login</h3>
                    <p class="text-center text-muted mb-4">Access your hospital dashboard</p>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="phone_number" class="form-label fw-semibold">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-telephone-fill text-primary"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 ps-0" id="phone_number" name="phone_number" placeholder="Enter hospital phone number" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-lock-fill text-primary"></i>
                                </span>
                                <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="Enter your password" required>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg py-2">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="index.php" class="text-muted text-decoration-none">
                    <i class="bi bi-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


