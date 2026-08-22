<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];

// Get all prescriptions for this patient
$stmt = $conn->prepare("
    SELECT p.prescription_id, p.medicines, p.notes, p.issued_date,
           u.name AS doctor_name, d.specialization
    FROM prescriptions p
    JOIN users u ON p.doctor_id = u.user_id
    JOIN doctors d ON p.doctor_id = d.doctor_id
    WHERE p.patient_id = ?
    ORDER BY p.issued_date DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = "My Prescriptions";
require_once '../header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">My Prescriptions</h2>
            <p class="text-muted mb-0">All prescriptions issued by your doctors</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary">← Dashboard</a>
    </div>

    <?php if (empty($prescriptions)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-prescription2 fs-1 text-muted d-block mb-3"></i>
                <h5>No prescriptions found</h5>
                <p class="text-muted">You don't have any prescriptions yet.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($prescriptions as $p): ?>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1">Dr. <?php echo htmlspecialchars($p['doctor_name']); ?></h5>
                                    <small class="text-muted"><?php echo htmlspecialchars($p['specialization']); ?></small>
                                </div>
                                <span class="badge bg-primary">
                                    <?php echo date('d M Y', strtotime($p['issued_date'])); ?>
                                </span>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted small">Medicines</label>
                                <div class="bg-light rounded p-3" style="white-space: pre-line;">
                                    <?php echo htmlspecialchars($p['medicines']); ?>
                                </div>
                            </div>

                            <?php if (!empty($p['notes'])): ?>
                                <div>
                                    <label class="form-label fw-semibold text-muted small">Notes / Instructions</label>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($p['notes'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../footer.php'; ?>