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
$success = '';
$errors  = [];

// Cancel appointment
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $appointment_id = (int)$_GET['cancel'];

    $stmt = $conn->prepare("
        SELECT a.slot_id, a.status 
        FROM appointments a 
        WHERE a.appointment_id = ? AND a.patient_id = ?
    ");
    $stmt->bind_param("ii", $appointment_id, $patient_id);
    $stmt->execute();
    $appt = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($appt && in_array($appt['status'], ['pending', 'confirmed'])) {
        $conn->begin_transaction();
        try {
            // Update appointment status
            $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ?");
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            $stmt->close();

            // Free the time slot
            $stmt = $conn->prepare("UPDATE time_slots SET is_booked = 0 WHERE slot_id = ?");
            $stmt->bind_param("i", $appt['slot_id']);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $success = "Appointment cancelled successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Failed to cancel appointment.";
        }
    }
}

// Get all appointments
$stmt = $conn->prepare("
    SELECT a.appointment_id, a.status, a.fee, a.created_at,
           u.name AS doctor_name, d.specialization,
           ts.slot_date, ts.start_time, ts.end_time
    FROM appointments a
    JOIN users u ON a.doctor_id = u.user_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    JOIN time_slots ts ON a.slot_id = ts.slot_id
    WHERE a.patient_id = ?
    ORDER BY ts.slot_date DESC, ts.start_time DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = "My Appointments";
require_once '../header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">My Appointments</h2>
            <p class="text-muted mb-0">View and manage your appointments</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary">← Dashboard</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errors[0]); ?></div>
    <?php endif; ?>

    <?php if (empty($appointments)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-calendar-x fs-1 text-muted d-block mb-3"></i>
                <h5>No appointments found</h5>
                <p class="text-muted">You haven't booked any appointments yet.</p>
                <a href="search_doctors.php" class="btn btn-primary">Find a Doctor</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Doctor</th>
                                <th>Fee</th>
                                <th>Status</th>
                                <th style="min-width: 220px;">Action</th>
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
                                        <div class="fw-semibold">Dr. <?php echo htmlspecialchars($a['doctor_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($a['specialization']); ?></small>
                                    </td>
                                    <td><?php echo number_format($a['fee'], 0); ?> BDT</td>
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
                                        <div class="d-flex gap-1 flex-wrap">

                                            <!-- Reminder AI Button -->
                                            <?php if (in_array($a['status'], ['pending', 'confirmed'])): ?>
                                                <a href="appointment_reminder.php?appointment_id=<?php echo $a['appointment_id']; ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="bi bi-bell"></i> Reminder
                                                </a>
                                            <?php endif; ?>

                                            <!-- Cancel Button -->
                                            <?php if (in_array($a['status'], ['pending', 'confirmed'])): ?>
                                                <a href="appointments.php?cancel=<?php echo $a['appointment_id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                    Cancel
                                                </a>
                                            <?php endif; ?>

                                            <!-- Pay Now Button -->
                                            <?php if ($a['status'] === 'pending'): ?>
                                                <a href="payment.php?appointment_id=<?php echo $a['appointment_id']; ?>" 
                                                   class="btn btn-sm btn-success">
                                                    Pay Now
                                                </a>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../footer.php'; ?>