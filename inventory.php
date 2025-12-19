<?php include 'includes/header.php'; ?>
<?php
// Suppress any direct output from the DB include (e.g., debug echoes)
ob_start();
include 'db_connect.php';
ob_end_clean();

$sql = "SELECT * FROM Blood_Unit 
        WHERE NOT (Status = 'Used' OR Status = 'Expired')
        ORDER BY Collection_Date DESC";
$result = $conn->query($sql);
$rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Inventory</h2>
        <span class="text-muted">Blood units overview</span>
    </div>

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
            No blood units found.
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
