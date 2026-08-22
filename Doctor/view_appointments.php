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
$errors = [];
$success = '';

// Update status
if (isset($_POST['update_status']) && isset($_POST['appointment_id'])) {
    $appt_id    = (int)$_POST['appointment_id'];
    $new_status = $_POST['status'] ?? '';

    $allowed = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (in_array($new_status, $allowed)) {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?");
        $stmt->bind_param("sii", $new_status, $appt_id, $doctor_id);
        if ($stmt->execute()) {
            $success = "Appointment status updated successfully.";
        } else {
            $errors[] = "Failed to update status.";
        }
        $stmt->close();
    }
}

// Filters
$status_filter = $_GET['status'] ?? 'all';
$date_filter   = $_GET['date'] ?? '';

$sql = "
    SELECT a.appointment_id, a.patient_id, a.status, a.created_at,
           u.name AS patient_name, u.phone AS patient_phone, u.email AS patient_email,
           ts.slot_date, ts.start_time, ts.end_time,
           p.blood_group
    FROM appointments a
    JOIN users u ON a.patient_id = u.user_id
    JOIN time_slots ts ON a.slot_id = ts.slot_id
    LEFT JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.doctor_id = ?
";
$params = [$doctor_id];
$types  = "i";

if ($status_filter !== 'all') {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}
if (!empty($date_filter)) {
    $sql .= " AND ts.slot_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

$sql .= " ORDER BY ts.slot_date DESC, ts.start_time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = "My Appointments";
require_once '../header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">My Appointments</h2>
            <p class="text-muted mb-0">All appointments booked with you</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary">← Dashboard</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars(implode('<br>', $errors)); ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($appointments)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                    No appointments found.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Patient</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th style="min-width: 280px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $a): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo date('d M Y', strtotime($a['slot_date'])); ?></div>
                                        <small class="text-muted">
                                            <?php echo date('h:i A', strtotime($a['start_time'])); ?> –
                                            <?php echo date('h:i A', strtotime($a['end_time'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($a['patient_name']); ?>
                                        <?php if (!empty($a['blood_group'])): ?>
                                            <br><small class="text-muted">BG: <?php echo htmlspecialchars($a['blood_group']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($a['patient_phone'] ?? '—'); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($a['patient_email']); ?></small>
                                    </td>
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
                                        <span class="badge bg-<?php echo $badge; ?>"><?php echo ucfirst($a['status']); ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <a href="patient_records.php?patient_id=<?php echo $a['patient_id']; ?>"
                                               class="btn btn-sm btn-outline-info">Records</a>
                                            <a href="issue_prescription.php?appointment_id=<?php echo $a['appointment_id']; ?>"
                                               class="btn btn-sm btn-outline-success">Prescribe</a>

                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="appointment_id" value="<?php echo $a['appointment_id']; ?>">
                                                <select name="status" class="form-select form-select-sm d-inline-block w-auto"
                                                        onchange="this.form.submit()">
                                                    <option value="">Change status…</option>
                                                    <option value="confirmed">Confirm</option>
                                                    <option value="completed">Complete</option>
                                                    <option value="cancelled">Cancel</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                        </div>
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