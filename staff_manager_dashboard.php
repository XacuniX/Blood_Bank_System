<?php
include 'staff_session_check.php';

// Check if user is a Manager
if ($_SESSION['role'] !== 'Manager') {
    header("Location: staff_login.php");
    exit();
}

include 'db_connect.php';

// Get Total Donors
$total_donors = 0;
$donors_result = $conn->query("SELECT COUNT(*) as count FROM donor");
if ($donors_result) {
    $total_donors = $donors_result->fetch_assoc()['count'];
}

// Get Registered Hospitals
$total_hospitals = 0;
$hospitals_result = $conn->query("SELECT COUNT(*) as count FROM hospital");
if ($hospitals_result) {
    $total_hospitals = $hospitals_result->fetch_assoc()['count'];
}

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="text-danger"><i class="bi bi-speedometer2 me-2"></i>Manager Dashboard</h2>
            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        </div>
        <div class="col-auto">
            <a href="staff_logout.php" class="btn btn-outline-danger">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4 mt-5">
        <div class="col-lg-10 mx-auto">
            <div class="row g-4">
                <!-- Total Donors Card -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-danger h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-people-fill text-danger mb-3" style="font-size: 3rem;"></i>
                            <h3 class="card-title text-danger mb-2"><?php echo number_format($total_donors); ?></h3>
                            <p class="card-text text-muted mb-0">Total Donors</p>
                        </div>
                    </div>
                </div>

                <!-- Registered Hospitals Card -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-danger h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-hospital-fill text-danger mb-3" style="font-size: 3rem;"></i>
                            <h3 class="card-title text-danger mb-2"><?php echo number_format($total_hospitals); ?></h3>
                            <p class="card-text text-muted mb-0">Registered Hospitals</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Management Actions -->
    <div class="row mb-4">
        <div class="col-lg-10 mx-auto">
            <div class="row g-4">
                <!-- Hospital Management Card -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-danger h-100">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="bi bi-hospital me-2"></i>Hospital Management</h5>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <p class="card-text mb-4">Register new hospitals and manage existing hospital records in the blood bank system.</p>
                            <div class="mt-auto">
                                <a href="staff_manager_hospitals.php" class="btn btn-danger w-100">
                                    <i class="bi bi-building-fill-gear me-2"></i>Manage Hospitals
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Donor Management Card -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-danger h-100">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="bi bi-person-fill-gear me-2"></i>Donor Management</h5>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <p class="card-text mb-4">Edit and manage donor records, update contact information and eligibility status.</p>
                            <div class="mt-auto">
                                <a href="staff_manager_donors.php" class="btn btn-danger w-100">
                                    <i class="bi bi-people-fill me-2"></i>Manage Donors
                                </a>
                            </div>
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
