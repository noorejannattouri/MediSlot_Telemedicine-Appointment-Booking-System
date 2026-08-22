<?php
require_once 'auth_check.php';
$page_title = "Dashboard";

// Stats
$total_users     = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'patient'")->fetch_assoc()['c'];
$total_doctors   = $conn->query("SELECT COUNT(*) AS c FROM doctors")->fetch_assoc()['c'];
$verified_docs   = $conn->query("SELECT COUNT(*) AS c FROM doctors WHERE is_verified = 1")->fetch_assoc()['c'];
$pending_docs    = $conn->query("SELECT COUNT(*) AS c FROM doctors WHERE is_verified = 0")->fetch_assoc()['c'];
$total_appts     = $conn->query("SELECT COUNT(*) AS c FROM appointments")->fetch_assoc()['c'];
$completed_appts = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status = 'completed'")->fetch_assoc()['c'];
$total_revenue   = $conn->query("SELECT COALESCE(SUM(amount),0) AS s FROM payments WHERE status = 'completed'")->fetch_assoc()['s'];
$pending_pay     = $conn->query("SELECT COUNT(*) AS c FROM payments WHERE status = 'pending'")->fetch_assoc()['c'];

require_once 'header.php';
?>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-muted mb-1 small">Patients</p>
                        <h3 class="fw-bold mb-0"><?php echo $total_users; ?></h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-muted mb-1 small">Verified Doctors</p>
                        <h3 class="fw-bold mb-0"><?php echo $verified_docs; ?></h3>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-3">
                        <i class="bi bi-person-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-muted mb-1 small">Pending Doctors</p>
                        <h3 class="fw-bold mb-0"><?php echo $pending_docs; ?></h3>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-muted mb-1 small">Total Revenue</p>
                        <h3 class="fw-bold mb-0">৳<?php echo number_format($total_revenue, 2); ?></h3>
                    </div>
                    <div class="bg-info bg-opacity-10 text-info rounded-3 p-3">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Appointments Overview</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        Total Appointments <span class="badge bg-primary"><?php echo $total_appts; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        Completed <span class="badge bg-success"><?php echo $completed_appts; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        Pending Payments <span class="badge bg-warning text-dark"><?php echo $pending_pay; ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Quick Actions</h5>
                <div class="d-grid gap-2">
                    <a href="verify_doctors.php" class="btn btn-outline-primary">
                        <i class="bi bi-person-check me-2"></i>Verify Pending Doctors (<?php echo $pending_docs; ?>)
                    </a>
                    <a href="manage_users.php" class="btn btn-outline-secondary">
                        <i class="bi bi-people me-2"></i>Manage Users
                    </a>
                    <a href="reports.php" class="btn btn-outline-info">
                        <i class="bi bi-bar-chart me-2"></i>View Full Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>