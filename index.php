<?php 
// Start session and check if user is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect logged-in users to their respective dashboards
if (isset($_SESSION['donor_id'])) {
    header("Location: donor_dashboard.php");
    exit();
} elseif (isset($_SESSION['hospital_id'])) {
    header("Location: hospital_dashboard.php");
    exit();
} elseif (isset($_SESSION['staff_id'])) {
    // Redirect to appropriate staff dashboard based on role
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === 'Officer') {
            header("Location: staff_officer_dashboard.php");
        } elseif ($_SESSION['role'] === 'Manager') {
            header("Location: staff_manager_dashboard.php");
        } elseif ($_SESSION['role'] === 'Admin') {
            header("Location: staff_admin_dashboard.php");
        }
        exit();
    }
}

include 'includes/header.php'; 
?>

<script>
// Add login button to navbar only on homepage
document.addEventListener('DOMContentLoaded', function() {
    const navbarNav = document.querySelector('#mainNavbar .navbar-nav');
    if (navbarNav) {
        const loginItem = document.createElement('li');
        loginItem.className = 'nav-item';
        loginItem.innerHTML = '<a class="nav-link fw-semibold text-white" href="#portals"><i class="bi bi-box-arrow-in-right"></i> Login</a>';
        navbarNav.appendChild(loginItem);
    }
});
</script>

<!-- Hero Section -->
<div class="bg-danger text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-3 fw-bold mb-4">LifeSaver Blood Bank</h1>
                <p class="lead mb-4">Every drop counts. Join us in saving lives through blood donation and efficient
                    blood bank management.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="donor_register.php" class="btn btn-light btn-lg px-4">
                        <i class="bi bi-heart-fill"></i> Become a Donor
                    </a>
                    <a href="#services" class="btn btn-outline-light btn-lg px-4">Learn More</a>
                </div>
            </div>
            <div class="col-lg-6 text-center mt-4 mt-lg-0">
                <i class="bi bi-droplet-fill" style="font-size: 200px; opacity: 0.3;"></i>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats Section -->
<div class="container my-5">
    <div class="row text-center g-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <i class="bi bi-people-fill text-danger" style="font-size: 3rem;"></i>
                    <h3 class="mt-3">Donors</h3>
                    <p class="text-muted">Registered donors ready to help</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <i class="bi bi-droplet-fill text-danger" style="font-size: 3rem;"></i>
                    <h3 class="mt-3">Blood Units</h3>
                    <p class="text-muted">Available blood units in stock</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <i class="bi bi-hospital-fill text-danger" style="font-size: 3rem;"></i>
                    <h3 class="mt-3">Hospitals</h3>
                    <p class="text-muted">Partner hospitals served</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <i class="bi bi-clock-history text-danger" style="font-size: 3rem;"></i>
                    <h3 class="mt-3">24/7</h3>
                    <p class="text-muted">Round the clock service</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Why Donate Section -->
<div class="bg-light py-5" id="services">
    <div class="container">
        <h2 class="text-center mb-5 display-5 fw-bold">Why Donate Blood?</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow h-100">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-heart-pulse-fill text-danger" style="font-size: 3.5rem;"></i>
                        <h4 class="mt-3">Save Lives</h4>
                        <p class="text-muted">One blood donation can save up to three lives. Be someone's hero today.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow h-100">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-shield-fill-check text-danger" style="font-size: 3.5rem;"></i>
                        <h4 class="mt-3">Safe Process</h4>
                        <p class="text-muted">All donations are conducted with sterile equipment in a safe, monitored environment.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow h-100">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-people-fill text-danger" style="font-size: 3.5rem;"></i>
                        <h4 class="mt-3">Join Community</h4>
                        <p class="text-muted">Become part of a community of lifesavers making a difference every day.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Portal Access Section -->
<div class="container my-5" id="portals">
    <h2 class="text-center mb-5 display-5 fw-bold">Access Portals</h2>
    <div class="row g-4 justify-content-center">
        <!-- Donor Portal -->
        <div class="col-lg-4 col-md-6">
            <div class="card border-danger shadow-lg h-100 hover-card">
                <div class="card-header bg-danger text-white text-center py-4">
                    <i class="bi bi-person-heart" style="font-size: 4rem;"></i>
                    <h3 class="mt-3 mb-0">Donor Portal</h3>
                </div>
                <div class="card-body text-center d-flex flex-column">
                    <p class="text-muted flex-grow-1">Access your donor dashboard, view donation history, and manage
                        your profile.</p>
                    <div class="d-grid gap-2">
                        <a href="donor_login.php" class="btn btn-danger btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Donor Login
                        </a>
                        <a href="donor_register.php" class="btn btn-outline-danger">
                            <i class="bi bi-person-plus"></i> Register as Donor
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hospital Portal -->
        <div class="col-lg-4 col-md-6">
            <div class="card border-primary shadow-lg h-100 hover-card">
                <div class="card-header bg-primary text-white text-center py-4">
                    <i class="bi bi-hospital" style="font-size: 4rem;"></i>
                    <h3 class="mt-3 mb-0">Hospital Portal</h3>
                </div>
                <div class="card-body text-center d-flex flex-column">
                    <p class="text-muted flex-grow-1">Request blood units, manage blood requests, and access hospital dashboard for efficient blood management.</p>
                    <div class="d-grid">
                        <a href="hospital_login.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Hospital Login
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff Portal -->
        <div class="col-lg-4 col-md-6">
            <div class="card border-dark shadow-lg h-100 hover-card">
                <div class="card-header bg-dark text-white text-center py-4">
                    <i class="bi bi-shield-lock" style="font-size: 4rem;"></i>
                    <h3 class="mt-3 mb-0">Staff Portal</h3>
                </div>
                <div class="card-body text-center d-flex flex-column">
                    <p class="text-muted flex-grow-1">Manage blood inventory, process requests, oversee operations, and maintain blood bank records efficiently.</p>
                    <div class="d-grid">
                        <a href="staff_login.php" class="btn btn-dark btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Staff Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Blood Groups Info Section -->
<div class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-4 display-6 fw-bold">Blood Groups</h2>
        <p class="text-center text-muted mb-5">Know your blood type and compatibility</p>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="card text-center shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="text-danger fw-bold">A+</h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="text-danger fw-bold">A-</h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="text-danger fw-bold">B+</h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="text-danger fw-bold">B-</h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="text-danger fw-bold">AB+</h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="text-danger fw-bold">AB-</h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="text-danger fw-bold">O+</h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="text-danger fw-bold">O-</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action Section -->
<div class="bg-danger text-white py-5">
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="display-4 fw-bold mb-4">Ready to Save Lives?</h2>
                <p class="lead mb-4">
                    Your blood donation can save up to three lives. Join our community of heroes today and make a lasting impact.
                </p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="donor_register.php" class="btn btn-light btn-lg px-5">
                        <i class="bi bi-heart-fill"></i> Register as Donor
                    </a>
                    <a href="donor_login.php" class="btn btn-outline-light btn-lg px-5">
                        <i class="bi bi-box-arrow-in-right"></i> Donor Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .hover-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .175) !important;
    }
</style>

<?php include 'includes/footer.php'; ?>