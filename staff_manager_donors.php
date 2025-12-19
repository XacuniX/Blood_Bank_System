<?php
include 'staff_session_check.php';

// Check if user is a Manager
if ($_SESSION['role'] !== 'Manager') {
    header("Location: staff_login.php");
    exit();
}

include 'db_connect.php';

$successMessage = '';
$errorMessage = '';

// Handle Edit Donor Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_donor'])) {
    $donor_id = $_POST['donor_id'] ?? '';
    $name = trim($_POST['edit_name'] ?? '');
    $age = intval($_POST['edit_age'] ?? 0);
    $gender = trim($_POST['edit_gender'] ?? '');
    $phone = trim($_POST['edit_phone'] ?? '');
    $password = trim($_POST['edit_password'] ?? '');
    
    if (!empty($donor_id) && !empty($name) && $age > 0 && !empty($gender) && !empty($phone)) {
        // Update donor - only update password if provided
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE donor SET Name = ?, Age = ?, Gender = ?, Phone_Number = ?, Password = ? WHERE Donor_ID = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sisssi", $name, $age, $gender, $phone, $hashed_password, $donor_id);
        } else {
            $update_sql = "UPDATE donor SET Name = ?, Age = ?, Gender = ?, Phone_Number = ? WHERE Donor_ID = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sissi", $name, $age, $gender, $phone, $donor_id);
        }
        
        if ($update_stmt->execute()) {
            $successMessage = "Donor updated successfully!";
        } else {
            $errorMessage = "Error updating donor: " . $update_stmt->error;
        }
        $update_stmt->close();
    } else {
        $errorMessage = "Please fill in all required fields.";
    }
}

// Handle Delete Donor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_donor'])) {
    $donor_id = $_POST['donor_id'] ?? '';
    
    if (!empty($donor_id)) {
        // Check if donor has related blood units
        $check_units_sql = "SELECT COUNT(*) as count FROM blood_unit WHERE Donor_ID = ?";
        $stmt = $conn->prepare($check_units_sql);
        $stmt->bind_param("i", $donor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt->close();
        
        if ($count > 0) {
            $errorMessage = "Cannot delete donor. This donor has $count blood unit(s) in the system. Please remove or reassign those units first.";
        } else {
            // Safe to delete
            $delete_sql = "DELETE FROM donor WHERE Donor_ID = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $donor_id);
            
            if ($delete_stmt->execute()) {
                $successMessage = "Donor deleted successfully.";
            } else {
                $errorMessage = "Error deleting donor: " . $delete_stmt->error;
            }
            $delete_stmt->close();
        }
    }
}

// Handle Search
$search_name = $_GET['search_name'] ?? '';
$search_blood_group = $_GET['search_blood_group'] ?? '';

// Build query with search filters
$donors_sql = "SELECT Donor_ID, Name, Age, Gender, Blood_Group, Phone_Number FROM donor WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_name)) {
    $donors_sql .= " AND Name LIKE ?";
    $params[] = "%$search_name%";
    $types .= "s";
}

if (!empty($search_blood_group)) {
    $donors_sql .= " AND Blood_Group = ?";
    $params[] = $search_blood_group;
    $types .= "s";
}

$donors_sql .= " ORDER BY Name ASC";

// Execute query
if (!empty($params)) {
    $stmt = $conn->prepare($donors_sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $donors = $stmt->get_result();
    } else {
        // Prepare failed, show error for debugging
        die("SQL Error: " . $conn->error . "<br>Query: " . htmlspecialchars($donors_sql));
    }
} else {
    $donors = $conn->query($donors_sql);
}

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="text-danger"><i class="bi bi-people-fill me-2"></i>Donor Management</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="staff_manager_dashboard.php" class="text-danger">Dashboard</a></li>
                    <li class="breadcrumb-item active">Donors</li>
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

    <!-- Search Bar -->
    <div class="row mb-4">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-search me-2"></i>Search Donors</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="search_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="search_name" name="search_name" 
                                       value="<?php echo htmlspecialchars($search_name); ?>" 
                                       placeholder="Enter donor name">
                            </div>
                            <div class="col-md-6">
                                <label for="search_blood_group" class="form-label">Blood Group</label>
                                <select class="form-select" id="search_blood_group" name="search_blood_group">
                                    <option value="">All Blood Groups</option>
                                    <option value="A+" <?php echo $search_blood_group === 'A+' ? 'selected' : ''; ?>>A+</option>
                                    <option value="A-" <?php echo $search_blood_group === 'A-' ? 'selected' : ''; ?>>A-</option>
                                    <option value="B+" <?php echo $search_blood_group === 'B+' ? 'selected' : ''; ?>>B+</option>
                                    <option value="B-" <?php echo $search_blood_group === 'B-' ? 'selected' : ''; ?>>B-</option>
                                    <option value="AB+" <?php echo $search_blood_group === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                    <option value="AB-" <?php echo $search_blood_group === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                    <option value="O+" <?php echo $search_blood_group === 'O+' ? 'selected' : ''; ?>>O+</option>
                                    <option value="O-" <?php echo $search_blood_group === 'O-' ? 'selected' : ''; ?>>O-</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-danger">
                                    <i class="bi bi-search me-2"></i>Search
                                </button>
                                <a href="staff_manager_donors.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-2"></i>Clear Filters
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Donors Table -->
    <div class="row mb-4">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 text-danger"><i class="bi bi-table me-2"></i>Registered Donors</h5>
                </div>
                <div class="card-body">
                    <?php if ($donors && $donors->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                        <th>Blood Group</th>
                                        <th>Phone</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $donors->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($row['Donor_ID']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['Name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['Age']); ?></td>
                                            <td><?php echo htmlspecialchars($row['Gender']); ?></td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <?php echo htmlspecialchars($row['Blood_Group']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['Phone_Number']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editModal<?php echo $row['Donor_ID']; ?>">
                                                    <i class="bi bi-pencil me-1"></i>Edit
                                                </button>
                                                <form method="POST" action="" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this donor? This action cannot be undone.');">
                                                    <input type="hidden" name="donor_id" value="<?php echo $row['Donor_ID']; ?>">
                                                    <button type="submit" name="delete_donor" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash me-1"></i>Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?php echo $row['Donor_ID']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">Edit Donor</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" action="">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="donor_id" value="<?php echo $row['Donor_ID']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                                                <input type="text" class="form-control" name="edit_name" 
                                                                       value="<?php echo htmlspecialchars($row['Name']); ?>" required>
                                                            </div>
                                                            
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Age <span class="text-danger">*</span></label>
                                                                        <input type="number" class="form-control" name="edit_age" 
                                                                               value="<?php echo htmlspecialchars($row['Age']); ?>" min="18" max="65" required>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                                                                        <select class="form-select" name="edit_gender" required>
                                                                            <option value="Male" <?php echo $row['Gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                                                            <option value="Female" <?php echo $row['Gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Phone <span class="text-danger">*</span></label>
                                                                <input type="tel" class="form-control" name="edit_phone" 
                                                                       value="<?php echo htmlspecialchars($row['Phone_Number']); ?>" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">New Password</label>
                                                                <input type="password" class="form-control" name="edit_password" 
                                                                       placeholder="Leave blank to keep current password">
                                                                <div class="form-text">Only enter a password if you want to change it.</div>
                                                            </div>
                                                            
                                                            <div class="alert alert-info mb-0">
                                                                <small><i class="bi bi-info-circle me-1"></i>Blood Group and Last Donation Date cannot be changed.</small>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="edit_donor" class="btn btn-danger">
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
                            <p class="text-muted mt-2 mb-0">
                                <?php if (!empty($search_name) || !empty($search_blood_group)): ?>
                                    No donors found matching your search criteria.
                                <?php else: ?>
                                    No donors registered yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
if (isset($stmt)) $stmt->close();
$conn->close();
include 'includes/footer.php'; 
?>
