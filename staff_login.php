<?php
session_start();

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the submitted username and password
    $username = trim($_POST['username'] ?? '');
    // Get password directly - don't process it yet (passwords may have spaces)
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        // Connect to DB
        include 'db_connect.php';

        if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            // Query to get staff with matching username from database
            // Note: MySQL table names are case-sensitive on Linux but not on Windows
            $stmt = $conn->prepare("SELECT Staff_ID, Username, Password, Role FROM Staff WHERE Username = ?");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // Staff member found in database - get the data
                    $staff = $result->fetch_assoc();
                    
                    // Debug: Show what we got from database
                    // $errorMessage = 'Debug: Found user. Password in DB: ' . substr($staff['Password'], 0, 10) . '... | Input: ' . $password;

                    // Get the stored password from database
                    $storedPassword = $staff['Password'];

                    // Check if password exists in database
                    if (empty($storedPassword)) {
                        $errorMessage = 'Password not set for this account. Please contact administrator.';
                    } else {
                        // Compare the input password with stored password
                        // Check if password is hashed or plain text
                        if (preg_match('/^\$2[ayb]\$/', $storedPassword)) {
                            // Password is hashed - use password_verify
                            $passwordMatch = password_verify($password, $storedPassword);
                        } else {
                            // Password is plain text - direct comparison
                            $passwordMatch = ($password === $storedPassword);
                        }

                        if ($passwordMatch) {
                            // Login successful - store data in session
                            $_SESSION['staff_id'] = $staff['Staff_ID'];
                            $_SESSION['username'] = $staff['Username'];
                            $_SESSION['role'] = $staff['Role'];

                            $stmt->close();
                            $conn->close();

                            // Redirect to staff dashboard
                            header("Location: staff_dashboard.php");
                            exit();
                        } else {
                            // Wrong password
                            $errorMessage = 'Invalid Username or Password.';
                        }
                    }
                } else {
                    // Staff does not exist
                    $errorMessage = 'Invalid Username or Password. Username entered: ' . htmlspecialchars($username);
                }
                $stmt->close();
            } else {
                $errorMessage = 'Database query error: ' . (isset($conn) ? $conn->error : 'Connection not established');
            }
            if (isset($conn)) {
                $conn->close();
            }
        } else {
            $errorMessage = 'Database connection failed. ' . (isset($conn) ? 'Connection error: ' . $conn->connect_error : 'No connection object');
        }
    } elseif (empty($username)) {
        $errorMessage = 'Please enter your username.';
    } else {
        $errorMessage = 'Please enter your password.';
    }
}

include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($errorMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-4 text-center">Staff Login</h3>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
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