<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Today's appointments
$stmt = $conn->prepare("
    SELECT a.appointment_id, a.status, a.created_at,
           u.name AS patient_name, u.phone AS patient_phone,
           ts.slot_date, ts.start_time, ts.end_time
    FROM appointments a
    JOIN users u ON a.patient_id = u.user_id
    JOIN time_slots ts ON a.slot_id = ts.slot_id
    WHERE a.doctor_id = ? AND ts.slot_date = ?
    ORDER BY ts.start_time ASC
");
$stmt->bind_param("is", $doctor_id, $today);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Counts
$total_today = count($appointments);
$confirmed = 0;
$pending = 0;
foreach ($appointments as $a) {
    if ($a['status'] === 'confirmed') $confirmed++;
    if ($a['status'] === 'pending') $pending++;
}

$page_title = "Doctor Dashboard";
require_once '../header.php';
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1">Welcome, Dr. <?php echo htmlspecialchars($_SESSION['name']); ?></h2>
            <p class="text-muted mb-0">Today's Schedule — <?php echo date('l, d F Y'); ?></p>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex flex-wrap gap-2">
            <a href="manage_schedule.php" class="btn btn-primary">
                <i class="bi bi-calendar-plus me-1"></i> Manage Schedule
            </a>
            <a href="view_appointments.php" class="btn btn-success">
                <i class="bi bi-calendar-check me-1"></i> View Appointments
            </a>
            <a href="patient_records.php" class="btn btn-info text-white">
                <i class="bi bi-folder2-open me-1"></i> Patient Records
            </a>
            <a href="issue_prescription.php" class="btn btn-warning">
                <i class="bi bi-prescription2 me-1"></i> Issue Prescription
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Pending Appointments</div>
                    <h2 class="fw-bold text-warning mb-0"><?php echo $pending; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Confirmed Appointments</div>
                    <h2 class="fw-bold text-success mb-0"><?php echo $confirmed; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Today's Appointments</div>
                    <h2 class="fw-bold text-primary mb-0"><?php echo $total_today; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Appointments Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold">Today's Appointments</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($appointments)): ?>
                <div class="text-center py-5 text-muted">
                    No appointments scheduled for today.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $a): ?>
                                <tr>
                                    <td>
                                        <?php echo date('h:i A', strtotime($a['start_time'])); ?>
                                        –
                                        <?php echo date('h:i A', strtotime($a['end_time'])); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($a['patient_name']); ?></td>
                                    <td><?php echo htmlspecialchars($a['patient_phone'] ?? '—'); ?></td>
                                    <td>
                                        <?php
                                        $badge = match($a['status']) {
                                            'confirmed' => 'success',
                                            'pending'   => 'warning',
                                            'completed' => 'primary',
                                            'cancelled' => 'danger',
                                            default     => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $badge; ?>">
                                            <?php echo ucfirst($a['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_appointments.php" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="issue_prescription.php?appointment_id=<?php echo $a['appointment_id']; ?>" 
                                           class="btn btn-sm btn-outline-success">Prescribe</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>