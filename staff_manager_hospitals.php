<?php
require 'audit_logger.php';
include 'staff_session_check.php';

// Check if user is a Manager
if ($_SESSION['role'] !== 'Manager') {
    header("Location: staff_login.php");
    exit();
}

include 'db_connect.php';

$successMessage = '';
$errorMessage = '';

// Handle Add Hospital Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_hospital'])) {
    $hospital_name = trim($_POST['hospital_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (!empty($hospital_name) && !empty($location) && !empty($contact_email) && !empty($phone) && !empty($password)) {
        // Check for duplicate email
        $check_email_sql = "SELECT Hospital_ID FROM hospital WHERE Contact_Email = ?";
        $stmt = $conn->prepare($check_email_sql);
        $stmt->bind_param("s", $contact_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errorMessage = "A hospital with this email already exists.";
            $stmt->close();
        } else {
            $stmt->close();
            
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new hospital
            $insert_sql = "INSERT INTO hospital (Hospital_Name, Location, Contact_Email, Phone, Password) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssss", $hospital_name, $location, $contact_email, $phone, $hashed_password);
            
            if ($insert_stmt->execute()) {
                $new_hospital_id = $conn->insert_id;
                $successMessage = "Hospital '$hospital_name' registered successfully!";
                
                // Log the hospital registration activity
                $details = "Hospital registered: Name={$hospital_name}, Location={$location}";
                log_activity($conn, $_SESSION['username'], 'Staff', 'INSERT', 'Hospital', $new_hospital_id, $details);
            } else {
                $errorMessage = "Error registering hospital: " . $insert_stmt->error;
            }
            $insert_stmt->close();
        }
    } else {
        $errorMessage = "Please fill in all fields.";
    }
}

// Handle Edit Hospital Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_hospital'])) {
    $hospital_id = $_POST['hospital_id'] ?? '';
    $hospital_name = trim($_POST['edit_hospital_name'] ?? '');
    $location = trim($_POST['edit_location'] ?? '');
    $contact_email = trim($_POST['edit_contact_email'] ?? '');
    $phone = trim($_POST['edit_phone'] ?? '');
    $password = trim($_POST['edit_password'] ?? '');
    
    if (!empty($hospital_id) && !empty($hospital_name) && !empty($location) && !empty($contact_email) && !empty($phone)) {
        // Check for duplicate email (excluding current hospital)
        $check_email_sql = "SELECT Hospital_ID FROM hospital WHERE Contact_Email = ? AND Hospital_ID != ?";
        $stmt = $conn->prepare($check_email_sql);
        $stmt->bind_param("si", $contact_email, $hospital_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errorMessage = "Another hospital with this email already exists.";
            $stmt->close();
        } else {
            $stmt->close();
            
            // Update hospital - only update password if provided
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE hospital SET Hospital_Name = ?, Location = ?, Contact_Email = ?, Phone = ?, Password = ? WHERE Hospital_ID = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sssssi", $hospital_name, $location, $contact_email, $phone, $hashed_password, $hospital_id);
            } else {
                $update_sql = "UPDATE hospital SET Hospital_Name = ?, Location = ?, Contact_Email = ?, Phone = ? WHERE Hospital_ID = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssssi", $hospital_name, $location, $contact_email, $phone, $hospital_id);
            }
            
            if ($update_stmt->execute()) {
                $successMessage = "Hospital updated successfully!";
                
                // Log the hospital update activity
                $details = !empty($password) ? "Hospital updated (including password): Name={$hospital_name}" : "Hospital updated: Name={$hospital_name}";
                log_activity($conn, $_SESSION['username'], 'Staff', 'UPDATE', 'Hospital', $hospital_id, $details);
            } else {
                $errorMessage = "Error updating hospital: " . $update_stmt->error;
            }
            $update_stmt->close();
        }
    } else {
        $errorMessage = "Please fill in all required fields.";
    }
}

// Handle Delete Hospital
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_hospital'])) {
    $hospital_id = $_POST['hospital_id'] ?? '';
    
    if (!empty($hospital_id)) {
        // Fetch hospital details for audit logging
        $hospital_name = 'Unknown';
        $hospital_location = 'Unknown';
        $fetch_stmt = $conn->prepare("SELECT Hospital_Name, Location FROM hospital WHERE Hospital_ID = ?");
        $fetch_stmt->bind_param("i", $hospital_id);
        $fetch_stmt->execute();
        $fetch_result = $fetch_stmt->get_result();
        if ($fetch_result->num_rows > 0) {
            $hospital_data = $fetch_result->fetch_assoc();
            $hospital_name = $hospital_data['Hospital_Name'];
            $hospital_location = $hospital_data['Location'];
        }
        $fetch_stmt->close();
        
        $delete_sql = "DELETE FROM hospital WHERE Hospital_ID = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $hospital_id);
        
        if ($stmt->execute()) {
            $successMessage = "Hospital deleted successfully.";
            
            // Log the hospital deletion activity
            $details = "Hospital deleted: Name={$hospital_name}, Location={$hospital_location}";
            log_activity($conn, $_SESSION['username'], 'Staff', 'DELETE', 'Hospital', $hospital_id, $details);
        } else {
            $errorMessage = "Error deleting hospital: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get all hospitals
$hospitals_sql = "SELECT Hospital_ID, Hospital_Name, Location, Contact_Email, Phone FROM hospital ORDER BY Hospital_Name ASC";
$hospitals = $conn->query($hospitals_sql);

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="text-danger"><i class="bi bi-hospital me-2"></i>Hospital Management</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="staff_manager_dashboard.php" class="text-danger">Dashboard</a></li>
                    <li class="breadcrumb-item active">Hospitals</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div class="row mb-3">
        <div class="col-lg-10 mx-auto">
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($successMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($errorMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Hospital Form -->
    <div class="row mb-4">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Add New Hospital</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="hospital_name" class="form-label">Hospital Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="hospital_name" name="hospital_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="location" name="location" required>
                            </div>
                            <div class="col-md-4">
                                <label for="contact_email" class="form-label">Contact Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email" required>
                            </div>
                            <div class="col-md-4">
                                <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="col-md-4">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="add_hospital" class="btn btn-danger">
                                    <i class="bi bi-plus-lg me-2"></i>Register Hospital
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Hospitals Table -->
    <div class="row mb-4">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 text-danger"><i class="bi bi-table me-2"></i>Registered Hospitals</h5>
                </div>
                <div class="card-body">
                    <?php if ($hospitals && $hospitals->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Hospital Name</th>
                                        <th>Location</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $hospitals->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($row['Hospital_ID']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['Hospital_Name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['Location']); ?></td>
                                            <td><?php echo htmlspecialchars($row['Contact_Email']); ?></td>
                                            <td><?php echo htmlspecialchars($row['Phone']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editModal<?php echo $row['Hospital_ID']; ?>">
                                                    <i class="bi bi-pencil me-1"></i>Edit
                                                </button>
                                                <form method="POST" action="" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this hospital? This action cannot be undone.');">
                                                    <input type="hidden" name="hospital_id" value="<?php echo $row['Hospital_ID']; ?>">
                                                    <button type="submit" name="delete_hospital" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash me-1"></i>Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?php echo $row['Hospital_ID']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">Edit Hospital</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" action="">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="hospital_id" value="<?php echo $row['Hospital_ID']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Hospital Name <span class="text-danger">*</span></label>
                                                                <input type="text" class="form-control" name="edit_hospital_name" 
                                                                       value="<?php echo htmlspecialchars($row['Hospital_Name']); ?>" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Location <span class="text-danger">*</span></label>
                                                                <input type="text" class="form-control" name="edit_location" 
                                                                       value="<?php echo htmlspecialchars($row['Location']); ?>" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact Email <span class="text-danger">*</span></label>
                                                                <input type="email" class="form-control" name="edit_contact_email" 
                                                                       value="<?php echo htmlspecialchars($row['Contact_Email']); ?>" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Phone <span class="text-danger">*</span></label>
                                                                <input type="tel" class="form-control" name="edit_phone" 
                                                                       value="<?php echo htmlspecialchars($row['Phone']); ?>" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">New Password</label>
                                                                <input type="password" class="form-control" name="edit_password" 
                                                                       placeholder="Leave blank to keep current password">
                                                                <div class="form-text">Only enter a password if you want to change it.</div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="edit_hospital" class="btn btn-danger">
                                                                <i class="bi bi-save me-1"></i>Save Changes
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2 mb-0">No hospitals registered yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>
