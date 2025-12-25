<?php
require 'audit_logger.php';
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
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if (!empty($donor_id) && !empty($expiry_date) && $quantity > 0 && $quantity <= 10) {
        // Check if Donor_ID exists and get blood group and last donation date
        $check_donor_sql = "SELECT Donor_ID, Blood_Group, Last_Donation_Date FROM donor WHERE Donor_ID = ?";
        $stmt = $conn->prepare($check_donor_sql);
        $stmt->bind_param("i", $donor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Donor exists - get blood group and check eligibility
            $donor = $result->fetch_assoc();
            $blood_group = $donor['Blood_Group'];
            $last_donation = $donor['Last_Donation_Date'];
            
            // Check if donor is eligible (56 days between donations)
            $eligible = true;
            $days_remaining = 0;
            
            if (!empty($last_donation)) {
                $last_donation_timestamp = strtotime($last_donation);
                $today_timestamp = time();
                $days_since_last_donation = floor(($today_timestamp - $last_donation_timestamp) / (60 * 60 * 24));
                
                if ($days_since_last_donation < 56) {
                    $eligible = false;
                    $days_remaining = 56 - $days_since_last_donation;
                }
            }
            
            if (!$eligible) {
                $next_eligible_date = date('Y-m-d', strtotime($last_donation . ' + 56 days'));
                $errorMessage = "Donor is not eligible to donate yet. Last donation was on " . date('Y-m-d', strtotime($last_donation)) . ". Next eligible date: $next_eligible_date ($days_remaining days remaining).";
            } else {
                // Insert new blood units based on quantity
                $status = 'Available';
                $staff_id = $_SESSION['staff_id'];
                
                // Notice we use the SQL function NOW() instead of a PHP variable (?)
                $insert_sql = "INSERT INTO blood_unit (Donor_ID, Blood_Group, Expiry_Date, Collection_Date, Status, Staff_ID) 
                              VALUES (?, ?, ?, NOW(), ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                
                if ($insert_stmt) {
                    $inserted_units = [];
                    $success_count = 0;
                    
                    // Loop to insert multiple units
                    for ($i = 0; $i < $quantity; $i++) {
                        $insert_stmt->bind_param("isssi", $donor_id, $blood_group, $expiry_date, $status, $staff_id);
                        
                        if ($insert_stmt->execute()) {
                            $inserted_units[] = $insert_stmt->insert_id;
                            $success_count++;
                        } else {
                            $errorMessage = "Error adding blood unit: " . $insert_stmt->error;
                            break;
                        }
                    }
                    
                    if ($success_count > 0) {
                        // Get donor name for audit logging
                        $donor_name = 'Unknown';
                        $name_stmt = $conn->prepare("SELECT Name FROM donor WHERE Donor_ID = ?");
                        if ($name_stmt) {
                            $name_stmt->bind_param("i", $donor_id);
                            $name_stmt->execute();
                            $name_result = $name_stmt->get_result();
                            if ($name_result->num_rows > 0) {
                                $name_data = $name_result->fetch_assoc();
                                $donor_name = $name_data['Name'];
                            }
                            $name_stmt->close();
                        }
                        
                        // Update donor's last donation date
                        $update_donor_sql = "UPDATE donor SET Last_Donation_Date = NOW() WHERE Donor_ID = ?";
                        $update_stmt = $conn->prepare($update_donor_sql);
                        $update_stmt->bind_param("i", $donor_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        // Log donor update activity
                        $donor_update_details = "Donor last donation date updated for {$donor_name} (ID: {$donor_id})";
                        log_activity($conn, $_SESSION['username'], 'Staff', 'UPDATE', 'Donor', $donor_id, $donor_update_details);
                        
                        // Log blood unit addition activity
                        $unit_details = "Added {$success_count} blood unit(s) - Blood Group: {$blood_group}, Donor: {$donor_name} (ID: {$donor_id})";
                        log_activity($conn, $_SESSION['username'], 'Staff', 'INSERT', 'Blood_Unit', $inserted_units[0], $unit_details);
                        
                        if ($success_count == 1) {
                            $successMessage = "Blood unit added successfully! Unit ID: " . $inserted_units[0];
                        } else {
                            $successMessage = "$success_count blood units added successfully! Unit IDs: " . implode(", ", $inserted_units);
                        }
                    }
                    
                    $insert_stmt->close();
                } else {
                    $errorMessage = "Database error: " . $conn->error;
                }
            }
        } else {
            $errorMessage = "Donor ID not found. Please verify the Donor ID and try again.";
        }
        $stmt->close();
    } else {
        $errorMessage = "Please fill in all fields correctly. Quantity must be between 1 and 10.";
    }
}

// Handle Discard/Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['discard_btn'])) {
    $unit_id = $_POST['unit_id'] ?? '';
    
    if (!empty($unit_id)) {
        // Fetch blood unit details for audit logging
        $blood_group = 'Unknown';
        $donor_id_log = 'Unknown';
        $unit_status = 'Unknown';
        $fetch_stmt = $conn->prepare("SELECT Blood_Group, Donor_ID, Status FROM blood_unit WHERE Unit_ID = ?");
        if ($fetch_stmt) {
            $fetch_stmt->bind_param("i", $unit_id);
            $fetch_stmt->execute();
            $fetch_result = $fetch_stmt->get_result();
            if ($fetch_result->num_rows > 0) {
                $unit_data = $fetch_result->fetch_assoc();
                $blood_group = $unit_data['Blood_Group'];
                $donor_id_log = $unit_data['Donor_ID'];
                $unit_status = $unit_data['Status'];
            }
            $fetch_stmt->close();
        }
        
        // Delete the blood unit from database
        $delete_sql = "DELETE FROM blood_unit WHERE Unit_ID = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $unit_id);
        
        if ($stmt->execute()) {
            $successMessage = "Blood unit #$unit_id has been discarded and removed from inventory.";
            
            // Log blood unit discard activity
            $discard_details = "Blood unit discarded - Unit ID: {$unit_id}, Blood Group: {$blood_group}, Donor ID: {$donor_id_log}, Status: {$unit_status}";
            log_activity($conn, $_SESSION['username'], 'Staff', 'DELETE', 'Blood_Unit', $unit_id, $discard_details);
        } else {
            $errorMessage = "Error discarding blood unit: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get all blood units for display
$blood_units_sql = "SELECT Unit_ID, Blood_Group, Donor_ID, Collection_Date, Expiry_Date, Status 
                    FROM blood_unit 
                    ORDER BY Expiry_Date ASC";
$blood_units = $conn->query($blood_units_sql);

// Get inventory statistics using aggregate functions
$stats_sql = "SELECT 
                COUNT(*) as total_units,
                SUM(CASE WHEN Status = 'Available' THEN 1 ELSE 0 END) as available_units,
                MIN(Expiry_Date) as earliest_expiry,
                MAX(Collection_Date) as latest_collection,
                AVG(DATEDIFF(Expiry_Date, Collection_Date)) as avg_shelf_life_days
              FROM blood_unit";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// SINGLE-ROW SUBQUERY: Get average donation count per donor
$avg_donations_sql = "SELECT 
                        (SELECT AVG(donation_count) 
                         FROM (SELECT Donor_ID, COUNT(*) as donation_count 
                               FROM blood_unit 
                               GROUP BY Donor_ID) as donor_donations) as avg_donations_per_donor";
$avg_donations_result = $conn->query($avg_donations_sql);
$avg_donations = $avg_donations_result->fetch_assoc();

// MULTIPLE-ROW SUBQUERY WITH IN: Get donors who have blood units currently available
$active_donors_sql = "SELECT d.Donor_ID, d.Name, d.Blood_Group, COUNT(bu.Unit_ID) as available_units
                      FROM donor d
                      INNER JOIN blood_unit bu ON d.Donor_ID = bu.Donor_ID
                      WHERE d.Donor_ID IN (SELECT Donor_ID FROM blood_unit WHERE Status = 'Available')
                      AND bu.Status = 'Available'
                      GROUP BY d.Donor_ID, d.Name, d.Blood_Group
                      ORDER BY available_units DESC
                      LIMIT 5";
$active_donors_result = $conn->query($active_donors_sql);

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="text-danger mb-3"><i class="bi bi-droplet-fill me-2"></i>Blood Inventory Management</h2>
            <a href="staff_officer_dashboard.php" class="btn btn-outline-danger">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Inventory Statistics (Using SQL Aggregate Functions) -->
    <div class="row mb-4">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow-sm border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Inventory Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="text-muted">Total Units</h6>
                                <h3 class="text-primary mb-0"><?php echo number_format($stats['total_units']); ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="text-muted">Available Units</h6>
                                <h3 class="text-success mb-0"><?php echo number_format($stats['available_units']); ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="text-muted">Earliest Expiry</h6>
                                <h3 class="text-warning mb-0"><?php echo $stats['earliest_expiry'] ? date('M d', strtotime($stats['earliest_expiry'])) : 'N/A'; ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="text-muted">Avg Shelf Life</h6>
                                <h3 class="text-info mb-0"><?php echo $stats['avg_shelf_life_days'] ? round($stats['avg_shelf_life_days']) : 0; ?> days</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Subquery Statistics Section -->
    <div class="row mb-4">
        <div class="col-lg-10 mx-auto">
            <div class="row">
                <!-- Single-Row Subquery Result -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-success h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-calculator me-2"></i>Average Donations</h5>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-center text-center">
                            <p class="text-muted mb-2">Average donations per donor</p>
                            <h2 class="text-success mb-0">
                                <?php echo $avg_donations['avg_donations_per_donor'] ? number_format($avg_donations['avg_donations_per_donor'], 2) : '0.00'; ?>
                            </h2>
                            <small class="text-muted">units per donor</small>
                        </div>
                    </div>
                </div>

                <!-- Multiple-Row Subquery Result -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-primary h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>Top Active Donors</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($active_donors_result && $active_donors_result->num_rows > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php while ($donor = $active_donors_result->fetch_assoc()): ?>
                                        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($donor['Name']); ?></strong>
                                                <span class="badge bg-danger ms-2"><?php echo htmlspecialchars($donor['Blood_Group']); ?></span>
                                                <br>
                                                <small class="text-muted">ID: <?php echo htmlspecialchars($donor['Donor_ID']); ?></small>
                                            </div>
                                            <span class="badge bg-primary rounded-pill"><?php echo $donor['available_units']; ?> units</span>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center mb-0">No active donors found</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
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

                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   min="1" max="10" value="1" required>
                            <div class="form-text">Number of blood units to add (1-10).</div>
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

            <!-- Current Blood Stock Section -->
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Current Blood Stock</h5>
                </div>
                <div class="card-body">
                    <?php if ($blood_units && $blood_units->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Unit ID</th>
                                        <th>Blood Group</th>
                                        <th>Donor ID</th>
                                        <th>Collection Date</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $blood_units->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($row['Unit_ID']); ?></strong></td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <?php echo htmlspecialchars($row['Blood_Group']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['Donor_ID']); ?></td>
                                            <td><?php echo htmlspecialchars($row['Collection_Date'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($row['Expiry_Date']); ?></td>
                                            <td>
                                                <?php 
                                                $status = strtolower($row['Status']);
                                                $badgeClass = match ($status) {
                                                    'available' => 'text-bg-success',
                                                    'used' => 'text-bg-info',
                                                    'expired' => 'text-bg-danger',
                                                    'discarded' => 'text-bg-dark',
                                                    default => 'text-bg-secondary',
                                                };
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>">
                                                    <?php echo htmlspecialchars($row['Status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" action="" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to discard this blood unit? This action cannot be undone.');">
                                                    <input type="hidden" name="unit_id" value="<?php echo $row['Unit_ID']; ?>">
                                                    <button type="submit" name="discard_btn" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash me-1"></i>Discard
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2 mb-0">No blood units in inventory yet.</p>
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
