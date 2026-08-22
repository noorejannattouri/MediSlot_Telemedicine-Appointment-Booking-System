<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Protect page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = "Dashboard";
require_once 'header.php';
?>

<style>
    .dashboard-hero {
        background: linear-gradient(rgba(13, 110, 253, 0.85), rgba(11, 94, 215, 0.9)),
                    url('https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
        background-size: cover;
        background-position: center;
        border-radius: 16px;
        color: white;
        padding: 40px 30px;
        margin-bottom: 30px;
    }

    .stat-card {
        border: none;
        border-radius: 16px;
        transition: all 0.3s ease;
        height: 100%;
    }

    .stat-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 25px rgba(0,0,0,0.1);
    }

    .icon-box {
        width: 60px;
        height: 60px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
    }
</style>

<!-- Hero Section with Background -->
<div class="dashboard-hero">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
            <p class="mb-0 opacity-75">You are logged in as <strong><?php echo ucfirst($_SESSION['role']); ?></strong></p>
        </div>
        <a href="logout.php" class="btn btn-light">
            <i class="bi bi-box-arrow-right me-1"></i> Logout
        </a>
    </div>
</div>

<!-- Main Cards -->
<div class="row g-4 mb-4">

    <div class="col-md-4">
        <div class="card stat-card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <h5 class="fw-bold mb-0">My Appointments</h5>
                </div>
                <p class="text-muted mb-3">View and manage your upcoming appointments with doctors.</p>
                <a href="my-appointments.php" class="btn btn-primary btn-sm">View Appointments</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-box bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-search"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Find Doctors</h5>
                </div>
                <p class="text-muted mb-3">Search verified doctors and book appointments easily.</p>
                <a href="doctors.php" class="btn btn-success btn-sm">Find Doctors</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-box bg-info bg-opacity-10 text-info me-3">
                        <i class="bi bi-robot"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Medicine AI</h5>
                </div>
                <p class="text-muted mb-3">Get instant information about medicines using AI assistant.</p>
                <a href="medicine-assistant.php" class="btn btn-info btn-sm text-white">Ask AI</a>
            </div>
        </div>
    </div>

</div>

<!-- Quick Actions -->
<div class="card border-0 shadow-sm" style="border-radius: 16px;">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-3">Quick Actions</h5>
        <div class="d-flex flex-wrap gap-2">
            <a href="profile.php" class="btn btn-outline-primary">
                <i class="bi bi-person me-1"></i> My Profile
            </a>
            <a href="medical-records.php" class="btn btn-outline-primary">
                <i class="bi bi-file-medical me-1"></i> Medical Records
            </a>
            <a href="my-appointments.php" class="btn btn-outline-primary">
                <i class="bi bi-calendar2-check me-1"></i> Appointments
            </a>
            <a href="doctors.php" class="btn btn-outline-success">
                <i class="bi bi-search me-1"></i> Find Doctors
            </a>
            <a href="logout.php" class="btn btn-outline-danger">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>