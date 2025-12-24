<?php
require 'audit_logger.php';
session_start();

// STRICT Access Control - Admin Only
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: staff_login.php");
    exit();
}

include 'db_connect.php';

$successMessage = '';
$errorMessage = '';

// Handle Add New Staff
if (isset($_POST['add_staff'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    if (!empty($username) && !empty($password) && !empty($role)) {
        if ($conn instanceof mysqli && !$conn->connect_error) {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT Staff_ID FROM Staff WHERE Username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $errorMessage = 'Username already exists. Please choose a different username.';
                $stmt->close();
            } else {
                $stmt->close();
                
                // Hash the password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new staff
                $stmt = $conn->prepare("INSERT INTO Staff (Username, Password, Role) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $hashedPassword, $role);
                
                if ($stmt->execute()) {
                    $new_staff_id = $conn->insert_id;
                    $successMessage = 'Staff member added successfully!';
                    
                    // Log the staff creation activity
                    $details = "New staff account created: Username={$username}, Role={$role}";
                    log_activity($conn, $_SESSION['username'], 'Staff', 'INSERT', 'Staff', $new_staff_id, $details);
                } else {
                    $errorMessage = 'Failed to add staff member: ' . $conn->error;
                }
                $stmt->close();
            }
        } else {
            $errorMessage = 'Database connection failed.';
        }
    } else {
        $errorMessage = 'All fields are required.';
    }
}

// Handle Delete Staff
if (isset($_POST['delete_staff'])) {
    $staffIdToDelete = $_POST['staff_id'] ?? 0;
    
    // Prevent admin from deleting their own account
    if ($staffIdToDelete == $_SESSION['staff_id']) {
        $errorMessage = 'You cannot delete your own account.';
    } else {
        if ($conn instanceof mysqli && !$conn->connect_error) {
            // First, get the staff details for audit logging
            $stmt = $conn->prepare("SELECT Username, Role FROM Staff WHERE Staff_ID = ?");
            $stmt->bind_param("i", $staffIdToDelete);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $deleted_username = 'Unknown';
            $deleted_role = 'Unknown';
            if ($result->num_rows > 0) {
                $staff_data = $result->fetch_assoc();
                $deleted_username = $staff_data['Username'];
                $deleted_role = $staff_data['Role'];
            }
            $stmt->close();
            
            // Now delete the staff member
            $stmt = $conn->prepare("DELETE FROM Staff WHERE Staff_ID = ?");
            $stmt->bind_param("i", $staffIdToDelete);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $successMessage = 'Staff member deleted successfully!';
                    
                    // Log the staff deletion activity
                    $details = "Staff account deleted: Username={$deleted_username}, Role={$deleted_role}";
                    log_activity($conn, $_SESSION['username'], 'Staff', 'DELETE', 'Staff', $staffIdToDelete, $details);
                } else {
                    $errorMessage = 'Staff member not found.';
                }
            } else {
                $errorMessage = 'Failed to delete staff member: ' . $conn->error;
            }
            $stmt->close();
        } else {
            $errorMessage = 'Database connection failed.';
        }
    }
}

// Get all staff members
$staffList = [];
if ($conn instanceof mysqli && !$conn->connect_error) {
    $result = $conn->query("SELECT Staff_ID, Username, Role FROM Staff ORDER BY Staff_ID ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $staffList[] = $row;
        }
    }
    $conn->close();
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Header with Back Button -->
    <div class="row mb-4">
        <div class="col-12">
            <a href="staff_admin_dashboard.php" class="btn btn-outline-secondary mb-3">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
            <h2 class="mb-0">User Management</h2>
            <p class="text-muted">Add new staff members and manage existing users</p>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (!empty($successMessage)) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($successMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($errorMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Add New Staff Section -->
    <div class="row mb-5">
        <div class="col-lg-8 col-xl-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title mb-4">
                        <i class="bi bi-person-plus-fill"></i> Add New Staff
                    </h4>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-4">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Select a role...</option>
                                <option value="Admin">Admin</option>
                                <option value="Manager">Manager</option>
                                <option value="Officer">Officer</option>
                            </select>
                        </div>
                        <button type="submit" name="add_staff" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Staff Member
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Existing Staff Section -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title mb-4">
                        <i class="bi bi-people-fill"></i> Existing Staff
                    </h4>
                    
                    <?php if (count($staffList) > 0) : ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staffList as $staff) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($staff['Staff_ID']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($staff['Username']); ?>
                                                <?php if ($staff['Staff_ID'] == $_SESSION['staff_id']) : ?>
                                                    <span class="badge bg-info">You</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $badgeClass = 'bg-secondary';
                                                if ($staff['Role'] == 'Admin') $badgeClass = 'bg-danger';
                                                elseif ($staff['Role'] == 'Manager') $badgeClass = 'bg-primary';
                                                elseif ($staff['Role'] == 'Officer') $badgeClass = 'bg-success';
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>">
                                                    <?php echo htmlspecialchars($staff['Role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($staff['Staff_ID'] == $_SESSION['staff_id']) : ?>
                                                    <button class="btn btn-sm btn-secondary" disabled>
                                                        <i class="bi bi-lock-fill"></i> Cannot Delete Self
                                                    </button>
                                                <?php else : ?>
                                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this staff member?');">
                                                        <input type="hidden" name="staff_id" value="<?php echo htmlspecialchars($staff['Staff_ID']); ?>">
                                                        <button type="submit" name="delete_staff" class="btn btn-sm btn-danger">
                                                            <i class="bi bi-trash-fill"></i> Delete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-info">
                            No staff members found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
