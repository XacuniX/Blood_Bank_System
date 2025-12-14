<?php include 'includes/header.php'; ?>
<?php
// Suppress any direct output from the DB include (e.g., debug echoes)
ob_start();
include 'db_connect.php';
ob_end_clean();

$rows = [];
$selectedBloodGroup = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedBloodGroup = $_POST['blood_group'] ?? '';
    
    if (!empty($selectedBloodGroup)) {
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM Blood_Unit WHERE Blood_Group = ? AND Status = 'Available'");
        if ($stmt) {
            $stmt->bind_param("s", $selectedBloodGroup);
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $errorMessage = "Database query error: " . $conn->error;
        }
    }
}
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Search Blood Units</h2>
        <span class="text-muted">Find available blood units</span>
    </div>

    <?php if (!empty($errorMessage)) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($errorMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="post" action="" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label for="blood_group" class="form-label">Blood Group</label>
                    <select class="form-select" id="blood_group" name="blood_group" required>
                        <option value="" <?php echo empty($selectedBloodGroup) ? 'selected' : ''; ?> disabled>Select blood group</option>
                        <option value="A+" <?php echo $selectedBloodGroup === 'A+' ? 'selected' : ''; ?>>A+</option>
                        <option value="A-" <?php echo $selectedBloodGroup === 'A-' ? 'selected' : ''; ?>>A-</option>
                        <option value="B+" <?php echo $selectedBloodGroup === 'B+' ? 'selected' : ''; ?>>B+</option>
                        <option value="B-" <?php echo $selectedBloodGroup === 'B-' ? 'selected' : ''; ?>>B-</option>
                        <option value="AB+" <?php echo $selectedBloodGroup === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                        <option value="AB-" <?php echo $selectedBloodGroup === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                        <option value="O+" <?php echo $selectedBloodGroup === 'O+' ? 'selected' : ''; ?>>O+</option>
                        <option value="O-" <?php echo $selectedBloodGroup === 'O-' ? 'selected' : ''; ?>>O-</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-danger w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($selectedBloodGroup)) : ?>
        <?php if (!empty($rows)) : ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                    <tr>
                        <?php foreach (array_keys($rows[0]) as $column) : ?>
                            <th scope="col"><?php echo htmlspecialchars($column); ?></th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <?php foreach ($row as $column => $value) : ?>
                                <td>
                                    <?php if (strtolower($column) === 'status') : ?>
                                        <?php
                                        $status = strtolower((string)$value);
                                        $badgeClass = match ($status) {
                                            'available' => 'text-bg-success',
                                            'used' => 'text-bg-danger',
                                            'reserved' => 'text-bg-primary',
                                            default => 'text-bg-secondary',
                                        };
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($value); ?>
                                        </span>
                                    <?php else : ?>
                                        <?php echo htmlspecialchars((string)$value); ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="alert alert-info" role="alert">
                No available blood units found for blood group <strong><?php echo htmlspecialchars($selectedBloodGroup); ?></strong>.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

