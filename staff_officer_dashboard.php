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

include 'includes/header.php';

// Get staff info
$staff_id = $_SESSION['staff_id'];
$username = $_SESSION['username'] ?? 'Officer';

// Get statistics for dashboard

// 1. Total Blood Units
$total_units_sql = "SELECT COUNT(*) as total FROM blood_unit";
$total_units = $conn->query($total_units_sql)->fetch_assoc()['total'];

// 2. Pending Requests
$pending_requests_sql = "SELECT COUNT(*) as pending FROM request WHERE Status = 'Pending'";
$pending_requests = $conn->query($pending_requests_sql)->fetch_assoc()['pending'];

// 3. Low Stock Alerts (blood groups with less than 5 units)
$low_stock_sql = "SELECT COUNT(DISTINCT Blood_Group) as low_stock 
                  FROM blood_unit 
                  WHERE Status = 'Available' 
                  GROUP BY Blood_Group 
                  HAVING COUNT(*) < 5";
$low_stock_result = $conn->query($low_stock_sql);
$low_stock_count = $low_stock_result->num_rows;

// Get recent activity (last 5 blood units added)
$recent_activity_sql = "SELECT Unit_ID, Blood_Group, Donation_Date, Expiry_Date, Status 
                        FROM blood_unit 
                        ORDER BY Donation_Date DESC 
                        LIMIT 5";
$recent_activity = $conn->query($recent_activity_sql);

?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="text-danger">Officer Dashboard</h2>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($username); ?>!</p>
        </div>
        <div class="col-auto">
            <a href="staff_logout.php" class="btn btn-outline-danger">Logout</a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-5">
        <!-- Total Blood Units Card -->
        <div class="col-md-4">
            <div class="card border-danger shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-droplet-fill text-danger" style="font-size: 3rem;"></i>
                    <h3 class="card-title mt-3 text-danger"><?php echo $total_units; ?></h3>
                    <p class="card-text text-muted">Total Blood Units</p>
                </div>
            </div>
        </div>

        <!-- Pending Requests Card -->
        <div class="col-md-4">
            <div class="card border-warning shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-clock-history text-warning" style="font-size: 3rem;"></i>
                    <h3 class="card-title mt-3 text-warning"><?php echo $pending_requests; ?></h3>
                    <p class="card-text text-muted">Pending Requests</p>
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts Card -->
        <div class="col-md-4">
            <div class="card border-danger shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 3rem;"></i>
                    <h3 class="card-title mt-3 text-danger"><?php echo $low_stock_count; ?></h3>
                    <p class="card-text text-muted">Low Stock Alerts</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-activity me-2"></i>Recent Activity</h5>
                </div>
                <div class="card-body">
                    <?php if ($recent_activity && $recent_activity->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Unit ID</th>
                                        <th>Blood Group</th>
                                        <th>Donation Date</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $recent_activity->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['Unit_ID']); ?></td>
                                            <td><span class="badge bg-danger"><?php echo htmlspecialchars($row['Blood_Group']); ?></span></td>
                                            <td><?php echo htmlspecialchars($row['Donation_Date']); ?></td>
                                            <td><?php echo htmlspecialchars($row['Expiry_Date']); ?></td>
                                            <td>
                                                <?php 
                                                $status = $row['Status'];
                                                $badge_class = 'bg-secondary';
                                                if ($status === 'Available') {
                                                    $badge_class = 'bg-success';
                                                } elseif ($status === 'Used') {
                                                    $badge_class = 'bg-info';
                                                } elseif ($status === 'Expired') {
                                                    $badge_class = 'bg-danger';
                                                }
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo htmlspecialchars($status); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">No recent activity to display.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="row mt-4 mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 text-danger"><i class="bi bi-list-check me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="staff_officer_inventory.php" class="btn btn-outline-danger w-100">
                                <i class="bi bi-box-seam me-2"></i>Manage Inventory
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="staff_officer_requests.php" class="btn btn-outline-danger w-100">
                                <i class="bi bi-clipboard-check me-2"></i>Manage Requests
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="donor_search.php" class="btn btn-outline-danger w-100">
                                <i class="bi bi-search me-2"></i>Search Donors
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>
