<?php
include 'hospital_session_check.php'; // 1. Run the security check first!

// Suppress debug output from db_connect.php
ob_start();
include 'db_connect.php';
ob_end_clean();

include 'includes/header.php';

// Get hospital ID from session
$hospital_id = $_SESSION['hospital_id'];

// Get blood group and type from URL parameters
$bloodGroup = $_GET['group'] ?? '';
$requestType = $_GET['type'] ?? '';

$successMessage = '';
$errorMessage = '';
$requestId = null;

// Validate blood group
$validBloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
if (!empty($bloodGroup) && !in_array($bloodGroup, $validBloodGroups)) {
    $errorMessage = 'Invalid blood group.';
    $bloodGroup = '';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bloodGroup = trim($_POST['blood_group'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $urgency = trim($_POST['urgency'] ?? '');
    
    // Validation
    if (empty($bloodGroup) || !in_array($bloodGroup, $validBloodGroups)) {
        $errorMessage = 'Invalid blood group selected.';
    } elseif ($quantity < 1) {
        $errorMessage = 'Quantity must be at least 1.';
    } elseif (!in_array($urgency, ['Normal', 'Critical'])) {
        $errorMessage = 'Please select a valid urgency level.';
    } else {
        // Insert into Request table
        $status = 'Pending';
        $requestDate = date('Y-m-d H:i:s'); // Current timestamp
        
        $stmt = $conn->prepare("INSERT INTO Request (Hospital_ID, Required_Blood_Group, Quantity, Urgency_Level, Status, Request_Date) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isisss", $hospital_id, $bloodGroup, $quantity, $urgency, $status, $requestDate);
            if ($stmt->execute()) {
                $requestId = $conn->insert_id; // Get the auto-generated Request_ID
                $successMessage = "Request Submitted Successfully. ID: #{$requestId}";
            } else {
                $errorMessage = 'Failed to submit request: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $errorMessage = 'Database query error: ' . $conn->error;
        }
    }
}

?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Blood Request Form</h2>
        <a href="hospital_dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if (!empty($successMessage)) : ?>
        <!-- Success Message -->
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center py-5">
                <div class="alert alert-success" role="alert">
                    <h4 class="alert-heading">
                        <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($successMessage); ?>
                    </h4>
                </div>
                <a href="hospital_dashboard.php" class="btn btn-primary btn-lg mt-3">
                    <i class="bi bi-house-door"></i> Go to Dashboard
                </a>
            </div>
        </div>
    <?php else : ?>
        <!-- Request Form -->
        <?php if (!empty($errorMessage)) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($errorMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($bloodGroup)) : ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> No blood group specified. Please select a blood group from the search results.
            </div>
            <a href="hospital_dashboard.php" class="btn btn-primary">Go to Dashboard</a>
        <?php else : ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title mb-4">Request Blood Units</h4>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="blood_group" class="form-label">Blood Group</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="blood_group" 
                                   name="blood_group" 
                                   value="<?php echo htmlspecialchars($bloodGroup); ?>" 
                                   readonly 
                                   style="background-color: #e9ecef;">
                            <small class="form-text text-muted">Blood group is pre-filled from your search.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="quantity" 
                                   name="quantity" 
                                   min="1" 
                                   required 
                                   value="<?php echo isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '1'; ?>">
                            <small class="form-text text-muted">Enter the number of blood units needed (minimum 1).</small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="urgency" class="form-label">Urgency</label>
                            <select class="form-select" id="urgency" name="urgency" required>
                                <option value="" <?php echo (!isset($_POST['urgency']) || empty($_POST['urgency'])) ? 'selected' : ''; ?> disabled>Select urgency level</option>
                                <option value="Normal" <?php echo (isset($_POST['urgency']) && $_POST['urgency'] === 'Normal') ? 'selected' : ''; ?>>Normal</option>
                                <option value="Critical" <?php echo (isset($_POST['urgency']) && $_POST['urgency'] === 'Critical') ? 'selected' : ''; ?>>Critical</option>
                            </select>
                            <small class="form-text text-muted">
                                <strong>Normal:</strong> Standard request timeline. 
                                <strong>Critical:</strong> Emergency situation requiring immediate attention.
                            </small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="bi bi-send-fill"></i> Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>

