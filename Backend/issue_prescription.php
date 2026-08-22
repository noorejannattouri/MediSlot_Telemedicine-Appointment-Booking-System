<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

$doctor_id      = $_SESSION['user_id'];
$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
$errors         = [];
$success        = '';

// ============================================
// CASE 1: No appointment selected → show list
// ============================================
if ($appointment_id <= 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {

    $stmt = $conn->prepare("
        SELECT a.appointment_id, a.status,
               u.name AS patient_name,
               ts.slot_date, ts.start_time
        FROM appointments a
        JOIN users u ON a.patient_id = u.user_id
        JOIN time_slots ts ON a.slot_id = ts.slot_id
        WHERE a.doctor_id = ?
          AND a.status IN ('confirmed', 'completed', 'pending')
        ORDER BY ts.slot_date DESC, ts.start_time DESC
        LIMIT 50
    ");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $page_title = "Issue Prescription";
    require_once '../header.php';
    ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Issue Prescription</h2>
                <p class="text-muted mb-0">Select an appointment to prescribe medicine</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary">← Dashboard</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($appointments)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-prescription2 fs-1 d-block mb-2"></i>
                        No appointments available for prescribing.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Patient</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $a): ?>
                                    <tr>
                                        <td>
                                            <?php echo date('d M Y', strtotime($a['slot_date'])); ?><br>
                                            <small class="text-muted"><?php echo date('h:i A', strtotime($a['start_time'])); ?></small>
                                        </td>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($a['patient_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $a['status'] === 'confirmed' ? 'success' : ($a['status'] === 'completed' ? 'primary' : 'warning'); ?>">
                                                <?php echo ucfirst($a['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="issue_prescription.php?appointment_id=<?php echo $a['appointment_id']; ?>"
                                               class="btn btn-sm btn-success">Prescribe</a>
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
// CASE 2: Specific appointment selected
// ============================================

$appointment = null;
if ($appointment_id > 0) {
    $stmt = $conn->prepare("
        SELECT a.*, u.name AS patient_name, u.user_id AS patient_id
        FROM appointments a
        JOIN users u ON a.patient_id = u.user_id
        WHERE a.appointment_id = ? AND a.doctor_id = ?
    ");
    $stmt->bind_param("ii", $appointment_id, $doctor_id);
    $stmt->execute();
    $appointment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$appointment) {
        die("Appointment not found or access denied.");
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = (int)($_POST['appointment_id'] ?? 0);
    $patient_id     = (int)($_POST['patient_id'] ?? 0);
    $medicine_name  = trim($_POST['medicine_name'] ?? '');
    $dosage         = trim($_POST['dosage'] ?? '');
    $duration       = trim($_POST['duration'] ?? '');
    $instructions   = trim($_POST['instructions'] ?? '');

    if (empty($medicine_name) || empty($dosage) || empty($duration)) {
        $errors[] = "Medicine name, dosage and duration are required.";
    }
    if ($appointment_id <= 0 || $patient_id <= 0) {
        $errors[] = "Invalid appointment data.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO prescriptions
                (appointment_id, patient_id, doctor_id, medicine_name, dosage, duration, instructions, prescribed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iiissss", $appointment_id, $patient_id, $doctor_id, $medicine_name, $dosage, $duration, $instructions);

        if ($stmt->execute()) {
            $diag  = "Prescription issued: " . $medicine_name;
            $notes = "Dosage: $dosage | Duration: $duration" . ($instructions ? " | $instructions" : "");

            $rec = $conn->prepare("
                INSERT INTO medical_records (patient_id, doctor_id, appointment_id, diagnosis, notes, record_date)
                VALUES (?, ?, ?, ?, ?, CURDATE())
            ");
            $rec->bind_param("iiiss", $patient_id, $doctor_id, $appointment_id, $diag, $notes);
            $rec->execute();
            $rec->close();

            $success = "Prescription issued successfully!";
        } else {
            $errors[] = "Failed to save prescription. Please try again.";
        }
        $stmt->close();
    }
}

$page_title = "Issue Prescription";
require_once '../header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Issue Prescription</h2>
                <a href="issue_prescription.php" class="btn btn-outline-secondary">← Select Another</a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <div class="mt-2">
                        <a href="issue_prescription.php" class="btn btn-sm btn-success">Issue Another</a>
                        <a href="view_appointments.php" class="btn btn-sm btn-outline-primary">Back to Appointments</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($appointment && empty($success)): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <p class="mb-1"><strong>Patient:</strong> <?php echo htmlspecialchars($appointment['patient_name']); ?></p>
                        <p class="mb-0"><strong>Appointment ID:</strong> #<?php echo $appointment['appointment_id']; ?></p>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST">
                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                            <input type="hidden" name="patient_id" value="<?php echo $appointment['patient_id']; ?>">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Medicine Name *</label>
                                <input type="text" name="medicine_name" class="form-control form-control-lg"
                                       placeholder="e.g. Paracetamol 500mg" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Dosage *</label>
                                    <input type="text" name="dosage" class="form-control form-control-lg"
                                           placeholder="e.g. 1 tablet twice daily" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Duration *</label>
                                    <input type="text" name="duration" class="form-control form-control-lg"
                                           placeholder="e.g. 5 days / 1 week" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Additional Instructions</label>
                                <textarea name="instructions" class="form-control form-control-lg" rows="3"
                                          placeholder="Take after meals, avoid alcohol, etc."></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-prescription2 me-2"></i>Issue Prescription
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>