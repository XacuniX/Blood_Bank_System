<?php
include 'hospital_session_check.php'; // 1. Run the security check first!

// Suppress debug output from db_connect.php
ob_start();
include 'db_connect.php';
ob_end_clean();

include 'includes/header.php';

// Get blood group from URL parameter
$bloodGroup = $_GET['blood_group'] ?? '';
$stockCount = 0;
$errorMessage = '';

if (!empty($bloodGroup)) {
    // Validate blood group
    $validBloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    if (!in_array($bloodGroup, $validBloodGroups)) {
        $errorMessage = 'Invalid blood group selected.';
    } else {
        // Query to COUNT available units of this blood group
        $stmt = $conn->prepare("SELECT COUNT(*) as stock_count FROM Blood_Unit WHERE Blood_Group = ? AND Status = 'Available'");
        if ($stmt) {
            $stmt->bind_param("s", $bloodGroup);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stockCount = (int)($row['stock_count'] ?? 0);
            $stmt->close();
        } else {
            $errorMessage = 'Database query error: ' . $conn->error;
        }
    }
} else {
    $errorMessage = 'Please select a blood group to search.';
}

?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Search Results</h2>
        <a href="hospital_dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if (!empty($errorMessage)) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($errorMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($bloodGroup) && empty($errorMessage)) : ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <h3 class="mb-4">Blood Group: <span class="badge bg-danger fs-4"><?php echo htmlspecialchars($bloodGroup); ?></span></h3>
                
                <?php if ($stockCount > 0) : ?>
                    <!-- Scenario A: Stock Available -->
                    <div class="mb-4">
                        <h2 class="text-success mb-3">
                            <i class="bi bi-check-circle-fill"></i> <?php echo $stockCount; ?> Unit<?php echo $stockCount != 1 ? 's' : ''; ?> Available
                        </h2>
                        <p class="text-muted">Blood units are in stock and ready for request.</p>
                    </div>
                    <div>
                        <a href="hospital_request_form.php?group=<?php echo urlencode($bloodGroup); ?>&type=stock" 
                           class="btn btn-success btn-lg px-5">
                            <i class="bi bi-cart-plus"></i> Request This Stock
                        </a>
                    </div>
                <?php else : ?>
                    <!-- Scenario B: Out of Stock -->
                    <div class="mb-4">
                        <h2 class="text-danger mb-3">
                            <i class="bi bi-x-circle-fill"></i> Out of Stock
                        </h2>
                        <p class="text-muted">No available units found for this blood group.</p>
                    </div>
                    <div>
                        <a href="hospital_request_form.php?group=<?php echo urlencode($bloodGroup); ?>&type=emergency" 
                           class="btn btn-danger btn-lg px-5">
                            <i class="bi bi-exclamation-triangle-fill"></i> Create Emergency Request
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif (empty($bloodGroup)) : ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle"></i> Please select a blood group from the dashboard to search.
                </div>
                <a href="hospital_dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>

