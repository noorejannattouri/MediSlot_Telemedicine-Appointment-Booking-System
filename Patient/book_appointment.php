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
$slot_id    = isset($_GET['slot_id']) ? (int)$_GET['slot_id'] : 0;
$doctor_id  = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;

$errors  = [];
$success = false;

// Validate slot
if ($slot_id <= 0 || $doctor_id <= 0) {
    header("Location: search_doctors.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT ts.*, d.consultation_fee, u.name AS doctor_name
    FROM time_slots ts
    JOIN doctors d ON ts.doctor_id = d.doctor_id
    JOIN users u ON d.doctor_id = u.user_id
    WHERE ts.slot_id = ? AND ts.doctor_id = ? AND ts.is_booked = 0
");
$stmt->bind_param("ii", $slot_id, $doctor_id);
$stmt->execute();
$slot = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$slot) {
    die("This time slot is no longer available.");
}

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $conn->begin_transaction();

    try {
        // 1. Create appointment
        $fee = $slot['consultation_fee'];
        $status = 'pending';

        $stmt = $conn->prepare("
            INSERT INTO appointments (patient_id, doctor_id, slot_id, status, fee)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiisd", $patient_id, $doctor_id, $slot_id, $status, $fee);
        $stmt->execute();
        $appointment_id = $stmt->insert_id;
        $stmt->close();

        // 2. Mark slot as booked
        $stmt = $conn->prepare("UPDATE time_slots SET is_booked = 1 WHERE slot_id = ?");
        $stmt->bind_param("i", $slot_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        // Redirect to payment page
        header("Location: payment.php?appointment_id=" . $appointment_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = "Booking failed. Please try again.";
    }
}

$page_title = "Confirm Booking";
require_once '../header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">

            <div class="mb-3">
                <a href="doctor_profile.php?doctor_id=<?php echo $doctor_id; ?>" class="btn btn-outline-secondary btn-sm">
                    ← Back
                </a>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-4 text-center">Confirm Appointment</h3>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($errors[0]); ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <p class="mb-2"><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($slot['doctor_name']); ?></p>
                        <p class="mb-2"><strong>Date:</strong> <?php echo date('l, d F Y', strtotime($slot['slot_date'])); ?></p>
                        <p class="mb-2"><strong>Time:</strong> 
                            <?php echo date('h:i A', strtotime($slot['start_time'])); ?> – 
                            <?php echo date('h:i A', strtotime($slot['end_time'])); ?>
                        </p>
                        <p class="mb-0"><strong>Consultation Fee:</strong> 
                            <span class="text-success fw-bold"><?php echo number_format($slot['consultation_fee'], 0); ?> BDT</span>
                        </p>
                    </div>

                    <form method="POST">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-calendar-check me-2"></i> Confirm & Proceed to Payment
                            </button>
                            <a href="doctor_profile.php?doctor_id=<?php echo $doctor_id; ?>" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>