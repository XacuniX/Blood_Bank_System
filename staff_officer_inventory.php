<?php
include 'staff_session_check.php';

// Check if user is an Officer
if ($_SESSION['role'] !== 'Officer') {
    header("Location: staff_login.php");
    exit();
}

// Suppress debug output from db_connect.php
ob_start();
include 'db_connect.php';
ob_end_clean();

$successMessage = '';
$errorMessage = '';

// Handle Add Blood Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_blood'])) {
    $donor_id = trim($_POST['donor_id'] ?? '');
    $expiry_date = trim($_POST['expiry_date'] ?? '');
    
    if (!empty($donor_id) && !empty($expiry_date)) {
        // Check if Donor_ID exists and get blood group
        $check_donor_sql = "SELECT Donor_ID, Blood_Group FROM donor WHERE Donor_ID = ?";
        $stmt = $conn->prepare($check_donor_sql);
        $stmt->bind_param("i", $donor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Donor exists - get blood group
            $donor = $result->fetch_assoc();
            $blood_group = $donor['Blood_Group'];
            
            // Insert new blood unit
            $status = 'Available';
            
            // Notice we use the SQL function NOW() instead of a PHP variable (?)
            $insert_sql = "INSERT INTO blood_unit (Donor_ID, Blood_Group, Expiry_Date, Collection_Date, Status, Staff_ID) 
                          VALUES (?, ?, ?, NOW(), ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            
            if ($insert_stmt) {
                $staff_id = $_SESSION['staff_id'];
                $insert_stmt->bind_param("isssi", $donor_id, $blood_group, $expiry_date, $status, $staff_id);
                
                if ($insert_stmt->execute()) {
                    $successMessage = "Blood unit added successfully! Unit ID: " . $insert_stmt->insert_id;
                } else {
                    $errorMessage = "Error adding blood unit: " . $insert_stmt->error;
                }
                $insert_stmt->close();
            } else {
                $errorMessage = "Database error: " . $conn->error;
            }
        } else {
            $errorMessage = "Donor ID not found. Please verify the Donor ID and try again.";
        }
        $stmt->close();
    } else {
        $errorMessage = "Please fill in all fields.";
    }
}

// Handle Discard/Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['discard_unit'])) {
    $unit_id = $_POST['unit_id'] ?? '';
    
    if (!empty($unit_id)) {
        // Update status to 'Discarded' instead of deleting
        $discard_sql = "UPDATE blood_unit SET Status = 'Discarded' WHERE Unit_ID = ?";
        $stmt = $conn->prepare($discard_sql);
        $stmt->bind_param("i", $unit_id);
        
        if ($stmt->execute()) {
            $successMessage = "Blood unit #$unit_id has been discarded.";
        } else {
            $errorMessage = "Error discarding blood unit: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get all blood units for display
$blood_units_sql = "SELECT Unit_ID, Blood_Group, Donor_ID, Donation_Date, Expiry_Date, Status 
                    FROM blood_unit 
                    ORDER BY Donation_Date DESC";
$blood_units = $conn->query($blood_units_sql);

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="text-danger"><i class="bi bi-droplet-fill me-2"></i>Blood Inventory Management</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="staff_officer_dashboard.php" class="text-danger">Dashboard</a></li>
                    <li class="breadcrumb-item active">Inventory</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div class="row mb-3">
        <div class="col-lg-6 mx-auto">
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

    <!-- Add Blood Form Section -->
    <div class="row mb-4">
        <div class="col-lg-6 mx-auto">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Add New Blood Unit</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="donor_id" class="form-label">Donor ID <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="donor_id" name="donor_id" 
                                   placeholder="Enter Donor ID" required>
                            <div class="form-text">Enter the ID of the donor who donated this blood.</div>
                        </div>

                        <div class="mb-3">
                            <label for="expiry_date" class="form-label">Expiry Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="expiry_date" name="expiry_date" 
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                            <div class="form-text">Blood units typically expire 35-42 days after donation.</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="add_blood" class="btn btn-danger">
                                <i class="bi bi-plus-lg me-2"></i>Add Blood Unit
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tips Card -->
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 text-danger"><i class="bi bi-lightbulb me-2"></i>Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="mb-2">Verify donor ID before adding blood units</li>
                        <li>Blood typically expires 35-42 days after donation</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>
