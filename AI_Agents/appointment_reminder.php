<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id     = $_SESSION['user_id'];
$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
$success        = false;
$error          = "";
$reminder_text  = "";

// Get appointment details
if ($appointment_id > 0) {
    $stmt = $conn->prepare("
        SELECT a.appointment_id, a.status, a.fee,
               u.name AS doctor_name, d.specialization,
               ts.slot_date, ts.start_time, ts.end_time
        FROM appointments a
        JOIN users u ON a.doctor_id = u.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN time_slots ts ON a.slot_id = ts.slot_id
        WHERE a.appointment_id = ? AND a.patient_id = ?
          AND a.status IN ('pending', 'confirmed')
    ");
    $stmt->bind_param("ii", $appointment_id, $patient_id);
    $stmt->execute();
    $appointment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$appointment) {
        $error = "Appointment not found or already completed/cancelled.";
    }
} else {
    $error = "Invalid appointment.";
}

// Generate AI Reminder
if ($appointment && empty($error)) {

    $doctor   = $appointment['doctor_name'];
    $spec     = $appointment['specialization'];
    $date     = date('l, d F Y', strtotime($appointment['slot_date']));
    $time     = date('h:i A', strtotime($appointment['start_time']));
    $end_time = date('h:i A', strtotime($appointment['end_time']));

    // Smart reminder text (AI-style)
    $reminder_text = "🔔 Appointment Reminder – MediSlot

Dear " . htmlspecialchars($_SESSION['name']) . ",

This is a friendly reminder for your upcoming appointment.

📌 Doctor       : Dr. $doctor ($spec)
📅 Date         : $date
⏰ Time         : $time – $end_time
💰 Fee          : " . number_format($appointment['fee'], 0) . " BDT
📍 Status       : " . ucfirst($appointment['status']) . "

Please arrive 10 minutes early.
If you need to cancel or reschedule, please do it from your dashboard.

Stay healthy!
— MediSlot AI Assistant";

    // Save reminder in database
    $agent_id = 3; // Appointment Reminder Agent (from your seed data)

    $stmt = $conn->prepare("
        INSERT INTO appointment_reminders (appointment_id, agent_id, reminder_type, status)
        VALUES (?, ?, 'before', 'sent')
    ");
    $stmt->bind_param("ii", $appointment_id, $agent_id);
    $stmt->execute();
    $stmt->close();

    $success = true;
}

$page_title = "Appointment Reminder";
require_once '../header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">
                    <i class="bi bi-bell text-warning me-2"></i> Appointment Reminder AI
                </h3>
                <a href="appointments.php" class="btn btn-outline-secondary btn-sm">← Back</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="bi bi-robot me-2"></i> AI Generated Reminder
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="bg-light rounded-3 p-4" style="white-space: pre-line; line-height: 1.7; font-size: 1.05rem;">
                            <?php echo htmlspecialchars($reminder_text); ?>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button onclick="copyReminder()" class="btn btn-primary">
                                <i class="bi bi-clipboard me-1"></i> Copy Reminder
                            </button>
                            <a href="appointments.php" class="btn btn-outline-secondary">Back to Appointments</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function copyReminder() {
    const text = `<?php echo addslashes($reminder_text); ?>`;
    navigator.clipboard.writeText(text).then(() => {
        alert("Reminder copied to clipboard!");
    });
}
</script>

<?php require_once '../footer.php'; ?>