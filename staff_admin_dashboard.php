<?php
session_start();

// STRICT Access Control - Admin Only
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: staff_login.php");
    exit();
}

// Database Backup Logic using mysqldump
if (isset($_POST['backup_btn'])) {
    // Database credentials
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $database = 'bloodbank';
    
    $filename = "bloodbank_backup_" . date('Y-m-d_H-i-s') . ".sql";
    $filepath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
    
    // Build mysqldump command
    // Try default location first, then custom location
    $mysqldump_path = "C:\\xampp\\mysql\\bin\\mysqldump.exe";
    if (!file_exists($mysqldump_path)) {
        $mysqldump_path = "E:\\NSU Study\\Xampp\\mysql\\bin\\mysqldump.exe";
    }
    
    if ($password === '') {
        $command = "\"{$mysqldump_path}\" --host={$host} --user={$user} {$database} > \"{$filepath}\"";
    } else {
        $command = "\"{$mysqldump_path}\" --host={$host} --user={$user} --password={$password} {$database} > \"{$filepath}\"";
    }
    
    exec($command, $output, $result);
    
    if ($result === 0 && file_exists($filepath)) {
        // Send file to browser for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        readfile($filepath);
        unlink($filepath); // Delete temp file
        exit();
    } else {
        $_SESSION['backup_error'] = "Backup failed. Please ensure mysqldump is accessible.";
        header("Location: staff_admin_dashboard.php");
        exit();
    }
}

// Get Statistics
include 'db_connect.php';

$totalStaff = 0;
$totalDonors = 0;
$totalHospitals = 0;

if ($conn instanceof mysqli && !$conn->connect_error) {
    // Get Total Staff
    $result = $conn->query("SELECT COUNT(*) as count FROM Staff");
    if ($result) {
        $row = $result->fetch_assoc();
        $totalStaff = $row['count'];
    }
    
    // Get Total Donors
    $result = $conn->query("SELECT COUNT(*) as count FROM Donor");
    if ($result) {
        $row = $result->fetch_assoc();
        $totalDonors = $row['count'];
    }
    
    // Get Total Hospitals
    $result = $conn->query("SELECT COUNT(*) as count FROM Hospital");
    if ($result) {
        $row = $result->fetch_assoc();
        $totalHospitals = $row['count'];
    }
    
    $conn->close();
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0">Admin Dashboard</h2>
                <p class="text-muted mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></p>
            </div>
            <div>
                <a href="staff_logout.php" class="btn btn-outline-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title text-muted">Total Staff</h5>
                    <h2 class="display-4 text-primary"><?php echo $totalStaff; ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title text-muted">Total Donors</h5>
                    <h2 class="display-4 text-success"><?php echo $totalDonors; ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title text-muted">Total Hospitals</h5>
                    <h2 class="display-4 text-info"><?php echo $totalHospitals; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Actions -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-people-fill"></i> User Management
                    </h5>
                    <p class="card-text">Manage staff members, donors, and hospital accounts.</p>
                    <a href="staff_admin_users.php" class="btn btn-primary">
                        <i class="bi bi-gear-fill"></i> Manage Users
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-file-text-fill"></i> Audit Logs
                    </h5>
                    <p class="card-text">View system activity logs and monitor past user actions.</p>
                    <div class="mt-3">
                        <a href="staff_admin_audit.php" class="btn btn-warning">
                            <i class="bi bi-eye-fill"></i> View Audit Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-database-fill"></i> Database Backup
                    </h5>
                    <p class="card-text">Download a complete backup of the database in SQL format.</p>
                    <form method="POST" action="" class="mt-3">
                        <button type="submit" name="backup_btn" class="btn btn-danger">
                            <i class="bi bi-download"></i> Download Backup
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
