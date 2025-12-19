<?php include 'includes/header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm text-center">
                <div class="card-body py-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h2 class="card-title mb-3">Registration Successful!</h2>
                    <p class="text-muted mb-4">Your donor account has been created successfully. You can now log in to access your dashboard.</p>
                    
                    <div class="d-grid gap-3">
                        <a href="donor_login.php" class="btn btn-danger btn-lg">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Go to Donor Login
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-house-door me-2"></i>Go to Home Page
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
