<?php
session_start();
include 'staff_session_check.php';

ob_start();
include 'db_connect.php';
include 'audit_logger.php';
ob_end_clean();

include 'includes/header.php';

// Get filter parameters
$action_filter = $_GET['action'] ?? 'all';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

// Get audit logs
if ($action_filter === 'all') {
    $logs = get_audit_logs($conn, $limit);
} else {
    $logs = get_audit_logs($conn, $limit, $action_filter);
}

// Get statistics
$stats = get_audit_statistics($conn);
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-clipboard-data"></i> Audit Logs</h2>
        <a href="staff_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <?php 
        $stat_array = [];
        while ($stat = $stats->fetch_assoc()) {
            $stat_array[$stat['Action_Type']] = $stat['count'];
        }
        
        $action_types = ['INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT'];
        $colors = ['primary', 'warning', 'danger', 'success', 'secondary'];
        
        foreach ($action_types as $index => $type):
            $count = $stat_array[$type] ?? 0;
        ?>
        <div class="col-md-2 mb-3">
            <div class="card border-<?php echo $colors[$index]; ?>">
                <div class="card-body text-center">
                    <h6 class="text-<?php echo $colors[$index]; ?>"><?php echo $type; ?></h6>
                    <h4><?php echo $count; ?></h4>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filter Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Action Type</label>
                    <select name="action" class="form-select">
                        <option value="all" <?php echo $action_filter === 'all' ? 'selected' : ''; ?>>All Actions</option>
                        <option value="INSERT" <?php echo $action_filter === 'INSERT' ? 'selected' : ''; ?>>INSERT</option>
                        <option value="UPDATE" <?php echo $action_filter === 'UPDATE' ? 'selected' : ''; ?>>UPDATE</option>
                        <option value="DELETE" <?php echo $action_filter === 'DELETE' ? 'selected' : ''; ?>>DELETE</option>
                        <option value="LOGIN" <?php echo $action_filter === 'LOGIN' ? 'selected' : ''; ?>>LOGIN</option>
                        <option value="LOGOUT" <?php echo $action_filter === 'LOGOUT' ? 'selected' : ''; ?>>LOGOUT</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Limit</label>
                    <select name="limit" class="form-select">
                        <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50 records</option>
                        <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100 records</option>
                        <option value="200" <?php echo $limit === 200 ? 'selected' : ''; ?>>200 records</option>
                        <option value="500" <?php echo $limit === 500 ? 'selected' : ''; ?>>500 records</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Logs Table -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Audit Trail</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Log ID</th>
                            <th>Staff</th>
                            <th>Action Type</th>
                            <th>Description</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($logs && $logs->num_rows > 0): ?>
                            <?php while ($log = $logs->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['Log_ID']); ?></td>
                                    <td>
                                        <?php if ($log['Username']): ?>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($log['Username']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">System/User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badge_color = match($log['Action_Type']) {
                                            'INSERT' => 'bg-primary',
                                            'UPDATE' => 'bg-warning',
                                            'DELETE' => 'bg-danger',
                                            'LOGIN' => 'bg-success',
                                            'LOGOUT' => 'bg-secondary',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $badge_color; ?>">
                                            <?php echo htmlspecialchars($log['Action_Type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['Description']); ?></td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y H:i:s', strtotime($log['Timestamp'])); ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No audit logs found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
