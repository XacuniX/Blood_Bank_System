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
                    } else {
                        // Verify password - check if it's hashed (starts with $2y$, $2a$, $2b$) or plain text
                        $passwordMatch = false;
                        $storedPassword = trim($storedPassword);
                        // Get password directly from POST to ensure we have the actual submitted value
                        $inputPassword = $_POST['password'] ?? '';
                        
                        if (empty($inputPassword)) {
                            $errorMessage = 'Please enter your password.';
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
                        <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#dc3545" class="bi bi-heart-pulse" viewBox="0 0 16 16">
                                <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053.918 3.995.78 5.323 1.508 7H.43c-2.128-5.697 4.165-8.83 7.394-5.857.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17c3.23-2.974 9.522.159 7.394 5.856h-1.078c.728-1.677.59-3.005.108-3.947C13.486.878 10.4.28 8.717 2.01L8 2.748ZM2.212 10h1.315C4.593 11.183 6.05 12.458 8 13.795c1.949-1.337 3.407-2.612 4.473-3.795h1.315c-1.265 1.566-3.14 3.25-5.788 5-2.648-1.75-4.523-3.434-5.788-5Z"/>
                                <path d="M10.464 3.314a.5.5 0 0 0-.945.049L7.921 8.956 6.464 5.314a.5.5 0 0 0-.88-.091L3.732 8H.5a.5.5 0 0 0 0 1H4a.5.5 0 0 0 .416-.223l1.473-2.209 1.647 4.118a.5.5 0 0 0 .945-.049l1.598-5.593 1.457 3.642A.5.5 0 0 0 12 9h3.5a.5.5 0 0 0 0-1h-3.162l-1.874-4.686Z"/>
                            </svg>
                        </div>
                    </div>
                    <h3 class="card-title mb-2 text-center fw-bold">Donor Login</h3>
                    <p class="text-center text-muted mb-4">Welcome back! Please login to your account</p>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="phone_number" class="form-label fw-semibold">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-telephone-fill text-danger"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 ps-0" id="phone_number" name="phone_number" placeholder="Enter your phone number" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-lock-fill text-danger"></i>
                                </span>
                                <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="Enter your password" required>
                            </div>
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-danger btn-lg py-2">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="text-muted mb-2">Don't have an account?</p>
                        <a href="donor_register.php" class="btn btn-outline-danger">
                            <i class="bi bi-person-plus"></i> Register as Donor
                        </a>
                    </div>
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

