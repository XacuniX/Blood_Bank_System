<?php
session_start();

// STRICT Access Control - Administrator Only
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: staff_login.php");
    exit();
}

require 'db_connect.php';
include 'includes/header.php';

// Get role filter from GET request
$role_filter = isset($_GET['role']) ? $_GET['role'] : 'All';

// Build the query based on filter
if ($role_filter === 'All') {
    $sql = "SELECT * FROM Audit_Log ORDER BY Log_Date DESC";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT * FROM Audit_Log WHERE Role = ? ORDER BY Log_Date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $role_filter);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="text-danger mb-2"><i class="bi bi-file-text me-2"></i>Audit Log</h2>
            <p class="text-muted mb-3">View all system activity logs</p>
            <a href="staff_admin_dashboard.php" class="btn btn-outline-danger">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filter Dropdown -->
    <div class="row mb-3">
        <div class="col-md-4">
            <form method="GET" class="d-flex align-items-center gap-2">
                <label for="role" class="form-label mb-0 fw-semibold">Filter by Role:</label>
                <select name="role" id="role" class="form-select" onchange="this.form.submit()">
                    <option value="All" <?php echo ($role_filter === 'All') ? 'selected' : ''; ?>>All Roles</option>
                    <option value="Staff" <?php echo ($role_filter === 'Staff') ? 'selected' : ''; ?>>Staff</option>
                    <option value="Donor" <?php echo ($role_filter === 'Donor') ? 'selected' : ''; ?>>Donor</option>
                    <option value="Hospital" <?php echo ($role_filter === 'Hospital') ? 'selected' : ''; ?>>Hospital</option>
                    <option value="System" <?php echo ($role_filter === 'System') ? 'selected' : ''; ?>>System</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Audit Log Table -->
    <div class="row">
        <div class="col">
            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Date/Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Target</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                    // Determine row background color
                                    $row_class = ($row['Role'] === 'System') ? 'table-secondary' : '';
                                    
                                    // Determine action text color
                                    $action_color = '';
                                    $action_style = '';
                                    if ($row['Action_Type'] === 'DELETE') {
                                        $action_color = 'text-danger fw-bold';
                                    } elseif ($row['Action_Type'] === 'INSERT') {
                                        $action_color = 'text-success fw-bold';
                                    } elseif ($row['Action_Type'] === 'UPDATE') {
                                        $action_color = 'fw-bold';
                                        $action_style = 'color: #ff8800;';
                                    } elseif ($row['Action_Type'] === 'LOGIN') {
                                        $action_color = 'fw-bold';
                                    }
                                ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($row['Log_Date'])); ?></td>
                                    <td>
                                        <?php 
                                            echo htmlspecialchars($row['Performed_By']) . ' (' . htmlspecialchars($row['Role']) . ')'; 
                                        ?>
                                    </td>
                                    <td class="<?php echo $action_color; ?>" style="<?php echo $action_style; ?>">
                                        <?php echo htmlspecialchars($row['Action_Type']); ?>
                                    </td>
                                    <td>
                                        <?php 
                                            echo htmlspecialchars($row['Target_Table']);
                                            if (!empty($row['Target_ID'])) {
                                                echo ' (ID: ' . htmlspecialchars($row['Target_ID']) . ')';
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['Details']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted">Total records: <?php echo $result->num_rows; ?></p>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No audit logs found.
                    <?php if ($role_filter !== 'All'): ?>
                        <a href="staff_admin_audit.php">Clear filter</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$stmt->close();
$conn->close();
include 'includes/footer.php';
?>
