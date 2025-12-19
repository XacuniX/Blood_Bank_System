<?php
include 'hospital_session_check.php'; // 1. Run the security check first!

// Suppress debug output from db_connect.php
ob_start();
include 'db_connect.php';
ob_end_clean();

include 'includes/header.php';

// Get hospital ID from session
$hospital_id = $_SESSION['hospital_id'];
$hospital_name = $_SESSION['hospital_name'] ?? 'Hospital';

// Fetch past requests for this hospital
$requests = [];
$stmt = $conn->prepare("SELECT * FROM Request WHERE Hospital_ID = ? ORDER BY Request_Date DESC");
if ($stmt) {
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $requests = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

?>

<div class="container mt-5">
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Welcome Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Welcome, <?php echo htmlspecialchars($hospital_name); ?>!</h2>
        <a href="hospital_logout.php" class="btn btn-outline-danger">Logout</a>
    </div>

    <!-- Visual 1: Hero Search Section -->
    <div class="card shadow-sm mb-5">
        <div class="card-body text-center py-5">
            <h1 class="display-4 mb-4">Find Blood</h1>
            <p class="lead text-muted mb-4">Search for available blood units by blood group</p>
            <form method="get" action="hospital_search.php" class="row g-3 justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <label for="blood_group" class="form-label visually-hidden">Blood Group</label>
                    <select class="form-select form-select-lg" id="blood_group" name="blood_group" required>
                        <option value="" selected disabled>Select blood group</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-danger btn-lg px-5">Search</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Visual 2: Past Requests Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> My Past Requests</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($requests)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Blood Group</th>
                                <th>Quantity</th>
                                <th>Urgency</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><strong>#<?php echo htmlspecialchars($request['Request_ID'] ?? 'N/A'); ?></strong></td>
                                    <td>
                                        <span class="badge bg-danger">
                                            <?php echo htmlspecialchars($request['Required_Blood_Group'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['Quantity'] ?? 'N/A'); ?> units</td>
                                    <td>
                                        <?php
                                        $urgency = strtolower($request['Urgency'] ?? '');
                                        $urgencyBadge = ($urgency === 'critical') ? 'bg-danger' : 'bg-info';
                                        ?>
                                        <span class="badge <?php echo $urgencyBadge; ?>">
                                            <?php echo htmlspecialchars($request['Urgency'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $status = strtolower($request['Status'] ?? '');
                                        $statusBadge = match($status) {
                                            'pending' => 'bg-warning',
                                            'approved' => 'bg-success',
                                            'rejected' => 'bg-danger',
                                            'completed' => 'bg-primary',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $statusBadge; ?>">
                                            <?php echo htmlspecialchars($request['Status'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($request['Request_Date'])) {
                                            echo date('M d, Y', strtotime($request['Request_Date']));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle"></i> No past requests found. Start by searching for blood units above.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>

