<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Patient Dashboard";
require_once '../header.php';
?>

<style>
    .stat-card {
        border: none;
        border-radius: 16px;
        transition: all 0.3s ease;
        height: 100%;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    }
    .icon-box {
        width: 55px;
        height: 55px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
</style>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
            <p class="text-muted mb-0">Manage your health appointments easily</p>
        </div>
        <a href="../logout.php" class="btn btn-outline-danger">
            <i class="bi bi-box-arrow-right me-1"></i> Logout
        </a>
    </div>

    <div class="row g-4">

        <!-- My Appointments -->
        <div class="col-md-4">
            <div class="card stat-card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <h5 class="fw-bold mb-0">My Appointments</h5>
                    </div>
                    <p class="text-muted mb-3">View and manage your upcoming appointments.</p>
                    <a href="appointments.php" class="btn btn-primary btn-sm">View Appointments</a>
                </div>
            </div>
        </div>

        <!-- Find Doctors -->
        <div class="col-md-4">
            <div class="card stat-card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-search"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Find Doctors</h5>
                    </div>
                    <p class="text-muted mb-3">Search verified doctors and book appointments.</p>
                    <a href="search_doctors.php" class="btn btn-success btn-sm">Find Doctors</a>
                </div>
            </div>
        </div>

        <!-- Medicine AI -->
        <div class="col-md-4">
            <div class="card stat-card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-robot"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Medicine AI</h5>
                    </div>
                    <p class="text-muted mb-3">Get information about medicines using AI.</p>
                    <a href="medicine_assistant.php" class="btn btn-info btn-sm text-white">Ask AI</a>
                </div>
            </div>
        </div>

        <!-- AI Doctor Matching -->
        <div class="col-md-4">
            <div class="card stat-card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-robot"></i>
                        </div>
                        <h5 class="fw-bold mb-0">AI Doctor Matching</h5>
                    </div>
                    <p class="text-muted mb-3">Describe symptoms and get doctor recommendations.</p>
                    <a href="doctor_matching.php" class="btn btn-primary btn-sm">Try AI Matching</a>
                </div>
            </div>
        </div>

        <!-- Medical Records -->
        <div class="col-md-4">
            <div class="card stat-card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-danger bg-opacity-10 text-danger me-3">
                            <i class="bi bi-file-medical"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Medical Records</h5>
                    </div>
                    <p class="text-muted mb-3">View your visit history and diagnoses.</p>
                    <a href="medical_records.php" class="btn btn-danger btn-sm">View Records</a>
                </div>
            </div>
        </div>

        <!-- Prescriptions -->
        <div class="col-md-4">
            <div class="card stat-card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-prescription2"></i>
                        </div>
                        <h5 class="fw-bold mb-0">My Prescriptions</h5>
                    </div>
                    <p class="text-muted mb-3">View all prescriptions from your doctors.</p>
                    <a href="prescriptions.php" class="btn btn-warning btn-sm">View Prescriptions</a>
                </div>
            </div>
        </div>

        <!-- Profile -->
        <div class="col-md-4">
            <div class="card stat-card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-secondary bg-opacity-10 text-secondary me-3">
                            <i class="bi bi-person"></i>
                        </div>
                        <h5 class="fw-bold mb-0">My Profile</h5>
                    </div>
                    <p class="text-muted mb-3">Update your personal information.</p>
                    <a href="profile.php" class="btn btn-secondary btn-sm">Edit Profile</a>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once '../footer.php'; ?>