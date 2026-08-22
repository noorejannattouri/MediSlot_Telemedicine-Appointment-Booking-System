<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

$doctor_id  = $_SESSION['user_id'];
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

// ============================================
// CASE 1: No patient selected → show patient list
// ============================================
if ($patient_id <= 0) {

    $stmt = $conn->prepare("
        SELECT DISTINCT u.user_id, u.name, u.phone, u.email, p.blood_group,
               COUNT(a.appointment_id) AS total_visits
        FROM appointments a
        JOIN users u ON a.patient_id = u.user_id
        LEFT JOIN patients p ON u.user_id = p.patient_id
        WHERE a.doctor_id = ?
        GROUP BY u.user_id
        ORDER BY u.name ASC
    ");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $page_title = "Patient Records";
    require_once '../header.php';
    ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Patient Records</h2>
                <p class="text-muted mb-0">Select a patient to view their medical history</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary">← Dashboard</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($patients)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-people fs-1 d-block mb-2"></i>
                        No patients found yet. Appointments will appear here.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Patient Name</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Blood Group</th>
                                    <th>Total Visits</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patients as $p): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($p['name']); ?></td>
                                        <td><?php echo htmlspecialchars($p['phone'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($p['email']); ?></td>
                                        <td><?php echo htmlspecialchars($p['blood_group'] ?? '—'); ?></td>
                                        <td><span class="badge bg-primary"><?php echo $p['total_visits']; ?></span></td>
                                        <td>
                                            <a href="patient_records.php?patient_id=<?php echo $p['user_id']; ?>"
                                               class="btn btn-sm btn-primary">View Records</a>
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

    <?php
    require_once '../footer.php';
    exit();
}

// ============================================
// CASE 2: Specific patient selected
// ============================================

$check = $conn->prepare("SELECT 1 FROM appointments WHERE doctor_id = ? AND patient_id = ? LIMIT 1");
$check->bind_param("ii", $doctor_id, $patient_id);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    $check->close();
    die("Access denied. You have no appointments with this patient.");
}
$check->close();

$stmt = $conn->prepare("
    SELECT u.name, u.email, u.phone, p.blood_group, p.date_of_birth, p.address
    FROM users u
    LEFT JOIN patients p ON u.user_id = p.patient_id
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
    die("Patient not found.");
}

$stmt = $conn->prepare("
    SELECT mr.*, u.name AS doctor_name
    FROM medical_records mr
    JOIN users u ON mr.doctor_id = u.user_id
    WHERE mr.patient_id = ?
    ORDER BY mr.record_date DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("
    SELECT pr.*, u.name AS doctor_name
    FROM prescriptions pr
    JOIN users u ON pr.doctor_id = u.user_id
    WHERE pr.patient_id = ?
    ORDER BY pr.prescribed_at DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = "Patient Records – " . $patient['name'];
require_once '../header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Patient Medical Records</h2>
            <p class="text-muted mb-0"><?php echo htmlspecialchars($patient['name']); ?></p>
        </div>
        <div>
            <a href="patient_records.php" class="btn btn-outline-secondary me-2">← All Patients</a>
            <a href="dashboard.php" class="btn btn-outline-secondary">Dashboard</a>
        </div>
    </div>

    <!-- Patient Info -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="fw-bold"><?php echo htmlspecialchars($patient['name']); ?></h4>
                    <p class="mb-1"><i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($patient['email']); ?></p>
                    <p class="mb-1"><i class="bi bi-telephone me-2"></i><?php echo htmlspecialchars($patient['phone'] ?? '—'); ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Blood Group:</strong> <?php echo htmlspecialchars($patient['blood_group'] ?? '—'); ?></p>
                    <p class="mb-1"><strong>Date of Birth:</strong>
                        <?php echo $patient['date_of_birth'] ? date('d M Y', strtotime($patient['date_of_birth'])) : '—'; ?>
                    </p>
                    <p class="mb-0"><strong>Address:</strong> <?php echo htmlspecialchars($patient['address'] ?? '—'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold">Medical Records</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($records)): ?>
                        <p class="text-muted text-center py-4">No medical records found.</p>
                    <?php else: ?>
                        <?php foreach ($records as $r): ?>
                            <div class="border rounded-3 p-3 mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong><?php echo htmlspecialchars($r['diagnosis'] ?? 'General Note'); ?></strong>
                                    <small class="text-muted"><?php echo date('d M Y', strtotime($r['record_date'])); ?></small>
                                </div>
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($r['notes'] ?? '')); ?></p>
                                <small class="text-muted">By Dr. <?php echo htmlspecialchars($r['doctor_name']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold">Prescriptions</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($prescriptions)): ?>
                        <p class="text-muted text-center py-4">No prescriptions found.</p>
                    <?php else: ?>
                        <?php foreach ($prescriptions as $p): ?>
                            <div class="border rounded-3 p-3 mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong><?php echo htmlspecialchars($p['medicine_name']); ?></strong>
                                    <small class="text-muted"><?php echo date('d M Y', strtotime($p['prescribed_at'])); ?></small>
                                </div>
                                <p class="mb-1">
                                    <strong>Dosage:</strong> <?php echo htmlspecialchars($p['dosage']); ?><br>
                                    <strong>Duration:</strong> <?php echo htmlspecialchars($p['duration']); ?>
                                </p>
                                <?php if (!empty($p['instructions'])): ?>
                                    <p class="mb-1"><em><?php echo htmlspecialchars($p['instructions']); ?></em></p>
                                <?php endif; ?>
                                <small class="text-muted">By Dr. <?php echo htmlspecialchars($p['doctor_name']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>