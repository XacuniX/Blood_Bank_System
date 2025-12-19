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

// Handle Reject Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_request'])) {
    $request_id = $_POST['request_id'] ?? '';
    
    if (!empty($request_id)) {
        $reject_sql = "UPDATE request SET Status = 'Rejected' WHERE Request_ID = ?";
        $stmt = $conn->prepare($reject_sql);
        $stmt->bind_param("i", $request_id);
        
        if ($stmt->execute()) {
            $successMessage = "Request #$request_id has been rejected.";
        } else {
            $errorMessage = "Error rejecting request: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Approve Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_request'])) {
    $request_id = $_POST['request_id'] ?? '';
    $required_blood_group = $_POST['required_blood_group'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    
    if (!empty($request_id) && !empty($required_blood_group) && $quantity > 0) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check available stock for this blood group
            $check_stock_sql = "SELECT COUNT(*) as available 
                               FROM blood_unit 
                               WHERE Blood_Group = ? AND Status = 'Available'";
            $stmt = $conn->prepare($check_stock_sql);
            $stmt->bind_param("s", $required_blood_group);
            $stmt->execute();
            $result = $stmt->get_result();
            $stock = $result->fetch_assoc();
            $available_units = $stock['available'];
            $stmt->close();
            
            if ($available_units >= $quantity) {
                // Enough stock - proceed with approval
                
                // Update request status to 'Approved'
                $approve_sql = "UPDATE request SET Status = 'Approved' WHERE Request_ID = ?";
                $stmt = $conn->prepare($approve_sql);
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
                $stmt->close();
                
                // Mark the oldest units as 'Used' (FIFO method)
                $update_units_sql = "UPDATE blood_unit 
                                    SET Status = 'Used' 
                                    WHERE Blood_Group = ? AND Status = 'Available' 
                                    ORDER BY Collection_Date ASC, Unit_ID ASC 
                                    LIMIT ?";
                $stmt = $conn->prepare($update_units_sql);
                if ($stmt) {
                    $stmt->bind_param("si", $required_blood_group, $quantity);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    throw new Exception("Error preparing update query: " . $conn->error);
                }
                
                // Commit transaction
                $conn->commit();
                $successMessage = "Request #$request_id approved successfully. $quantity unit(s) of $required_blood_group allocated.";
            } else {
                // Not enough stock - rollback
                $conn->rollback();
                $errorMessage = "Not enough stock. Only $available_units unit(s) of $required_blood_group available, but $quantity required.";
            }
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $errorMessage = "Error processing approval: " . $e->getMessage();
        }
    } else {
        $errorMessage = "Invalid request data.";
    }
}

// Get all pending requests with hospital names
$pending_requests_sql = "SELECT r.Request_ID, r.Hospital_ID, h.Hospital_Name, r.Required_Blood_Group, r.Quantity, r.Request_Date 
                        FROM request r 
                        JOIN hospital h ON r.Hospital_ID = h.Hospital_ID 
                        WHERE r.Status = 'Pending' 
                        ORDER BY r.Request_Date ASC";
$pending_requests_result = $conn->query($pending_requests_sql);

// Store results in array to avoid cursor issues
$pending_requests = [];
if ($pending_requests_result && $pending_requests_result->num_rows > 0) {
    while ($row = $pending_requests_result->fetch_assoc()) {
        $pending_requests[] = $row;
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="text-danger"><i class="bi bi-clipboard-check me-2"></i>Blood Requests Management</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="staff_officer_dashboard.php" class="text-danger">Dashboard</a></li>
                    <li class="breadcrumb-item active">Requests</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div class="row justify-content-center">
        <div class="col-lg-10 col-md-11">
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

    <!-- Summary Cards -->
    <div class="row justify-content-center mb-4">
        <div class="col-lg-10 col-md-11">
            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-clock-history text-warning" style="font-size: 2.5rem;"></i>
                            <h3 class="mt-2 text-warning">
                                <?php 
                                $total_pending = $conn->query("SELECT COUNT(*) as count FROM request WHERE Status = 'Pending'")->fetch_assoc()['count'];
                                echo $total_pending; 
                                ?>
                            </h3>
                            <p class="text-muted mb-0">Pending Requests</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-check-circle text-success" style="font-size: 2.5rem;"></i>
                            <h3 class="mt-2 text-success">
                                <?php 
                                $total_approved = $conn->query("SELECT COUNT(*) as count FROM request WHERE Status = 'Approved'")->fetch_assoc()['count'];
                                echo $total_approved; 
                                ?>
                            </h3>
                            <p class="text-muted mb-0">Approved Requests</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-x-circle text-danger" style="font-size: 2.5rem;"></i>
                            <h3 class="mt-2 text-danger">
                                <?php 
                                $total_rejected = $conn->query("SELECT COUNT(*) as count FROM request WHERE Status = 'Rejected'")->fetch_assoc()['count'];
                                echo $total_rejected; 
                                ?>
                            </h3>
                            <p class="text-muted mb-0">Rejected Requests</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Requests Section -->
    <div class="row justify-content-center">
        <div class="col-lg-10 col-md-11">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Pending Blood Requests</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($pending_requests)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Hospital Name</th>
                                        <th>Blood Group</th>
                                        <th>Quantity</th>
                                        <th>Request Date</th>
                                        <th>Available Stock</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_requests as $row): ?>
                                        <?php
                                        // Check available stock for this blood group
                                        $bg = $row['Required_Blood_Group'];
                                        $stock_check = $conn->query("SELECT COUNT(*) as available FROM blood_unit WHERE Blood_Group = '$bg' AND Status = 'Available'");
                                        $available = $stock_check->fetch_assoc()['available'];
                                        $has_stock = $available >= $row['Quantity'];
                                        ?>
                                        <tr>
                                            <td><strong>#<?php echo htmlspecialchars($row['Request_ID']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['Hospital_Name']); ?></td>
                                            <td>
                                                <span class="badge bg-danger fs-6">
                                                    <?php echo htmlspecialchars($row['Required_Blood_Group']); ?>
                                                </span>
                                            </td>
                                            <td><strong><?php echo htmlspecialchars($row['Quantity']); ?></strong> unit(s)</td>
                                            <td><?php echo htmlspecialchars($row['Request_Date']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $has_stock ? 'bg-success' : 'bg-warning'; ?>">
                                                    <?php echo $available; ?> available
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <!-- Approve Button -->
                                                    <form method="POST" action="" style="display: inline;" 
                                                          onsubmit="return confirm('Are you sure you want to approve this request?');">
                                                        <input type="hidden" name="request_id" value="<?php echo $row['Request_ID']; ?>">
                                                        <input type="hidden" name="required_blood_group" value="<?php echo $row['Required_Blood_Group']; ?>">
                                                        <input type="hidden" name="quantity" value="<?php echo $row['Quantity']; ?>">
                                                        <button type="submit" name="approve_request" 
                                                                class="btn btn-sm btn-success <?php echo !$has_stock ? 'disabled' : ''; ?>"
                                                                <?php echo !$has_stock ? 'disabled' : ''; ?>>
                                                            <i class="bi bi-check-circle me-1"></i>Approve
                                                        </button>
                                                    </form>
                                                    
                                                    <!-- Reject Button -->
                                                    <form method="POST" action="" style="display: inline;" 
                                                          onsubmit="return confirm('Are you sure you want to reject this request?');">
                                                        <input type="hidden" name="request_id" value="<?php echo $row['Request_ID']; ?>">
                                                        <button type="submit" name="reject_request" class="btn btn-sm btn-danger">
                                                            <i class="bi bi-x-circle me-1"></i>Reject
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check-all text-success" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">No pending requests at the moment.</p>
                            <p class="text-muted">All requests have been processed!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Approved Requests Section -->
    <div class="row justify-content-center mt-4">
        <div class="col-lg-10 col-md-11">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Approved Blood Requests</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $approved_sql = "SELECT r.Request_ID, h.Hospital_Name, r.Required_Blood_Group, r.Quantity, r.Request_Date 
                                    FROM request r 
                                    JOIN hospital h ON r.Hospital_ID = h.Hospital_ID 
                                    WHERE r.Status = 'Approved' 
                                    ORDER BY r.Request_Date DESC";
                    $approved_result = $conn->query($approved_sql);
                    $approved_requests = [];
                    if ($approved_result && $approved_result->num_rows > 0) {
                        while ($row = $approved_result->fetch_assoc()) {
                            $approved_requests[] = $row;
                        }
                    }
                    ?>
                    <?php if (!empty($approved_requests)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Hospital Name</th>
                                        <th>Blood Group</th>
                                        <th>Quantity</th>
                                        <th>Request Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approved_requests as $row): ?>
                                        <tr>
                                            <td><strong>#<?php echo htmlspecialchars($row['Request_ID']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['Hospital_Name']); ?></td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <?php echo htmlspecialchars($row['Required_Blood_Group']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['Quantity']); ?> unit(s)</td>
                                            <td><?php echo htmlspecialchars($row['Request_Date']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">No approved requests yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejected Requests Section -->
    <div class="row justify-content-center mt-4">
        <div class="col-lg-10 col-md-11">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-x-circle me-2"></i>Rejected Blood Requests</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $rejected_sql = "SELECT r.Request_ID, h.Hospital_Name, r.Required_Blood_Group, r.Quantity, r.Request_Date 
                                    FROM request r 
                                    JOIN hospital h ON r.Hospital_ID = h.Hospital_ID 
                                    WHERE r.Status = 'Rejected' 
                                    ORDER BY r.Request_Date DESC";
                    $rejected_result = $conn->query($rejected_sql);
                    $rejected_requests = [];
                    if ($rejected_result && $rejected_result->num_rows > 0) {
                        while ($row = $rejected_result->fetch_assoc()) {
                            $rejected_requests[] = $row;
                        }
                    }
                    ?>
                    <?php if (!empty($rejected_requests)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Hospital Name</th>
                                        <th>Blood Group</th>
                                        <th>Quantity</th>
                                        <th>Request Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rejected_requests as $row): ?>
                                        <tr>
                                            <td><strong>#<?php echo htmlspecialchars($row['Request_ID']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['Hospital_Name']); ?></td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <?php echo htmlspecialchars($row['Required_Blood_Group']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['Quantity']); ?> unit(s)</td>
                                            <td><?php echo htmlspecialchars($row['Request_Date']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">No rejected requests yet.</p>
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
