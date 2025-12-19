<?php
include 'staff_session_check.php'; // 1. Run the security check first!

// Suppress debug output from db_connect.php
ob_start();
include 'db_connect.php';
ob_end_clean();

include 'includes/header.php';

// Get staff ID and info from session
$staff_id = $_SESSION['staff_id'];
$username = $_SESSION['username'] ?? 'Staff';
$role = $_SESSION['role'] ?? 'Staff';

// Fetch staff complete information
$sql = "SELECT * FROM Staff WHERE Staff_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$staff_info = $result->fetch_assoc();
$stmt->close();

// Get statistics for dashboard
// Total blood units
$total_units_sql = "SELECT COUNT(*) as total FROM blood_unit";
$total_units = $conn->query($total_units_sql)->fetch_assoc()['total'];

// Available blood units
$available_units_sql = "SELECT COUNT(*) as available FROM blood_unit WHERE Status = 'Available'";
$available_units = $conn->query($available_units_sql)->fetch_assoc()['available'];

// Pending requests
$pending_requests_sql = "SELECT COUNT(*) as pending FROM request WHERE Status = 'Pending'";
$pending_requests = $conn->query($pending_requests_sql)->fetch_assoc()['pending'];

// Total donors
$total_donors_sql = "SELECT COUNT(*) as total FROM donor";
$total_donors = $conn->query($total_donors_sql)->fetch_assoc()['total'];

// Get blood inventory by group
$inventory_sql = "SELECT Blood_Group, COUNT(*) as count FROM blood_unit WHERE Status = 'Available' GROUP BY Blood_Group ORDER BY Blood_Group";
$inventory_result = $conn->query($inventory_sql);
$inventory = [];
while ($row = $inventory_result->fetch_assoc()) {
    $inventory[$row['Blood_Group']] = $row['count'];
}

// Ensure all blood groups are represented
$all_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
foreach ($all_groups as $group) {
    if (!isset($inventory[$group])) {
        $inventory[$group] = 0;
    }
}

// Get recent requests
$recent_requests_sql = "SELECT r.*, h.Hospital_Name FROM request r 
                        LEFT JOIN hospital h ON r.Hospital_ID = h.Hospital_ID 
                        ORDER BY r.Request_Date DESC LIMIT 10";
$recent_requests = $conn->query($recent_requests_sql);

?>

<div class="container mt-5">
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['success']);
            unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['error']);
            unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Welcome Header with Logout -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
            <p class="text-muted mb-0">Role: <?php echo htmlspecialchars($role); ?></p>
        </div>
        <a href="staff_logout.php" class="btn btn-outline-danger">Logout</a>
    </div>

    <!-- Statistics Cards Row -->
    <div class="row mb-4">
        <!-- Total Blood Units Card -->
        <div class="col-md-3 mb-3">
            <div class="card h-100 border-primary">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-droplet-fill"></i> Total Blood Units
                </div>
                <div class="card-body text-center">
                    <h2 class="display-4"><?php echo $total_units; ?></h2>
                </div>
            </div>
        </div>

        <!-- Available Units Card -->
        <div class="col-md-3 mb-3">
            <div class="card h-100 border-success">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-check-circle"></i> Available Units
                </div>
                <div class="card-body text-center">
                    <h2 class="display-4"><?php echo $available_units; ?></h2>
                </div>
            </div>
        </div>

        <!-- Pending Requests Card -->
        <div class="col-md-3 mb-3">
            <div class="card h-100 border-warning">
                <div class="card-header bg-warning text-dark">
                    <i class="bi bi-clock-history"></i> Pending Requests
                </div>
                <div class="card-body text-center">
                    <h2 class="display-4"><?php echo $pending_requests; ?></h2>
                </div>
            </div>
        </div>

        <!-- Total Donors Card -->
        <div class="col-md-3 mb-3">
            <div class="card h-100 border-info">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-people-fill"></i> Total Donors
                </div>
                <div class="card-body text-center">
                    <h2 class="display-4"><?php echo $total_donors; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Blood Inventory Section -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-archive"></i> Blood Inventory (Available Units by Group)</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($all_groups as $group): ?>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div
                            class="card text-center <?php echo $inventory[$group] > 0 ? 'border-success' : 'border-secondary'; ?>">
                            <div class="card-body">
                                <h3><?php echo $group; ?></h3>
                                <p class="display-6 mb-0"><?php echo $inventory[$group]; ?> units</p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-lightning-fill"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="inventory.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-box-seam"></i> View Inventory
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="donor_search.php" class="btn btn-outline-info w-100">
                                <i class="bi bi-search"></i> Search Donors
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="hospital_search.php" class="btn btn-outline-success w-100">
                                <i class="bi bi-hospital"></i> Hospital Search
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="index.php" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-house"></i> Home Page
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Requests Section -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Blood Requests</h5>
        </div>
        <div class="card-body">
            <?php if ($recent_requests->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Request ID</th>
                                <th>Hospital</th>
                                <th>Blood Group</th>
                                <th>Quantity</th>
                                <th>Urgency</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($request = $recent_requests->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['Request_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($request['Hospital_Name'] ?? 'Unknown'); ?></td>
                                    <td><span
                                            class="badge bg-danger"><?php echo htmlspecialchars($request['Required_Blood_Group']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['Quantity']); ?> units</td>
                                    <td>
                                        <?php
                                        $urgency_class = [
                                            'Critical' => 'danger',
                                            'Urgent' => 'warning',
                                            'Normal' => 'info'
                                        ];
                                        $class = $urgency_class[$request['Urgency_Level']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $class; ?>">
                                            <?php echo htmlspecialchars($request['Urgency_Level']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'Fulfilled' => 'success',
                                            'Approved' => 'primary',
                                            'Pending' => 'warning'
                                        ];
                                        $class = $status_class[$request['Status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $class; ?>">
                                            <?php echo htmlspecialchars($request['Status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($request['Request_Date'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center mb-0">No blood requests found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>