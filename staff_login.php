<?php
require 'audit_logger.php';
session_start();

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_user = trim($_POST['username'] ?? '');
    $input_password = $_POST['password'] ?? '';

    if (!empty($input_user) && !empty($input_password)) {
        include 'db_connect.php';

        if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            $stmt = $conn->prepare("SELECT Staff_ID, Username, Password, Role FROM Staff WHERE Username = ?");
            if ($stmt) {
                $stmt->bind_param("s", $input_user);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $staff = $result->fetch_assoc();
                    $storedPassword = $staff['Password'];

                    if (empty($storedPassword)) {
                        $errorMessage = 'Password not set for this account. Please contact administrator.';
                    } else {
                        // Check if password is hashed or plain text
                        if (preg_match('/^\$2[ayb]\$/', $storedPassword)) {
                            $passwordMatch = password_verify($input_password, $storedPassword);
                        } else {
                            $passwordMatch = ($input_password === $storedPassword);
                        }

                        if ($passwordMatch) {
                            $_SESSION['staff_id'] = $staff['Staff_ID'];
                            $_SESSION['username'] = $staff['Username'];
                            $_SESSION['role'] = $staff['Role'];

                            log_activity($conn, $staff['Username'], 'Staff', 'LOGIN', 'Staff', $staff['Staff_ID'], 'User logged in');

                            $stmt->close();
                            $conn->close();

                            // Redirect based on role
                            $role = $staff['Role'];
                            if ($role === 'Officer') {
                                header("Location: staff_officer_dashboard.php");
                            } elseif ($role === 'Manager') {
                                header("Location: staff_manager_dashboard.php");
                            } elseif ($role === 'Admin') {
                                header("Location: staff_admin_dashboard.php");
                            } else {
                                header("Location: staff_dashboard.php");
                            }
                            exit();
                        } else {
                            $errorMessage = 'Invalid Username or Password.';
                        }
                    }
                } else {
                    $errorMessage = 'Invalid Username or Password.';
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
    } elseif (empty($input_user)) {
        $errorMessage = 'Please enter your username.';
    } else {
        $errorMessage = 'Please enter your password.';
    }
}

include 'includes/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($errorMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-dark bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#212529" class="bi bi-person-badge" viewBox="0 0 16 16">
                                <path d="M6.5 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1h-3zM11 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                <path d="M4.5 0A2.5 2.5 0 0 0 2 2.5V14a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2.5A2.5 2.5 0 0 0 11.5 0h-7zM3 2.5A1.5 1.5 0 0 1 4.5 1h7A1.5 1.5 0 0 1 13 2.5v10.795a4.2 4.2 0 0 0-.776-.492C11.392 12.387 10.063 12 8 12s-3.392.387-4.224.803a4.2 4.2 0 0 0-.776.492V2.5z"/>
                            </svg>
                        </div>
                    </div>
                    <h3 class="card-title mb-2 text-center fw-bold">Staff Login</h3>
                    <p class="text-center text-muted mb-4">Access blood bank management system</p>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-person-fill text-dark"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 ps-0" id="username" name="username" placeholder="Enter your username" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-lock-fill text-dark"></i>
                                </span>
                                <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="Enter your password" required>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-dark btn-lg py-2">
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