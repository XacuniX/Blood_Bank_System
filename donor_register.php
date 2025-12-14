<?php include 'includes/header.php'; ?>
<?php
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pull form data
    $name = trim($_POST['name'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $gender = $_POST['gender'] ?? '';
    $bloodGroup = $_POST['blood_group'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate password
    if (empty($password)) {
        $errorMessage = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errorMessage = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirmPassword) {
        $errorMessage = "Passwords do not match.";
    } else {
        // Hash the password before storing
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Connect to DB (suppress debug echo from db_connect.php)
        ob_start();
        include 'db_connect.php';
        ob_end_clean();

        if ($conn instanceof mysqli && !$conn->connect_error) {
            $stmt = $conn->prepare("INSERT INTO Donor (name, age, gender, blood_group, phone_number, Password) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sissss", $name, $age, $gender, $bloodGroup, $phone, $hashedPassword);
                if ($stmt->execute()) {
                    $successMessage = "Donor registered successfully.";
                } else {
                    $errorMessage = "Failed to register donor: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $errorMessage = "Could not prepare the statement: " . $conn->error;
            }
            $conn->close();
        } else {
            $errorMessage = "Database connection failed.";
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <?php if (!empty($successMessage)) : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($successMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif (!empty($errorMessage)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($errorMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-4 text-center">Donor Registration</h3>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="age" name="age" min="18" max="100" required>
                        </div>
                        <div class="mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="" selected disabled>Select gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="blood_group" class="form-label">Blood Group</label>
                            <select class="form-select" id="blood_group" name="blood_group" required>
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
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            <small class="form-text text-muted">Password must be at least 6 characters long.</small>
                        </div>
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger">Register</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
