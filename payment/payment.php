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
$errors         = [];
$success        = false;

// Validate appointment
if ($appointment_id <= 0) {
    header("Location: appointments.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT a.*, u.name AS doctor_name, d.consultation_fee,
           ts.slot_date, ts.start_time, ts.end_time
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    JOIN users u ON a.doctor_id = u.user_id
    JOIN time_slots ts ON a.slot_id = ts.slot_id
    WHERE a.appointment_id = ? AND a.patient_id = ?
");
$stmt->bind_param("ii", $appointment_id, $patient_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appointment) {
    die("Appointment not found.");
}

// Check if already paid
$check = $conn->prepare("SELECT payment_id FROM payments WHERE appointment_id = ? AND status = 'completed'");
$check->bind_param("i", $appointment_id);
$check->execute();
$check->store_result();
$already_paid = $check->num_rows > 0;
$check->close();

// Handle payment form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_paid) {

    $method = $_POST['method'] ?? '';
    $allowed_methods = ['bkash', 'card', 'nagad'];

    if (!in_array($method, $allowed_methods)) {
        $errors[] = "Please select a valid payment method.";
    } else {
        $amount = $appointment['fee'];
        $status = 'completed'; // For demo we mark as completed
        $transaction_id = strtoupper($method) . '-' . time() . '-' . rand(1000, 9999);

        $stmt = $conn->prepare("
            INSERT INTO payments (appointment_id, amount, method, status, transaction_id, paid_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("idsss", $appointment_id, $amount, $method, $status, $transaction_id);

        if ($stmt->execute()) {
            // Update appointment status to confirmed
            $upd = $conn->prepare("UPDATE appointments SET status = 'confirmed' WHERE appointment_id = ?");
            $upd->bind_param("i", $appointment_id);
            $upd->execute();
            $upd->close();

            $success = true;
        } else {
            $errors[] = "Payment failed. Please try again.";
        }
        $stmt->close();
    }
}

$page_title = "Payment";
require_once '../header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">

            <div class="mb-3">
                <a href="appointments.php" class="btn btn-outline-secondary btn-sm">← My Appointments</a>
            </div>

            <?php if ($success || $already_paid): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5 text-center">
                        <div class="text-success mb-3">
                            <i class="bi bi-check-circle-fill" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="fw-bold mb-2">Payment Successful!</h3>
                        <p class="text-muted mb-4">Your appointment has been confirmed.</p>
                        <a href="appointments.php" class="btn btn-primary">View My Appointments</a>
                    </div>
                </div>
            <?php else: ?>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-3">Appointment Summary</h4>
                        <p class="mb-1"><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                        <p class="mb-1"><strong>Date:</strong> <?php echo date('d M Y', strtotime($appointment['slot_date'])); ?></p>
                        <p class="mb-1"><strong>Time:</strong> 
                            <?php echo date('h:i A', strtotime($appointment['start_time'])); ?> – 
                            <?php echo date('h:i A', strtotime($appointment['end_time'])); ?>
                        </p>
                        <p class="mb-0"><strong>Amount to Pay:</strong> 
                            <span class="text-success fw-bold fs-5"><?php echo number_format($appointment['fee'], 0); ?> BDT</span>
                        </p>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-4">Select Payment Method</h4>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($errors[0]); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-4">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="method" id="bkash" value="bkash" required>
                                    <label class="form-check-label fw-semibold" for="bkash">
                                        <i class="bi bi-phone me-2 text-danger"></i> bKash
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="method" id="nagad" value="nagad">
                                    <label class="form-check-label fw-semibold" for="nagad">
                                        <i class="bi bi-phone me-2 text-warning"></i> Nagad
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="method" id="card" value="card">
                                    <label class="form-check-label fw-semibold" for="card">
                                        <i class="bi bi-credit-card me-2 text-primary"></i> Credit / Debit Card
                                    </label>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-lock me-2"></i> Pay <?php echo number_format($appointment['fee'], 0); ?> BDT
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