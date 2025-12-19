<?php
include 'donor_session_check.php'; // 1. Run the security check first!

// Suppress debug output from db_connect.php
ob_start();
include 'db_connect.php';
ob_end_clean();

include 'includes/header.php';

// Fetch the donor's details using the ID stored in the session
$donor_id = $_SESSION['donor_id'];

// Get the donor's complete information
$sql = "SELECT * FROM Donor WHERE Donor_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();
$donor_info = $result->fetch_assoc();
$stmt->close();

// Calculate next eligibility date (56 days after last donation)
$next_eligibility = null;
$days_until_eligible = null;
$is_eligible = false;

if ($donor_info['Last_Donation_Date']) {
    $last_donation = new DateTime($donor_info['Last_Donation_Date']);
    $next_eligibility = clone $last_donation;
    $next_eligibility->modify('+84 days');
    $today = new DateTime();
    $days_until_eligible = $today->diff($next_eligibility)->days;
    $is_eligible = $today >= $next_eligibility;
} else {
    $is_eligible = true; // Never donated, eligible now
}

// Get donation history count (blood units collected from this donor)
$history_sql = "SELECT COUNT(*) as donation_count FROM blood_unit WHERE Donor_ID = ?";
$stmt = $conn->prepare($history_sql);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$history_result = $stmt->get_result();
$donation_count = $history_result->fetch_assoc()['donation_count'];
$stmt->close();

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
        <h2 class="mb-0">Welcome back, <?php echo htmlspecialchars($donor_info['Name']); ?>!</h2>
        <a href="logout.php" class="btn btn-outline-danger">Logout</a>
    </div>

    <!-- Status Cards Row -->
    <div class="row mb-4">
        <!-- Eligibility Status Card -->
        <div class="col-md-4 mb-3">
            <div class="card h-100 <?php echo $is_eligible ? 'border-success' : 'border-warning'; ?>">
                <div class="card-header bg-<?php echo $is_eligible ? 'success' : 'warning'; ?> text-white">
                    <i class="bi bi-heart-pulse"></i> Donation Status
                </div>
                <div class="card-body">
                    <?php if ($is_eligible): ?>
                        <h5 class="card-title text-success">✓ Eligible to Donate</h5>
                        <p class="card-text">You can donate blood now!</p>
                    <?php else: ?>
                        <h5 class="card-title text-warning">⏳ Not Yet Eligible</h5>
                        <p class="card-text">Next eligibility: <?php echo $next_eligibility->format('M d, Y'); ?></p>
                        <p class="text-muted mb-0"><?php echo $days_until_eligible; ?> days remaining</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Last Donation Card -->
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-info">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-calendar-check"></i> Last Donation
                </div>
                <div class="card-body">
                    <h5 class="card-title">
                        <?php echo $donor_info['Last_Donation_Date'] ? date('M d, Y', strtotime($donor_info['Last_Donation_Date'])) : 'Never'; ?>
                    </h5>
                    <p class="card-text">Total Donations: <strong><?php echo $donation_count; ?></strong></p>
                </div>
            </div>
        </div>

        <!-- Profile Info Card -->
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-primary">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-person-circle"></i> Your Profile
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Blood Group:</strong>
                        <?php echo htmlspecialchars($donor_info['Blood_Group']); ?></p>
                    <p class="mb-2 d-flex justify-content-between align-items-center">
                        <span><strong>Phone:</strong> <span
                                id="phone-display"><?php echo htmlspecialchars($donor_info['Phone_Number']); ?></span></span>
                        <a href="#" class="text-primary text-decoration-none" title="Edit Phone Number"
                            onclick="editPhone(); return false;">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </p>
                    <p class="mb-2 d-flex justify-content-between align-items-center">
                        <span><strong>Password:</strong> <span id="password-display">••••••••</span></span>
                        <a href="#" class="text-primary text-decoration-none" title="Edit Password"
                            onclick="editPassword(); return false;">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </p>
                    <p class="mb-2"><strong>Age:</strong> <?php echo htmlspecialchars($donor_info['Age'] ?? 'N/A'); ?>
                    </p>
                    <p class="mb-2"><strong>Gender:</strong>
                        <?php echo htmlspecialchars($donor_info['Gender'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Donation History Section -->
    <div class="card mb-4" id="donation-history">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Donation History</h5>
        </div>
        <div class="card-body">
            <?php
            $history_sql = "SELECT Collection_Date, Blood_Group, Status, Expiry_Date 
                           FROM blood_unit 
                           WHERE Donor_ID = ? 
                           ORDER BY Collection_Date DESC 
                           LIMIT 10";
            $stmt = $conn->prepare($history_sql);
            $stmt->bind_param("i", $donor_id);
            $stmt->execute();
            $history = $stmt->get_result();
            $stmt->close();
            ?>

            <?php if ($history->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Collection Date</th>
                                <th>Blood Group</th>
                                <th>Status</th>
                                <th>Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $history->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($row['Collection_Date'])); ?></td>
                                    <td><span
                                            class="badge bg-danger"><?php echo htmlspecialchars($row['Blood_Group']); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $status = strtolower($row['Status']);
                                        $badge = match ($status) {
                                            'available' => 'bg-success',
                                            'used' => 'bg-secondary',
                                            'reserved' => 'bg-primary',
                                            'expired' => 'bg-warning',
                                            default => 'bg-light text-dark'
                                        };
                                        ?>
                                        <span
                                            class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($row['Status']); ?></span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['Expiry_Date'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No donation history found. Make your first donation today!
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Impact Stats -->
    <div class="card bg-light">
        <div class="card-body text-center">
            <h5 class="card-title">Your Impact</h5>
            <p class="card-text">
                You have donated <strong><?php echo $donation_count; ?></strong>
                time<?php echo $donation_count != 1 ? 's' : ''; ?>.
                Each donation can save up to <strong>3 lives</strong>.
                <?php if ($donation_count > 0): ?>
                    You've potentially saved <strong><?php echo $donation_count * 3; ?></strong> lives! 🎉
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<script>
    function editPhone() {
        const currentPhone = document.getElementById('phone-display').textContent;
        const newPhone = prompt('Enter new phone number:', currentPhone);
        if (newPhone && newPhone !== currentPhone) {
            // Submit to server
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'donor_update_profile.php';

            const field = document.createElement('input');
            field.type = 'hidden';
            field.name = 'field';
            field.value = 'phone';
            form.appendChild(field);

            const value = document.createElement('input');
            value.type = 'hidden';
            value.name = 'value';
            value.value = newPhone;
            form.appendChild(value);

            document.body.appendChild(form);
            form.submit();
        }
    }

    function editPassword() {
        const newPassword = prompt('Enter new password (leave blank to cancel):');
        if (newPassword && newPassword.length >= 6) {
            const confirmPassword = prompt('Confirm new password:');
            if (newPassword === confirmPassword) {
                // Submit to server
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'donor_update_profile.php';

                const field = document.createElement('input');
                field.type = 'hidden';
                field.name = 'field';
                field.value = 'password';
                form.appendChild(field);

                const value = document.createElement('input');
                value.type = 'hidden';
                value.name = 'value';
                value.value = newPassword;
                form.appendChild(value);

                document.body.appendChild(form);
                form.submit();
            } else {
                alert('Passwords do not match!');
            }
        } else if (newPassword && newPassword.length < 6) {
            alert('Password must be at least 6 characters long!');
        }
    }
</script>

<?php
$conn->close();
include 'includes/footer.php';
?>