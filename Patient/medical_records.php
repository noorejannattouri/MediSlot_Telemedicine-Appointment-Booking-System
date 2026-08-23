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

// Get medical records
$stmt = $conn->prepare("
    SELECT mr.record_id, mr.diagnosis, mr.notes, mr.visit_date,
           u.name AS doctor_name, d.specialization
    FROM medical_records mr
    JOIN users u ON mr.doctor_id = u.user_id
    JOIN doctors d ON mr.doctor_id = d.doctor_id
    WHERE mr.patient_id = ?
    ORDER BY mr.visit_date DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = "Medical Records";
require_once '../header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">My Medical Records</h2>
            <p class="text-muted mb-0">Your visit history and diagnoses</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary">← Dashboard</a>
    </div>

    <?php if (empty($records)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-file-medical fs-1 text-muted d-block mb-3"></i>
                <h5>No medical records found</h5>
                <p class="text-muted">Your medical records will appear here after doctor visits.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($records as $r): ?>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($r['diagnosis']); ?></h5>
                                    <small class="text-muted">
                                        Dr. <?php echo htmlspecialchars($r['doctor_name']); ?> 
                                        (<?php echo htmlspecialchars($r['specialization']); ?>)
                                    </small>
                                </div>
                                <span class="badge bg-primary">
                                    <?php echo date('d M Y', strtotime($r['visit_date'])); ?>
                                </span>
                            </div>

                            <?php if (!empty($r['notes'])): ?>
                                <div class="bg-light rounded p-3">
                                    <small class="text-muted d-block mb-1">Notes:</small>
                                    <?php echo nl2br(htmlspecialchars($r['notes'])); ?>
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