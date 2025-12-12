<?php
session_start();

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the submitted phone number
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    
    if (!empty($phoneNumber)) {
        // Connect to DB (suppress debug echo from db_connect.php)
        ob_start();
        include 'db_connect.php';
        ob_end_clean();
        
        if ($conn instanceof mysqli && !$conn->connect_error) {
            // Query to check if donor exists with matching phone number
            $stmt = $conn->prepare("SELECT Donor_ID FROM Donor WHERE Phone_Number = ?");
            if ($stmt) {
                $stmt->bind_param("s", $phoneNumber);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Donor exists - get the Donor_ID
                    $donor = $result->fetch_assoc();
                    $_SESSION['donor_id'] = $donor['Donor_ID'];
                    $stmt->close();
                    $conn->close();
                    
                    // Redirect to donor dashboard
                    header("Location: donor_dashboard.php");
                    exit();
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
    } else {
        $errorMessage = 'Please enter your phone number.';
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

